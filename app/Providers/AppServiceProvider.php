<?php

namespace App\Providers;

use App\Console\Commands\SetupStorageDirectories;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Configuration de visibilité et URL pour chaque disque
     * Par défaut, tous les disques ont visibility=private et url=false
     * Vous pouvez surcharger pour certains disques spécifiques
     */
    private const DISK_CONFIG_OVERRIDES = [    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Configurer les disques de stockage AVANT le boot
        // pour que Spatie Media Library puisse les utiliser
        $this->configureStorageDisks();

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Configure les disques de stockage dynamiquement
     * Les répertoires sont récupérés depuis SetupStorageDirectories
     * Les modules sont détectés automatiquement depuis database/seeders/lang/
     */
    private function configureStorageDisks(): void
    {
        // Récupérer les disques de base depuis SetupStorageDirectories
        $directories = SetupStorageDirectories::getRootDirectories();

        // Ajouter les disques des modules dynamiquement
        $modulesPath = database_path('seeders/lang');
        if (File::exists($modulesPath)) {
            $moduleFiles = File::files($modulesPath);
            foreach ($moduleFiles as $file) {
                if ($file->getExtension() === 'json') {
                    $moduleName = $file->getFilenameWithoutExtension();
                    // Ajouter le module seulement s'il n'existe pas déjà dans les directories
                    if (!in_array($moduleName, $directories)) {
                        array_push($directories, $moduleName);
                    }
                }
            }
        }

        // Configurer tous les disques (base + modules)
        foreach ($directories as $diskName) {
            // Par défaut: modules = public dans storage/app/public, système = private dans storage/app
            $isSystemDisk = in_array($diskName, ['profils', 'temporaryDirectory', 'apps', 'chats', 'documents', 'chat_attachments']);
            $defaultConfig = $isSystemDisk
                ? ['visibility' => 'private', 'url' => false]
                : ['visibility' => 'public', 'url' => true];

            $config = self::DISK_CONFIG_OVERRIDES[$diskName] ?? $defaultConfig;

            // Modules publics: storage/app/public/{diskName}
            // Disques système: storage/app/{diskName}
            $root = $isSystemDisk
                ? storage_path("app/private/{$diskName}")
                : storage_path("app/public/{$diskName}");

            // Pour les modules (non système), supprimer l'ancien répertoire storage/app/{diskName} s'il existe
            if (!$isSystemDisk) {
                $oldPath = storage_path("app/{$diskName}");
                if (File::exists($oldPath) && File::isDirectory($oldPath)) {
                    \Log::warning("Ancien répertoire détecté et supprimé: {$oldPath}. Les fichiers doivent être dans storage/app/public/{$diskName}");
                    File::deleteDirectory($oldPath);
                }
            }

            Config::set("filesystems.disks.{$diskName}", [
                'driver' => 'local',
                'root' => $root,
                'url' => $config['url'] ? rtrim(env('APP_URL'), '/')."/storage/{$diskName}" : null,
                'visibility' => $config['visibility'],
                'throw' => false,
                'report' => false,
            ]);
        }
    }
}
