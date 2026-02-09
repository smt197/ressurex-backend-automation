<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModuleManager;

class ModuleManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = array (
  0 => 
  array (
    'module_name' => 'tasks',
    'slug' => 'tasks',
    'display_name' => 'Tâches',
    'display_name_singular' => 'Tâche',
    'resource_type' => 'tasks',
    'identifier_field' => 'id',
    'identifier_type' => 'number',
    'requires_auth' => true,
    'roles' => 
    array (
      0 => 'user',
    ),
    'route_path' => 'tasks',
    'github_repository_slug' => NULL,
    'github_branch' => NULL,
    'github_commit_sha' => NULL,
    'github_pushed_at' => NULL,
    'fields' => 
    array (
      0 => 
      array (
        'name' => 'title',
        'type' => 'string',
        'label' => 'Titre',
        'required' => true,
      ),
      1 => 
      array (
        'name' => 'description',
        'type' => 'text',
        'label' => 'Description',
        'required' => false,
      ),
      2 => 
      array (
        'name' => 'status',
        'type' => 'string',
        'label' => 'Statut',
        'required' => true,
      ),
    ),
    'enabled' => true,
    'dev_mode' => false,
    'translations' => 
    array (
      'en' => 
      array (
        'add' => 'Add Tâche',
        'edit' => 'Edit Tâche',
        'title' => 'Tâches',
        'delete' => 'Delete Tâche',
        'subTitle' => 'Manage your Tâches',
      ),
      'fr' => 
      array (
        'add' => 'Ajouter Tâche',
        'edit' => 'Modifier Tâche',
        'title' => 'Tâches',
        'delete' => 'Supprimer Tâche',
        'subTitle' => 'Gérez vos Tâches',
      ),
    ),
    'actions' => 
    array (
      'edit' => 
      array (
        'enabled' => true,
      ),
      'show' => 
      array (
        'enabled' => true,
      ),
      'create' => 
      array (
        'enabled' => true,
      ),
      'delete' => 
      array (
        'enabled' => true,
      ),
      'export' => 
      array (
        'enabled' => false,
      ),
      'search' => 
      array (
        'enabled' => true,
      ),
      'deleteAll' => 
      array (
        'enabled' => false,
      ),
    ),
  ),
  1 => 
  array (
    'module_name' => 'outils',
    'slug' => 'outils',
    'display_name' => 'Outils',
    'display_name_singular' => 'Outil',
    'resource_type' => 'outils',
    'identifier_field' => 'id',
    'identifier_type' => 'number',
    'requires_auth' => true,
    'roles' => 
    array (
      0 => 'user',
    ),
    'route_path' => 'outils',
    'github_repository_slug' => NULL,
    'github_branch' => NULL,
    'github_commit_sha' => NULL,
    'github_pushed_at' => NULL,
    'fields' => 
    array (
      0 => 
      array (
        'name' => 'name',
        'type' => 'string',
        'label' => 'Nom',
        'required' => true,
      ),
      1 => 
      array (
        'name' => 'description',
        'type' => 'text',
        'label' => 'Description',
        'required' => false,
      ),
    ),
    'enabled' => true,
    'dev_mode' => false,
    'translations' => 
    array (
      'en' => 
      array (
        'add' => 'Add Outil',
        'edit' => 'Edit Outil',
        'title' => 'Outils',
        'delete' => 'Delete Outil',
        'subTitle' => 'Manage your Outils',
      ),
      'fr' => 
      array (
        'add' => 'Ajouter Outil',
        'edit' => 'Modifier Outil',
        'title' => 'Outils',
        'delete' => 'Supprimer Outil',
        'subTitle' => 'Gérez vos Outils',
      ),
    ),
    'actions' => 
    array (
      'edit' => 
      array (
        'enabled' => true,
      ),
      'show' => 
      array (
        'enabled' => true,
      ),
      'create' => 
      array (
        'enabled' => true,
      ),
      'delete' => 
      array (
        'enabled' => true,
      ),
      'export' => 
      array (
        'enabled' => false,
      ),
      'search' => 
      array (
        'enabled' => true,
      ),
      'deleteAll' => 
      array (
        'enabled' => false,
      ),
    ),
  ),
);

        foreach ($modules as $module) {
            ModuleManager::updateOrCreate(
                ['module_name' => $module['module_name']],
                $module
            );
        }
    }
}