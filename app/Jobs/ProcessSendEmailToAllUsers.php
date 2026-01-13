<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\sendEmailNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSendEmailToAllUsers implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct() {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            $chunkSize = config('mail.bulk_email_chunk_size', 100);

            $today = now()->format('Y-m-d');

            User::whereNotNull('email_verified_at')
                ->whereNotNull('email')
                ->whereNotExists(function ($query) use ($today) {
                    $query->select(DB::raw(1))
                        ->from('notifications')
                        ->whereColumn('notifiable_id', 'users.id')
                        ->where('notifiable_type', 'App\\Models\\User')
                        ->where('type', 'App\\Notifications\\sendEmailNotification')
                        ->whereDate('created_at', $today)
                        ->whereJsonContains('data->type', 'notification_rappel');
                })
                ->chunk($chunkSize, function ($users) use ($notificationService) {
                    foreach ($users as $user) {
                        try {
                            $type = 'notification_rappel';
                            $app_url = config('app.url_frontend');
                            $user->notify(new sendEmailNotification($type, $user, $app_url, $user->getLocale()));
                            $messageContent = __('email.security_reminder_text');
                            $notificationService->sendNotificationToUser($user, $messageContent);
                        } catch (\Exception $e) {
                            Log::warning("Failed to send notification to user {$user->id}: ".$e->getMessage());
                        }
                    }
                });

            $processedCount = User::whereNotNull('email_verified_at')
                ->whereNotNull('email')
                ->whereNotExists(function ($query) use ($today) {
                    $query->select(DB::raw(1))
                        ->from('notifications')
                        ->whereColumn('notifiable_id', 'users.id')
                        ->where('notifiable_type', 'App\\Models\\User')
                        ->where('type', 'App\\Notifications\\sendEmailNotification')
                        ->whereDate('created_at', $today)
                        ->whereJsonContains('data->type', 'notification_rappel');
                })
                ->count();

            Log::info("Emails and notifications sent to {$processedCount} users successfully.");
        } catch (\Exception $e) {
            Log::error('Error sending emails and notifications to all users: '.$e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSendEmailToAllUsers job failed completely: '.$exception->getMessage());
    }
}
