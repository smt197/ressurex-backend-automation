<?php

namespace Database\Seeders;

// Assurez-vous que ce modèle et sa table existent
use Illuminate\Database\Seeder;

// Inutile ici si vous ne l'utilisez pas directement

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seeders de base (Rôles, Permissions, Pays, Traductions)
        $this->call([
            TranslationLoaderSeeder::class, // Si vous avez ce seeder
            RoleSeeder::class,
            PermissionSeeder::class,
            PermissionRoleTableSeeder::class,
            CountrySeeder::class,
            CategorySeeder::class, // AVANT MenuSeeder
            UserSeeder::class,
            MenuSeeder::class,
            ChatSeeder::class,
            MaintenanceModeSeeder::class,
            ModuleManagerSeeder::class,]);
        $this->command->info('DatabaseSeeder a terminé avec succès.');
    }
}
