<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Settings\MinioSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class SetupStorageDirectoriesMinio extends Command
{
    /**
     * Les répertoires à créer
     */
    private const DIRECTORIES = [
        'profils',
        'temporaryDirectory',
        'temporaryDirectory/profils',
        'apps',
        'apps/logos',
        'chats',
        'documents',
    ];

    /**
     * Nom et signature de la commande
     *
     * @var string
     */
    protected $signature = 'storage:minio';

    /**
     * Description de la commande
     *
     * @var string
     */
    protected $description = 'Configure le stockage et crée les répertoires nécessaires';

    /**
     * Exécution de la commande
     */
    public function handle()
    {
        // Configuration MinIO
        $minioSettings = app(MinioSettings::class);
        $this->minioConfig($minioSettings);

        $disk = Config::get('filesystems.default');

        if (empty($disk)) {
            $this->error('Aucun disque par défaut configuré');

            return 1; // Code d'erreur
        }

        // Création des répertoires
        foreach (self::DIRECTORIES as $directory) {
            $currentDisk = $disk; // Default to the configured disk

            if ($directory === 'apps' || str_contains($directory, 'logos')) {
                $currentDisk = 'apps';
            }

            // Suppression du répertoire s'il existe
            // try {
            //     if (Storage::disk($currentDisk)->exists($directory)) {
            //         Storage::disk($currentDisk)->deleteDirectory($directory);
            //         $this->info("Répertoire {$directory} supprimé sur le disque {$currentDisk}");
            //     }
            // } catch (\Exception $e) {
            //     $this->error("Échec création du répertoire {$directory}");
            // }
            if (Helpers::ensureDirectoryExists($currentDisk, $directory)) {
                $this->info("Répertoire {$directory} créé sur le disque {$currentDisk}");
            } else {
                $this->error("Échec création du répertoire {$directory}");
            }
        }

        $this->info('Configuration stockage terminée!');

        return 0; // Succès
    }

    /**
     * Configuration MinIO
     */
    private function minioConfig(MinioSettings $minioSettings): void
    {
        Config::set('filesystems.default', $minioSettings->filesystem_disk);

        $disksToConfigure = ['minio', 'profils', 'temporaryDirectory', 'chat_attachments', 'documents'];

        foreach ($disksToConfigure as $disk) {
            Config::set("filesystems.disks.{$disk}", [
                'driver' => 's3',
                'key' => $minioSettings->local_access_key_id,
                'secret' => $minioSettings->local_secret_access_key,
                'region' => $minioSettings->default_region,
                'bucket' => $minioSettings->local_bucket,
                'url' => $minioSettings->local_url,
                'endpoint' => $minioSettings->local_endpoint,
                'use_path_style_endpoint' => $minioSettings->use_path_style_endpoint,
                'throw' => true,
                'scheme' => 'http',
                'options' => [
                    'multipart' => true,
                    'verify' => false,
                ],
                'MultipartUpload' => [
                    'enabled' => true,
                    'part_size' => 50 * 1024 * 1024,
                ],
                'cache' => $disk === 'minio' ? [
                    'store' => 'redis',
                    'expire' => 600,
                    'prefix' => 'minio-cache',
                ] : null,
            ]);
        }
        $this->info('Configuration MinIO mise à jour');
    }
}
