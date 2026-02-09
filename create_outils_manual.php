<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ModuleManagerService;
use Illuminate\Support\Facades\Log;

echo "Creating Outils module (Manual Script)...\n";

$data = [
    'moduleName' => 'outils',
    'displayName' => 'Outils',
    'displayNameSingular' => 'Outil',
    'fields' => [
        ['name' => 'name', 'type' => 'string', 'required' => true, 'label' => 'Nom'],
        ['name' => 'description', 'type' => 'text', 'required' => false, 'label' => 'Description']
    ],
    'gitConfig' => [
        'createBranch' => true,
        'repositorySlug' => 'ressurex-backend-automation-smt197',
        'sourceBranch' => 'mcp'
    ]
];

try {
    $service = app(ModuleManagerService::class);
    $result = $service->createModule($data);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "SUCCESS: Module 'outils' created and pushed.\n";
    } else {
        echo "FAILURE: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    Log::error("Manual outils module creation failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
