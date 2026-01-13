<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GithubSettings extends Settings
{
    public ?string $github_token;

    public static function group(): string
    {
        return 'github';
    }
}
