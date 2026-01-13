<?php

namespace App\Models;

use Imtigger\LaravelJobStatus\JobStatus as BaseJobStatus;

class JobStatus extends BaseJobStatus
{
    protected $fillable = [
        'job_id',
        'type',
        'queue',
        'attempts',
        'progress_now',
        'progress_max',
        'status',
        'input',
        'output',
        'user_id',
        'description',
    ];
}
