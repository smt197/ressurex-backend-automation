<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Spatie\TranslationLoader\LanguageLine;

class TranslationLoaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chemin vers le dossier lang
        $langPath = database_path('seeders/lang');

        // Récupérer tous les fichiers JSON dans le dossier lang
        $jsonFiles = File::glob("{$langPath}/*.json");

        foreach ($jsonFiles as $file) {
            // Charger le contenu du fichier JSON
            $json = File::get($file);
            $data = json_decode($json);

            // Vérifier si le JSON est valide
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error('Erreur de décodage JSON dans le fichier: '.basename($file));

                continue;
            }

            // Parcourir les données JSON pour créer les entrées
            foreach ($data as $value) {
                try {
                    LanguageLine::create([
                        'group' => $value->group,
                        'key' => $value->key,
                        'text' => [
                            'en' => $value->text->en ?? null,
                            'fr' => $value->text->fr ?? null,
                            'pt' => $value->text->pt ?? null,
                        ],
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Erreur lors de l'insertion de la traduction: ".$e->getMessage());
                    $this->command->error('Fichier: '.basename($file).', Group: '.($value->group ?? 'null').', Key: '.($value->key ?? 'null'));
                }
            }

            $this->command->info('Traductions du fichier '.basename($file).' chargées avec succès.');
        }
    }
}
