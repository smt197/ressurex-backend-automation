<?php

use App\Jobs\ProcessSendEmailToAllUsers;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('app:create-user')->everyMinute();
// Schedule::job(new ProcessSendEmailToAllUsers)->everyMinute();
// Schedule::command('app:create-user')->{config('scheduling.create_user', 'everyMinute')}();
Schedule::job(new ProcessSendEmailToAllUsers)->{config('scheduling.send_email_to_all_users', 'everyMinute')}();
Schedule::command('telescope:prune --hours=48')->daily();
