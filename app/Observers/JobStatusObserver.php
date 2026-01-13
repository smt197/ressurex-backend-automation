<?php

namespace App\Observers;

use App\Events\JobStatusUpdated;
use Illuminate\Support\Facades\Log;
use Imtigger\LaravelJobStatus\JobStatus;

class JobStatusObserver
{
    /**
     * Handle the JobStatus "created" event.
     */
    public function created(JobStatus $jobStatus): void
    {

        Log::info('start created observabele');
        // On s'assure que l'input contient bien l'user_id avant de dispatcher.
        if (isset($jobStatus->input['user_id'])) {
            Log::info('start created observabele userId');
            event(new JobStatusUpdated($jobStatus));
        }
    }

    /**
     * Handle the JobStatus "updated" event.
     */
    public function updated(JobStatus $jobStatus): void
    {
        Log::info('start updated observabele');

        if (isset($jobStatus->input['user_id'])) {
            Log::info('start updated observabele userId');

            event(new JobStatusUpdated($jobStatus));
        }
    }
}
