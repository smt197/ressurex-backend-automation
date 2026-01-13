<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MinioSettings extends Settings
{
    // Configuration de production
    public string $prod_access_key_id = '5fRjufiRewaRJER9PVlC';

    public string $prod_secret_access_key = 'jD5I08Lp7p77etdvfYd4chLa42Wnx5gl5G2udlxO';

    public string $prod_endpoint = 'https://minio.courdejusticeuemoa.org';

    public string $prod_bucket = 'resurexbucket';

    public string $prod_url = 'https://minio.courdejusticeuemoa.org/resurexbucket';

    // Configuration locale
    public string $local_access_key_id;

    public string $local_secret_access_key;

    public string $local_endpoint;

    public string $local_bucket;

    public string $local_url;

    // Configuration commune
    public string $default_region;

    public bool $use_path_style_endpoint;

    public string $filesystem_disk;

    public static function group(): string
    {
        return 'minio';
    }

    // Méthodes pratiques pour récupérer la configuration active
    public function getActiveConfig(): array
    {
        if (config('app.env') === 'production') {
            return [
                'key' => $this->prod_access_key_id,
                'secret' => $this->prod_secret_access_key,
                'endpoint' => $this->prod_endpoint,
                'bucket' => $this->prod_bucket,
                'url' => $this->prod_url,
                'scheme' => 'https',
            ];
        }

        return [
            'key' => $this->local_access_key_id,
            'secret' => $this->local_secret_access_key,
            'endpoint' => $this->local_endpoint,
            'bucket' => $this->local_bucket,
            'url' => $this->local_url,
            'scheme' => 'http',
        ];
    }
}
