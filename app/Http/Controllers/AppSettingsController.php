<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseServer;
use App\Http\Requests\AppSettingsRequest;
use App\Models\AppSettingsMedia;
use App\Settings\AppSettings;

class AppSettingsController extends Controller
{
    public function index(AppSettings $settings)
    {
        return ResponseServer::getSettingsResponse($settings);
    }

    public function store(AppSettingsRequest $request, AppSettings $settings)
    {
        $validated = $request->validated();

        $settings->site_name = $validated['site_name'];
        $settings->site_description = $validated['site_description'];
        $settings->site_subtitle = $validated['site_subtitle'];
        $settings->site_active = $validated['site_active'];
        $settings->site_web = $validated['site_web'];

        if ($request->hasFile('site_logo') && $request->file('site_logo')->isValid()) {
            // Utiliser Spatie Media Library pour stocker le logo
            $media = AppSettingsMedia::setMediaForSetting('site_logo', $request->file('site_logo'));
            $settings->site_logo = $media->getUrl();
        }

        $settings->save();

        return ResponseServer::settingsUpdatedSuccess($settings);
    }
}
