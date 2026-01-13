<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Diffuser sur un canal privé pour cette conversation spécifique
        return new PrivateChannel('chat.'.$this->message->conversation_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new-message'; // Le frontend écoutera cet événement
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message, // Peut être une légende ou le nom du fichier
            'timestamp' => $this->message->created_at->toIso8601String(),
            'message_type' => $this->message->message_type,
            'file_url' => $this->message->file_url, // Important !
            'file_name' => $this->message->file_name,
            'file_mime_type' => $this->message->file_mime_type,
            'file_size' => $this->message->file_size,
        ];
    }
}
