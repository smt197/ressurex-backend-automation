<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessUserFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $collection;

    public $temporaryDirectoryDisk;

    public function __construct($user, $collection, $temporaryDirectoryDisk)
    {
        $this->user = $user;
        $this->collection = $collection;
        $this->temporaryDirectoryDisk = $temporaryDirectoryDisk;
    }

    public function handle()
    {
        $tempDirectory = $this->collection.'/'.$this->user->id;

        try {
            $disk = config('filesystems.default'); // Utilisez le disque par défaut
            $finalPath = "{$this->temporaryDirectoryDisk}/{$tempDirectory}";
            $storage = Storage::disk($disk);
            $temporaryFiles = $storage->files($finalPath);

            foreach ($temporaryFiles as $filePath) {
                $this->processFile($filePath, $disk);
            }

            // Optionnel : Supprimer le répertoire temporaire après traitement
            $storage->deleteDirectory($finalPath);

            \Log::info("ProcessUserFiles completed for user: {$this->user->id}");
        } catch (\Exception $e) {
            \Log::error("Error in ProcessUserFiles for user {$this->user->id}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function processFile(string $filePath, $disk)
    {
        try {
            $filename = basename($filePath);

            // Solution optimale pour Spatie Media Library
            $media = $this->user->addMediaFromDisk($filePath, $this->temporaryDirectoryDisk)
                ->usingName(pathinfo($filename, PATHINFO_FILENAME))
                ->usingFileName($filename)
                ->toMediaCollection($this->collection, $disk);

            if ($media && $this->collection === 'profils') {
                // Generate URL with 7-day expiration
                $this->user->photo = $media->getTemporaryUrl(now()->addDays(7));
                $this->user->save();
            }

            return $media;
        } catch (\Exception $e) {
            \Log::error("Error processing file {$filePath}: ".$e->getMessage());
            throw $e;
        }
    }
}
