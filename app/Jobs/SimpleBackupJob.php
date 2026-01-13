<?php

namespace App\Jobs;

use App\Events\BackupCompletedNotification; // Notre nouvel événement
use App\Events\UserNotificationCountUpdated;
use App\Models\User;
use App\Notifications\UserSpecificNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Pour un indicateur simple "en cours"
use Illuminate\Support\Str;

class SimpleBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobIdentifier;

    public $tries = 1;

    public $timeout = 3600; // 1 heure

    public User $user;

    const CACHE_KEY_JOB_RUNNING_ID = 'simple_backup_running_job_id';

    public function __construct(User $user)
    {
        $this->jobIdentifier = (string) Str::uuid();
        $this->user = $user;
    }

    public function handle()
    {
        $this->setUserLocale();

        Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Current backup config 'source.files.include': ".json_encode(config('backup.backup.source.files.include')));
        Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Current backup config 'source.databases': ".json_encode(config('backup.backup.source.databases')));
        Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Current backup config 'destination.disks': ".json_encode(config('backup.backup.destination.disks')));
        Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Starting backup process.");
        // Marquer qu'un backup est en cours
        Cache::put(self::CACHE_KEY_JOB_RUNNING_ID, $this->jobIdentifier, now()->addHours(2));

        $status = 'failed'; // Par défaut à échec
        $message = 'Backup process encountered an unknown issue.';
        $exitCode = -1;

        try {

            $exitCode = Artisan::call('backup:run --quiet');

            if ($exitCode === 0) {
                $status = 'completed';
                $message = __('backup.status');

                Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Backup command successful (exit code 0).");
            } else {
                $message = "Backup command failed with exit code: {$exitCode}. Check server logs for details from spatie/laravel-backup.";
            }
        } catch (\Throwable $e) {
            $message = 'Backup process failed due to an exception: '.Str::limit($e->getMessage(), 150);
            // $status reste 'failed'
        } finally {
            // Diffuser le résultat final
            Log::info("[SimpleBackupJob ID: {$this->jobIdentifier}] Broadcasting final state: S={$status}, M={$message}");
            broadcast(new BackupCompletedNotification($status, $message, $this->jobIdentifier))->toOthers();
            // Nettoyer l'indicateur "en cours"

            $this->user->notify(new UserSpecificNotification($this->user, $message));

            event(new UserNotificationCountUpdated($this->user, $this->user->unreadNotifications()->count()));

            Cache::forget(self::CACHE_KEY_JOB_RUNNING_ID);
        }
    }

    protected function setUserLocale(): void
    {
        // Utiliser la langue préférée de l'utilisateur ou 'fr' par défaut
        $locale = $this->user->preferred_language['code'] ?? 'fr';
        App::setLocale($locale);
    }

    public function failed(\Throwable $exception)
    {
        $message = 'Backup job failed permanently: '.Str::limit($exception->getMessage(), 150);
        broadcast(new BackupCompletedNotification('failed', $message, $this->jobIdentifier))->toOthers();
        Cache::forget(self::CACHE_KEY_JOB_RUNNING_ID);
    }
}
