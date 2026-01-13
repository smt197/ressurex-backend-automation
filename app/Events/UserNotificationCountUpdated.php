<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public int $unreadCount;

    public function __construct(User $user, int $unreadCount)
    {
        $this->user = $user;
        $this->unreadCount = $unreadCount;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user-unreadcount.'.$this->user->id);
    }

    public function broadcastAs()
    {
        return 'unread.count.updated'; // Nom d'événement clair pour le client
    }

    public function broadcastWith()
    {
        return [
            'unreadnotificationcount' => $this->unreadCount,
        ];
    }
}
