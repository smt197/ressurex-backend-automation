<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MenuUpdatedNotification extends Notification
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public bool $afterCommit = true;

    public Collection $menus;

    public string $action;

    public int $excludeUserId;

    public function __construct(Collection $menus, string $action = 'updated', int $excludeUserId = 0)
    {
        $this->menus = $menus;
        $this->action = $action;
        $this->excludeUserId = $excludeUserId;
    }

    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('menus');
    }

    public function broadcastAs(): string
    {
        return 'menus.updated';
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'menus' => $this->menus->toArray(),
            'action' => $this->action,
            'exclude_user_id' => $this->excludeUserId,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
