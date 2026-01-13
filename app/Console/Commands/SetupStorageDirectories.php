<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class SetupStorageDirectories extends Command
{
    /**
     * Les répertoires à créer dans le stockage Laravel
     */
    private const DIRECTORIES = [
        'profils',
        'temporaryDirectory',
        'temporaryDirectory/profils',
        'apps',
        'apps/logos',
        'chats',
        'documents',
        'chat_attachments',
    ];

    /**
     * Retourne les répertoires racines (sans les sous-répertoires)
     * Utilisé par AppServiceProvider pour configurer les disques dynamiquement
     */
    public static function getRootDirectories(): array
    {
        return array_values(array_unique(array_map(
            fn ($dir) => explode('/', $dir)[0],
            self::DIRECTORIES
        )));
    }

    /**
     * Nom et signature de la commande
     */
    protected $signature = 'storage:setup';

    /**
     * Description de la commande
     */
    protected $description = 'Crée les répertoires nécessaires dans le système de stockage Laravel';

    /**
     * Exécution de la commande
     */
    public function handle(): int
    {
        $disk = Config::get('filesystems.default');

        if (empty($disk)) {
            $this->error('Aucun disque par défaut configuré');

            return Command::FAILURE;
        }

        $this->info("Utilisation du disque: {$disk}");

        // Test de la connexion avant de continuer
        if (! $this->testConnection($disk)) {
            return Command::FAILURE;
        }

        // Étape 1 : Suppression de tous les répertoires existants
        $this->info('Suppression des répertoires existants...');
        foreach (self::DIRECTORIES as $directory) {
            $this->deleteDirectory($disk, $directory);
        }

        // Étape 2 : Création de tous les répertoires
        $this->info('Création des nouveaux répertoires...');
        foreach (self::DIRECTORIES as $directory) {
            $this->createDirectory($disk, $directory);
        }

        $this->info('Configuration du stockage terminée !');

        return Command::SUCCESS;
    }

    /**
     * Test de la connexion au stockage
     */
    private function testConnection(string $disk): bool
    {
        try {
            Storage::disk($disk)->files();
            $this->info("Connexion au disque [{$disk}] réussie.");

            return true;
        } catch (\Exception $e) {
            $this->error("Échec de la connexion au disque [{$disk}]: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Supprime un répertoire sur le disque spécifié
     */
    private function deleteDirectory(string $disk, string $directory): void
    {
        if (Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
            $this->info("✓ Répertoire {$directory} supprimé");
        }
    }

    /**
     * Crée un répertoire sur le disque spécifié
     */
    private function createDirectory(string $disk, string $directory): void
    {
        if (Helpers::ensureDirectoryExists($disk, $directory)) {
            $this->info("✓ Répertoire {$directory} créé");
        } else {
            $this->error("✗ Échec création du répertoire {$directory}");
        }
    }
}
