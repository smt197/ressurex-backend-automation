<?php

namespace App\Http\Controllers;

use App\Events\DeploymentStatusUpdated;
use App\Models\Deployment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DokployWebhookController extends Controller
{
    /**
     * Handle Dokploy deployment status webhook
     *
     * This endpoint receives callbacks from Dokploy when deployment status changes.
     * Configure this URL in Dokploy: POST /api/webhooks/dokploy/deployment
     *
     * Expected payload from Dokploy:
     * {
     *   "deploymentId": "abc123",
     *   "applicationId": "app-xyz",
     *   "status": "running|done|error",
     *   "branch": "module/my-module",
     *   "commit": "sha256...",
     *   "message": "Deployment message",
     *   "logs": ["log line 1", "log line 2"],
     *   "progress": 50,
     *   "timestamp": "2024-01-15T10:30:00Z"
     * }
     */
    public function handleDeploymentStatus(Request $request)
    {
        $data = $request->all();

        Log::info('Dokploy webhook received', [
            'data' => $data,
            'headers' => $request->headers->all()
        ]);

        // Validate webhook signature if configured
        if (config('services.dokploy.webhook_secret')) {
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Invalid Dokploy webhook signature');
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        // Find the deployment record
        $deployment = $this->findDeployment($data);

        if (!$deployment) {
            Log::warning('Deployment not found for Dokploy webhook', $data);
            return response()->json(['status' => 'ignored', 'message' => 'Deployment not found'], 200);
        }

        // Map Dokploy status to our status
        $status = $this->mapDokployStatus($data['status'] ?? 'unknown');
        $isCompleted = in_array($status, [Deployment::STATUS_SUCCESS, Deployment::STATUS_FAILED]);

        // Update deployment record
        $deployment->update([
            'status' => $status,
            'message' => $data['message'] ?? null,
            'logs' => $data['logs'] ?? $deployment->logs,
            'progress' => $data['progress'] ?? null,
            'dokploy_deployment_id' => $data['deploymentId'] ?? $deployment->dokploy_deployment_id,
            'finished_at' => $isCompleted ? now() : null,
        ]);

        Log::info('Deployment status updated', [
            'deployment_id' => $deployment->id,
            'status' => $status,
            'user_id' => $deployment->user_id
        ]);

        // Broadcast to frontend via WebSocket
        event(DeploymentStatusUpdated::fromDeployment($deployment));

        return response()->json([
            'status' => 'received',
            'deployment_id' => $deployment->id,
            'new_status' => $status
        ], 200);
    }

    /**
     * Find deployment by various criteria
     */
    private function findDeployment(array $data): ?Deployment
    {
        // Try to find by Dokploy deployment ID first
        if (!empty($data['deploymentId'])) {
            $deployment = Deployment::where('dokploy_deployment_id', $data['deploymentId'])->first();
            if ($deployment) {
                return $deployment;
            }
        }

        // Try to find by branch name
        if (!empty($data['branch'])) {
            $deployment = Deployment::where('branch_name', $data['branch'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->first();
            if ($deployment) {
                return $deployment;
            }
        }

        // Try to find by module slug extracted from branch name
        // Branch format is typically: module/my-module-name
        if (!empty($data['branch']) && str_starts_with($data['branch'], 'module/')) {
            $moduleSlug = str_replace('module/', '', $data['branch']);
            $deployment = Deployment::where('module_slug', $moduleSlug)
                ->active()
                ->orderBy('created_at', 'desc')
                ->first();
            if ($deployment) {
                return $deployment;
            }
        }

        return null;
    }

    /**
     * Map Dokploy status to our internal status
     */
    private function mapDokployStatus(string $dokployStatus): string
    {
        return match (strtolower($dokployStatus)) {
            'idle', 'queued', 'pending' => Deployment::STATUS_PENDING,
            'running', 'building' => Deployment::STATUS_BUILDING,
            'deploying' => Deployment::STATUS_DEPLOYING,
            'done', 'success', 'completed' => Deployment::STATUS_SUCCESS,
            'error', 'failed', 'cancelled' => Deployment::STATUS_FAILED,
            default => Deployment::STATUS_PENDING,
        };
    }

    /**
     * Validate webhook signature from Dokploy
     */
    private function validateWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Dokploy-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $secret = config('services.dokploy.webhook_secret');

        // Support both sha256= prefix and raw hash
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        $signatureToCompare = str_replace('sha256=', '', $signature);

        return hash_equals($expectedSignature, $signatureToCompare);
    }

    /**
     * Get active deployments for a user
     * Used for polling fallback if WebSocket is not available
     */
    public function getActiveDeployments(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $deployments = Deployment::forUser($user->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deployments->map(fn($d) => [
                'deployment_id' => (string) $d->id,
                'module_slug' => $d->module_slug,
                'branch_name' => $d->branch_name,
                'status' => $d->status,
                'message' => $d->message,
                'progress' => $d->progress,
                'started_at' => $d->started_at?->toIso8601String(),
            ])
        ]);
    }

    /**
     * Get deployment status by module slug
     */
    public function getDeploymentStatus(Request $request, string $moduleSlug)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $deployment = Deployment::forUser($user->id)
            ->where('module_slug', $moduleSlug)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$deployment) {
            return response()->json([
                'success' => false,
                'message' => 'No deployment found for this module'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'deployment_id' => (string) $deployment->id,
                'module_slug' => $deployment->module_slug,
                'branch_name' => $deployment->branch_name,
                'status' => $deployment->status,
                'message' => $deployment->message,
                'progress' => $deployment->progress,
                'logs' => $deployment->logs,
                'started_at' => $deployment->started_at?->toIso8601String(),
                'finished_at' => $deployment->finished_at?->toIso8601String(),
            ]
        ]);
    }
}
