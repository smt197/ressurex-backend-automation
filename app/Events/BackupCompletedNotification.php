<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupCompletedNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $status; // 'completed' ou 'failed'

    public string $message;

    public ?string $backupIdentifier; // Optionnel, si vous voulez l'utiliser

    public function __construct(string $status, string $message, ?string $backupIdentifier = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->backupIdentifier = $backupIdentifier;
    }

    public function broadcastOn()
    {
        return new Channel('global-backup-status'); // Un nouveau nom de canal ou le même
    }

    public function broadcastWith()
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'backup_identifier' => $this->backupIdentifier,
        ];
    }
}
