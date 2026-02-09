<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ModuleManager;

echo "Registering 'outils' module in the current database...\n";

$data = [
    'module_name' => 'outils',
    'display_name' => 'Outils',
    'display_name_singular' => 'Outil',
    'resource_type' => 'outils',
    'identifier_field' => 'id',
    'identifier_type' => 'number',
    'requires_auth' => true,
    'route_path' => 'outils',
    'fields' => [
        ['name' => 'name', 'type' => 'string', 'required' => true, 'label' => 'Nom'],
        ['name' => 'description', 'type' => 'text', 'required' => false, 'label' => 'Description']
    ],
    'enabled' => true,
    'dev_mode' => false,
    'roles' => ['user'],
    'translations' => [
        'en' => [
            'title' => 'Outils',
            'subTitle' => 'Manage your Outils',
            'add' => 'Add Outil',
            'edit' => 'Edit Outil',
            'delete' => 'Delete Outil',
        ],
        'fr' => [
            'title' => 'Outils',
            'subTitle' => 'Gérez vos Outils',
            'add' => 'Ajouter Outil',
            'edit' => 'Modifier Outil',
            'delete' => 'Supprimer Outil',
        ]
    ],
    'actions' => [
        'create' => ['enabled' => true],
        'edit' => ['enabled' => true],
        'delete' => ['enabled' => true],
        'deleteAll' => ['enabled' => false],
        'show' => ['enabled' => true],
        'search' => ['enabled' => true],
        'export' => ['enabled' => false]
    ]
];

$module = ModuleManager::updateOrCreate(
    ['module_name' => 'outils'],
    $data
);

if ($module) {
    echo "SUCCESS: 'outils' module registered (ID: {$module->id}).\n";
} else {
    echo "FAILURE: Could not register module.\n";
}
