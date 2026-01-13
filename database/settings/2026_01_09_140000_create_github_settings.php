<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('github', function ($blueprint) {
            $blueprint->add('github_token', config('services.github.token'));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('github', function ($blueprint) {
            $blueprint->delete('github_token');
        });
    }
};
