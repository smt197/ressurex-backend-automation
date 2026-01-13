<?php

namespace App\Providers;

use App\Observers\JobStatusObserver;
// Imports nécessaires
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Imtigger\LaravelJobStatus\JobStatus;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        // AJOUTEZ CETTE LIGNE
        JobStatus::class => [JobStatusObserver::class],
    ];

    // ... le reste du fichier ...
}
