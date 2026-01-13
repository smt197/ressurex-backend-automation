<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserSpecificNotification extends Notification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public array $payloadData;

    public function __construct(User $user, mixed $payload)
    {
        $this->userId = $user->id;
        $this->payloadData = $this->sanitizePayload($payload);
    }

    private function sanitizePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return array_map(function ($item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    return $item->toArray(request());
                } elseif (is_object($item)) {
                    return (array) $item;
                }

                return $item;
            }, $payload);
        } elseif (is_string($payload)) {
            return ['message' => $payload, 'notification_type' => 'string'];
        } elseif (is_object($payload) && method_exists($payload, 'toArray')) {
            return $payload->toArray(request());
        } elseif (is_object($payload)) {
            return (array) $payload;
        }

        return ['data' => $payload];
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->broadcastWithData($notifiable));
    }

    public function toArray($notifiable): array
    {
        $baseData = [
            'message' => __('message.show'),
            'notification_type' => 'generic',
            'model_type' => null,
        ];

        $type = $this->payloadData['notification_type'] ?? null;

        switch ($type) {
            case 'chat':
                $baseData['message'] = $this->payloadData['message'] ?? $baseData['message'];
                $baseData['notification_type'] = 'chat';
                $baseData['model_type'] = $this->payloadData['conversation_id'] ?? null;
                break;

            case 'user':
                $baseData['message'] = $this->payloadData['message'] ?? $baseData['message'];
                $baseData['notification_type'] = 'user';
                $baseData['model_type'] = $this->payloadData['user'] ?? null;
                break;

            case 'setting':
                $baseData['message'] = $this->payloadData['message'] ?? $baseData['message'];
                $baseData['notification_type'] = 'setting';
                $baseData['model_type'] = $this->payloadData['setting'] ?? null;
                break;

            case 'user_block_status':
                $baseData['message'] = $this->payloadData['message'] ?? $baseData['message'];
                $baseData['notification_type'] = 'user_block_status';
                $baseData['model_type'] = $this->payloadData['user_block_status'] ?? null;
                break;

            case 'string':
                $baseData['message'] = $this->payloadData['message'];
                $baseData['notification_type'] = 'simple_text_legacy';
                break;
        }

        return $baseData;
    }

    public function broadcastWithData($notifiable): array
    {
        try {
            // Comptage sécurisé des notifications non lues
            $currentUnreadCount = DatabaseNotification::where('notifiable_id', $notifiable->id)
                ->whereNull('read_at')
                ->count();

            // Récupération des 3 dernières notifications non lues
            $recentUnread = DatabaseNotification::where('notifiable_id', $notifiable->id)
                ->whereNull('read_at')
                ->latest()
                ->take(5)
                ->get();

            // Formatage sécurisé des notifications
            $formatted = [];
            if ($recentUnread->isNotEmpty()) {
                try {
                    $formatted = NotificationResource::collection($recentUnread)->toArray(request());
                } catch (\Exception $e) {
                    Log::warning('Erreur lors du formatage des notifications', [
                        'error' => $e->getMessage(),
                    ]);
                    $formatted = [];
                }
            }

            return [
                'unreadnotificationcount' => $currentUnreadCount,
                'recent_unread_list' => $formatted,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur dans broadcastWithData', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            return [
                'unreadnotificationcount' => 0,
                'recent_unread_list' => [],
                'error' => true,
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    public function broadcastAs(): string
    {
        return 'new.user.notification';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->userId);
    }

    public function failed(?\Throwable $exception = null): void
    {
        Log::error('UserSpecificNotification job échoué', [
            'user_id' => $this->userId,
            'exception' => $exception ? $exception->getMessage() : 'Erreur inconnue',
            'payload' => $this->payloadData,
        ]);
    }

    // Méthodes utilitaires
    public function getUser(): ?User
    {
        // Return the stored user object, or fetch it if not available
        return $this->user ?? User::find($this->userId);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }
}
