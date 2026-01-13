<?php

namespace App\Settings;

use App\Models\AppSettingsMedia;
use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $site_name;

    public ?string $site_description;

    public ?string $site_logo;

    public ?string $site_subtitle;

    public bool $site_active = true;

    public string $site_web;

    public static function group(): string
    {
        return 'general';
    }

    public function getLogoUrl(): ?string
    {
        $media = AppSettingsMedia::getMediaForSetting('site_logo');

        return $media?->getUrl();
    }
}
