<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('minio', function ($blueprint) {
            // Configuration de production
            $blueprint->add('prod_access_key_id', '5fRjufiRewaRJER9PVlC');
            $blueprint->add('prod_secret_access_key', 'jD5I08Lp7p77etdvfYd4chLa42Wnx5gl5G2udlxO');
            $blueprint->add('prod_endpoint', 'https://minio.courdejusticeuemoa.org');
            $blueprint->add('prod_bucket', 'resurexbucket');
            $blueprint->add('prod_url', 'https://minio.courdejusticeuemoa.org/resurexbucket');

            // Configuration locale
            $blueprint->add('local_access_key_id', env('MINIO_ACCESS_KEY_ID'));
            $blueprint->add('local_secret_access_key', env('MINIO_SECRET_ACCESS_KEY'));
            $blueprint->add('local_endpoint', env('MINIO_ENDPOINT'));
            $blueprint->add('local_bucket', env('MINIO_BUCKET'));
            $blueprint->add('local_url', env('MINIO_URL'));

            // Configuration commune
            $blueprint->add('default_region', 'us-east-1');
            $blueprint->add('use_path_style_endpoint', true);
            $blueprint->add('filesystem_disk', env('FILESYSTEM_DISK'));
        });
    }

    // Optionnel : méthode down si nécessaire
    public function down(): void
    {
        $this->migrator->inGroup('minio', function ($blueprint) {
            // Suppression des configurations de production
            $blueprint->delete('prod_access_key_id');
            $blueprint->delete('prod_secret_access_key');
            $blueprint->delete('prod_endpoint');
            $blueprint->delete('prod_bucket');
            $blueprint->delete('prod_url');

            // Suppression des configurations locales
            $blueprint->delete('local_access_key_id');
            $blueprint->delete('local_secret_access_key');
            $blueprint->delete('local_endpoint');
            $blueprint->delete('local_bucket');
            $blueprint->delete('local_url');

            // Suppression des configurations communes
            $blueprint->delete('default_region');
            $blueprint->delete('use_path_style_endpoint');
            $blueprint->delete('filesystem_disk');
        });
    }
};
