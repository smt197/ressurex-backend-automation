<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AppSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $disk = 'apps';

        return [
            'site_name' => $this->site_name,
            'site_description' => __('app_settings.site_description'),
            'site_subtitle' => $this->site_subtitle,
            'site_logo' => $this->site_logo ? asset(Storage::disk($disk)->url($this->site_logo)) : null,
            'site_active' => $this->site_active,
            'site_web' => $this->site_web,
            'translations' => [
                'site_description' => [
                    'fr' => __('app_settings.site_description', [], 'fr'),
                    'pt' => __('app_settings.site_description', [], 'pt'),
                    'en' => __('app_settings.site_description', [], 'en'),
                ]
            ],

        ];
    }
}
