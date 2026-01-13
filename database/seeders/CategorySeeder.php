<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Dashboard',
                'slug' => Str::slug('Dashboard'),
                'order' => 1,
                'position_reference_id' => null,
                'position_type' => null,
                'icon' => 'dashboard',
                'navigation_type' => 'subheading',
            ],
            [
                'name' => 'Administration',
                'slug' => Str::slug('Administration'),
                'order' => 2,
                'position_reference_id' => 1,
                'position_type' => 'after',
                'icon' => 'settings',
                'navigation_type' => 'subheading',
            ],
            [
                'name' => 'Autorisation',
                'slug' => Str::slug('Autorisation'),
                'order' => 3,
                'position_reference_id' => 2,
                'position_type' => 'after',
                'icon' => 'security',
                'navigation_type' => 'dropdown',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']], // Condition de recherche
                $category // Données à mettre à jour/créer
            );
        }
    }
}
