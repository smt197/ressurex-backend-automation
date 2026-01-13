<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModuleManager;

class ModuleManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder contains all module configurations.
     * Generated automatically from database.
     */
    public function run(): void
    {
        $modules = [
            
  [
    'module_name' => 'registres',
    'slug' => 'registres',
    'display_name' => 'Registres',
    'display_name_singular' => 'Registre',
    'resource_type' => 'registres',
    'identifier_field' => 'id',
    'identifier_type' => 'number',
    'requires_auth' => true,
    'route_path' => 'registres',
    'fields' => 
    [
            
      [
        'name' => 'name',
        'type' => 'string',
        'required' => true,
      ],
            
      [
        'name' => 'description',
        'type' => 'textarea',
        'required' => false,
      ],
    ],
    'enabled' => true,
    'dev_mode' => false,
    'translations' => 
    [
      'en' => 
      [
        'Registre' => 'Registre',
        'Registres' => 'Registres',
      ],
      'fr' => 
      [
        'Registre' => 'Registre',
        'Registres' => 'Registres',
      ],
      'pt' => 
      [
        'Registre' => 'Registre',
        'Registres' => 'Registres',
      ],
    ],
    'actions' => 
    [
      'edit' => 
      [
        'enabled' => true,
      ],
      'show' => 
      [
        'enabled' => true,
      ],
      'create' => 
      [
        'enabled' => true,
      ],
      'delete' => 
      [
        'enabled' => true,
      ],
      'export' => 
      [
        'enabled' => false,
      ],
      'search' => 
      [
        'enabled' => true,
      ],
      'deleteAll' => 
      [
        'enabled' => false,
      ],
    ],
  ],
];

        foreach ($modules as $moduleData) {
            ModuleManager::updateOrCreate(
                ['slug' => $moduleData['slug']], // Find by slug
                $moduleData // Update or create with this data
            );
        }

        $this->command->info('ModuleManagerSeeder completed successfully.');
    }
}
