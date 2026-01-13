<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class Helpers
{
    public static function ensureDirectoryExists($disk, $directory)
    {
        try {
            $storage = Storage::disk($disk);

            // Check if the directory exists
            if (! $storage->exists($directory)) {
                // Create an empty file in the directory to ensure it exists
                // (MinIO doesn't support creating empty directories directly)
                $storage->put($directory.'/.gitkeep', '');
                \Log::info("Directory created: {$directory}");

                return true;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to create directory {$directory} on disk {$disk}: ".$e->getMessage());

            // Don't throw the exception, just log it to prevent app from crashing
            return false;
        }
    }
}
