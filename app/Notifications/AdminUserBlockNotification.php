<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AdminUserBlockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public User $blockedUser;

    public User $admin;

    public bool $isBlocked;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $blockedUser, User $admin, bool $isBlocked)
    {
        $this->blockedUser = $blockedUser;
        $this->admin = $admin;
        $this->isBlocked = $isBlocked;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $message = $this->isBlocked
            ? "User {$this->blockedUser->first_name} {$this->blockedUser->last_name} has been blocked"
            : "User {$this->blockedUser->first_name} {$this->blockedUser->last_name} has been unblocked";

        return [
            'blocked_user_id' => $this->blockedUser->id,
            'blocked_user_name' => $this->blockedUser->first_name.' '.$this->blockedUser->last_name,
            'blocked_user_email' => $this->blockedUser->email,
            'admin_id' => $this->admin->id,
            'is_blocked' => $this->isBlocked,
            'message' => $message,
            'notification_type' => 'admin_user_block_action',
            'model_type' => 'admin_user_block_action',
            'target_user' => [
                'id' => $this->blockedUser->id,
                'slug' => $this->blockedUser->slug,
                'first_name' => $this->blockedUser->first_name,
                'last_name' => $this->blockedUser->last_name,
                'email' => $this->blockedUser->email,
            ],
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the type of the notification.
     */
    public function broadcastAs(): string
    {
        return 'admin.user.block.action';
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->admin->id);
    }
}
