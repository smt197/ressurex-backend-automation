<?php

namespace App\Notifications;

use App\Models\User; // <-- Utiliser la Collection Eloquent
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobStatusUpdatedNotification extends Notification
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Envoyer la notification après le commit de la transaction pour s'assurer
     * que les données sont bien persistées avant la notification.
     */
    public bool $afterCommit = true;

    public Collection $activeJobs;

    public User $user;

    public function __construct(Collection $activeJobs, User $user)
    {
        $this->activeJobs = $activeJobs;
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->user->id);
    }

    public function broadcastAs(): string
    {
        return 'user-jobs.status-updated';
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'active_jobs' => $this->activeJobs->toArray(),
        ]);
    }
}
