<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "Cleaning up 'outils' module...\n";

// 1. Delete from database
$module = ModuleManager::where('module_name', 'outils')->first();
if ($module) {
    echo "Deleting ModuleManager record...\n";
    $module->forceDelete();
}

// 2. Delete deployments
DB::table('deployments')->where('module_slug', 'outils')->delete();

// 3. Delete files
$files = [
    app_path('Models/Outil.php'),
    app_path('Http/Controllers/OutilController.php'),
    app_path('Http/Requests/OutilRequest.php'),
    app_path('Http/Resources/OutilResource.php'),
    app_path('Http/Resources/Collections/OutilCollection.php'),
];

foreach ($files as $file) {
    if (File::exists($file)) {
        echo "Deleting $file...\n";
        File::delete($file);
    }
}

// 4. Delete migrations
$migrations = File::glob(database_path('migrations/*_create_outils_table.php'));
foreach ($migrations as $migration) {
    echo "Deleting $migration...\n";
    File::delete($migration);
}

// 5. Delete frontend directory
$frontendPath = config('app.frontend_path') . '/src/app/modules/admin/pages/outils';
if (File::isDirectory($frontendPath)) {
    echo "Deleting frontend directory $frontendPath...\n";
    File::deleteDirectory($frontendPath);
}

echo "Cleanup DONE.\n";
