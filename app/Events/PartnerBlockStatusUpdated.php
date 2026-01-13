<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartnerBlockStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public bool $isBlocked;

    public Conversation $conversation;

    public function __construct(User $user, bool $isBlocked, Conversation $conversation)
    {
        $this->user = $user;
        $this->isBlocked = $isBlocked;
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.'.$this->conversation->id);
    }

    public function broadcastAs()
    {
        return 'partner.block.status.updated';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->id,
            'conversation_id' => $this->conversation->id,
            'is_blocked' => $this->isBlocked,
            'message' => $this->isBlocked ? 'Partner has been blocked' : 'Partner has been unblocked',
        ];
    }
}
