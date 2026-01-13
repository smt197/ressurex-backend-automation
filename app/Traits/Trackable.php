<?php

namespace App\Traits;

use App\Models\JobStatus;
use App\Models\User;
use App\Notifications\JobStatusUpdatedNotification;
use Illuminate\Support\Facades\Log;

trait Trackable
{
    /**
     * L'ID de l'enregistrement JobStatus associé à ce job.
     *
     * @var int
     */
    protected $jobStatusId;

    public function __construct(...$args) {}

    protected function prepareStatus(): void
    {
        $status = JobStatus::create([
            'type' => static::class,
            'queue' => $this->queue,
            'status' => 'queued',
        ]);
        $this->jobStatusId = $status->id;
    }

    private function getEntity(): ?JobStatus
    {
        return JobStatus::find($this->jobStatusId);
    }

    protected function update(array $data): void
    {
        $entity = $this->getEntity();
        if ($entity) {
            $entity->update($data);

            // Notifier seulement sur les changements importants (pas à chaque progress_now)
            $this->notifyUserIfNeeded($entity, $data);
        }
    }

    private function notifyUserIfNeeded(JobStatus $entity, array $data): void
    {
        // Notifier pour TOUS les changements (progression fluide en temps réel)
        if ($entity->user_id) {
            $user = User::find($entity->user_id);
            if ($user) {
                $activeJobs = JobStatus::where('user_id', $user->id)
                    ->whereIn('status', ['queued', 'executing'])
                    ->latest()
                    ->get();

                $progressNow = $data['progress_now'] ?? $entity->progress_now ?? 'N/A';
                $progressMax = $entity->progress_max ?? 'N/A';

                if (isset($data['progress_now']) || isset($data['status'])) {
                    Log::info("LIVE PROGRESS - User {$user->id} has {$activeJobs->count()} active jobs. Progress: {$progressNow}/{$progressMax}. Status: ".($data['status'] ?? $entity->status));
                    $user->notify(new JobStatusUpdatedNotification($activeJobs, $user));
                }
            }
        }
    }

    public function getJobStatusId(): ?int
    {
        return $this->jobStatusId;
    }

    protected function setProgressMax(int $value): void
    {
        $this->update(['progress_max' => $value]);
    }

    protected function setProgressNow(int $value): void
    {
        $this->update(['progress_now' => $value, 'status' => 'executing']);
    }

    protected function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Met à jour la progression avec des décimales pour une progression plus fine
     */
    protected function setProgressNowFloat(float $value): void
    {
        $this->update(['progress_now' => $value, 'status' => 'executing']);
    }

    /**
     * Incrémente la progression par petites étapes pour un fichier donné
     */
    protected function incrementProgressForFile(int $fileIndex, int $totalFiles, float $fileProgressPercent): void
    {
        // Chaque fichier représente (100 / totalFiles)% du total
        $fileWeight = 100.0 / $totalFiles;

        // Progression actuelle = (fichiers terminés * poids) + (progression du fichier actuel * poids)
        $totalProgress = ($fileIndex * $fileWeight) + ($fileProgressPercent * $fileWeight / 100.0);

        $this->setProgressNowFloat($totalProgress);
    }

    protected function setInput(array $value): void
    {
        $this->update(['input' => $value]);
    }

    protected function setOutput(array $value): void
    {
        $this->update(['output' => $value]);
    }
}
