<?php

// =========================================================================
// Configuration MinIO pour PostgreSQL
// C'est la source de vérité unique pour la connexion MinIO/S3.
// =========================================================================

// Configuration des paramètres MinIO selon l'environnement
if (config('app.env') === 'production') {
    $MINIO_ACCESS_KEY_ID = 'b3a3b78b737fbbf4dba5729f7a414683';
    $MINIO_SECRET_ACCESS_KEY = '130b1697086c3fbcc23fe66d1a892d9bb8802bd6fc979cd110cbe97037dbacbe';
    $MINIO_URL = 'https://viwuuxoibjeqqbzqkhix.storage.supabase.co/storage/v1/s3/resurexbucket';
    $MINIO_ENDPOINT = 'https://viwuuxoibjeqqbzqkhix.storage.supabase.co/storage/v1/s3';
    $MINIO_BUCKET = 'resurexbucket';
    $MINIO_DEFAULT_REGION = 'us-east-1';
    $SCHEME = 'https';
} else {
    // Configuration locale par défaut
    $MINIO_ACCESS_KEY_ID = 'b3a3b78b737fbbf4dba5729f7a414683';
    $MINIO_SECRET_ACCESS_KEY = '130b1697086c3fbcc23fe66d1a892d9bb8802bd6fc979cd110cbe97037dbacbe';
    $MINIO_URL = 'https://viwuuxoibjeqqbzqkhix.storage.supabase.co/storage/v1/s3/resurexbucket';
    $MINIO_ENDPOINT = 'https://viwuuxoibjeqqbzqkhix.storage.supabase.co/storage/v1/s3';
    $MINIO_BUCKET = 'resurexbucket';
    $MINIO_DEFAULT_REGION = 'us-east-1';
    $SCHEME = 'http';
}

// Configuration de base MinIO avec fonctionnalités avancées
$minioBaseConfig = [
    'driver' => 's3',
    'key' => env('MINIO_ACCESS_KEY_ID', $MINIO_ACCESS_KEY_ID),
    'secret' => env('MINIO_SECRET_ACCESS_KEY', $MINIO_SECRET_ACCESS_KEY),
    'region' => env('MINIO_DEFAULT_REGION', $MINIO_DEFAULT_REGION),
    'bucket' => env('MINIO_BUCKET', $MINIO_BUCKET),
    'url' => env('MINIO_URL', $MINIO_URL),
    'endpoint' => env('MINIO_ENDPOINT', $MINIO_ENDPOINT),
    'use_path_style_endpoint' => true,
    'scheme' => $SCHEME,

    // --- Options de performance et sécurité ---
    'options' => [
        'multipart' => true,
        'verify' => false,  // Critique pour les certificats auto-signés
    ],

    // Force la levée d'une exception en cas d'erreur, ce qui est mieux pour le débogage.
    'throw' => true,
];

// Configuration MinIO avec cache et upload multipart pour les gros fichiers
$minioSettingDisk = array_merge($minioBaseConfig, [
    'MultipartUpload' => [
        'enabled' => true,
        'part_size' => 50 * 1024 * 1024, // 50MB
    ],
    'cache' => [
        'store' => 'redis',
        'expire' => 600,
        'prefix' => 'minio-cache',
    ],
]);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => config('app.url').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // =========================================================================
        // Disques MinIO pointant tous vers la même configuration S3
        // Optimisés pour PostgreSQL avec cache Redis et upload multipart.
        // =========================================================================

        // Configuration S3 standard (compatibilité)
        's3' => $minioBaseConfig,

        // Disque principal MinIO avec fonctionnalités avancées
        'minio' => $minioSettingDisk,

        // Alias de disques pour une meilleure organisation du code.
        // Ils utilisent TOUS la même connexion MinIO avec optimisations.
        'profils' => $minioBaseConfig,
        'documents' => $minioBaseConfig,
        'apps' => $minioBaseConfig,
        'chat_attachments' => $minioBaseConfig,
        'temporaryDirectory' => $minioBaseConfig,
        'temp_uploads' => $minioBaseConfig,

        // Disque spécifique pour les médias des paramètres d'application
        'app_settings_media' => array_merge($minioBaseConfig, [
            'url' => env('MINIO_URL', $MINIO_URL).'/app-settings',
        ]),

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
