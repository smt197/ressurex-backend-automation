<?php

namespace App\Events;

use App\Models\Deployment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeploymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $deployment_id;
    public string $module_slug;
    public string $branch_name;
    public string $status;
    public ?string $message;
    public ?int $progress;
    public ?string $started_at;
    public ?string $finished_at;
    public ?array $logs;

    private int $user_id;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $user_id,
        string $deployment_id,
        string $module_slug,
        string $branch_name,
        string $status,
        ?string $message = null,
        ?int $progress = null,
        ?array $logs = null,
        ?string $started_at = null,
        ?string $finished_at = null
    ) {
        $this->user_id = $user_id;
        $this->deployment_id = $deployment_id;
        $this->module_slug = $module_slug;
        $this->branch_name = $branch_name;
        $this->status = $status;
        $this->message = $message;
        $this->progress = $progress;
        $this->logs = $logs;
        $this->started_at = $started_at ?? now()->toIso8601String();
        $this->finished_at = $finished_at;
    }

    /**
     * Create event from a Deployment model
     */
    public static function fromDeployment(Deployment $deployment): self
    {
        return new self(
            user_id: $deployment->user_id,
            deployment_id: (string) $deployment->id,
            module_slug: $deployment->module_slug,
            branch_name: $deployment->branch_name,
            status: $deployment->status,
            message: $deployment->message,
            progress: $deployment->progress,
            logs: $deployment->logs,
            started_at: $deployment->started_at?->toIso8601String(),
            finished_at: $deployment->finished_at?->toIso8601String()
        );
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'deployment.status-updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'deployment_id' => $this->deployment_id,
            'module_slug' => $this->module_slug,
            'branch_name' => $this->branch_name,
            'status' => $this->status,
            'message' => $this->message,
            'progress' => $this->progress,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'logs' => $this->logs,
        ];
    }
}
