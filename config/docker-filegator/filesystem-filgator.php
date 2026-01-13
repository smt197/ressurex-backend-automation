<?php

// =========================================================================
// Configuration SFTP pour FileGator
// C'est la source de vérité unique pour la connexion SFTP.
// =========================================================================
$fileGatorSftpConfig = [
    'driver' => 'sftp',
    'host' => env('SFTP_HOST'),
    'username' => env('SFTP_USERNAME'),
    'password' => env('SFTP_PASSWORD'), // Utilisé si 'privateKey' n'est pas fourni
    'root' => env('SFTP_ROOT', 'files'), // La racine du système de fichiers pour l'utilisateur SFTP

    // --- Paramètres de connexion ---
    'port' => (int) env('SFTP_PORT', 2222),
    'timeout' => (int) env('SFTP_TIMEOUT', 30),

    // --- Paramètres de visibilité et de permissions ---
    'visibility' => 'public', // `private` = 0600, `public` = 0644 par défaut. [2]
    'directory_visibility' => 'public', // `private` = 0700, `public` = 0755 par défaut. [2]

    // --- Définition des permissions pour la visibilité publique ---
    'permissions' => [
        'file' => [
            'public' => 0777,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0777,
            'private' => 0700,
        ],
    ],

    // Force la levée d'une exception en cas d'erreur, ce qui est mieux pour le débogage.
    'throw' => true,
    'url' => env('MEDIA_SERVER_BASE_URL', 'http://localhost/storage'),

];

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
        // Disques SFTP pointant tous vers la même configuration FileGator
        // Votre commande `storage:filegator` configure ces disques dynamiquement,
        // mais il est bon de les avoir ici pour que le reste de l'application
        // les reconnaisse.
        // =========================================================================

        // Disque principal SFTP, utilisé comme 'default' dans votre .env
        'filegator_sftp' => $fileGatorSftpConfig,

        // Alias de disques pour une meilleure organisation du code.
        // Ils utilisent TOUS la même connexion et les mêmes permissions.
        'profils' => $fileGatorSftpConfig,
        'documents' => $fileGatorSftpConfig,
        'apps' => $fileGatorSftpConfig,
        'chat_attachments' => $fileGatorSftpConfig,
        'temporaryDirectory' => $fileGatorSftpConfig,
        'temp_uploads' => $fileGatorSftpConfig,

        // Disque spécifique pour les médias des paramètres d'application
        'app_settings_media' => array_merge($fileGatorSftpConfig, [
            'root' => env('SFTP_ROOT', 'files').'/apps/logos',
            'url' => env('MEDIA_SERVER_BASE_URL', 'http://localhost/storage').'/app-settings',
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
