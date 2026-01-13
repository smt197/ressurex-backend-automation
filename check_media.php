<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$media = Spatie\MediaLibrary\MediaCollections\Models\Media::find(201);

if ($media) {
    echo "Media ID: " . $media->id . PHP_EOL;
    echo "Model: " . $media->model_type . " #" . $media->model_id . PHP_EOL;
    echo "Disk: " . $media->disk . PHP_EOL;
    echo "File name: " . $media->file_name . PHP_EOL;
    echo "Path: " . $media->getPath() . PHP_EOL;
    echo "URL: " . $media->getUrl() . PHP_EOL;
    echo "File exists: " . (file_exists($media->getPath()) ? 'YES' : 'NO') . PHP_EOL;

    // Check disk config
    echo "\nDisk config root: " . config('filesystems.disks.tasks.root') . PHP_EOL;
} else {
    echo "Media 201 not found" . PHP_EOL;
}
