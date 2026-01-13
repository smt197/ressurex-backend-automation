<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class UserBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public User $user;

    public bool $isBlocked;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, bool $isBlocked)
    {
        $this->user = $user;
        $this->isBlocked = $isBlocked;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'is_blocked' => $this->isBlocked,
            'message' => $this->isBlocked ? 'Your account has been blocked.' : 'Your account has been unblocked.',
            'notification_type' => 'user_block_status',
            'model_type' => 'user_block_status',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'user_id' => $this->user->id,
            'is_blocked' => $this->isBlocked,
            'message' => $this->isBlocked ? 'Your account has been blocked.' : 'Your account has been unblocked.',
            'notification_type' => 'user_block_status',
            'model_type' => 'user_block_status',
        ]);
    }

    /**
     * Get the type of the notification.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'user.block.status.updated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.'.$this->user->id);
    }
}
