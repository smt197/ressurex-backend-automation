<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppSettingsMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'app_settings_media';

    protected $fillable = ['setting_key'];

    public function registerMediaCollections(): void
    {
        $disk = config('filesystems.default'); // Utilisez le disque par défaut
        $this->addMediaCollection('logos')
            ->acceptsMimeTypes(['image/png', 'image/jpg', 'image/jpeg'])
            ->singleFile()
            ->useDisk($disk);
    }

    public static function getMediaForSetting(string $settingKey): ?Media
    {
        $mediaModel = self::where('setting_key', $settingKey)->first();

        return $mediaModel?->getFirstMedia('logos');
    }

    public static function setMediaForSetting(string $settingKey, $file): Media
    {
        $mediaModel = self::firstOrCreate(['setting_key' => $settingKey]);

        // Supprimer l'ancien média s'il existe
        $mediaModel->clearMediaCollection('logos');

        return $mediaModel->addMedia($file)
            ->toMediaCollection('logos');
    }
}
