<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OutilMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create default category (Dashboard)
        $category = Category::where('name', 'Dashboard')->first();

        if (!$category) {
            $category = Category::where('name', 'Dashboard')->first();
        }

        // Check if menu already exists
        $existingMenu = Menu::where('name', 'outils')
            ->orWhere('slug', 'outils')
            ->first();

        if ($existingMenu) {
            // Update existing menu
            $existingMenu->update([
                'icon' => 'extension',
                'color' => '#10b981',
                'route' => '/index/outils',
                'roles' => ["user"],
                'category_id' => $category?->id,
                'disable' => 1,
            ]);

            $this->command->info('Menu "outils" updated successfully.');
        } else {
            // Create new menu
            Menu::create([
                'name' => 'outils',
                'icon' => 'extension',
                'color' => '#10b981',
                'route' => '/index/outils',
                'roles' => ["user"],
                'slug' => 'outils',
                'category_id' => $category?->id,
                'disable' => 1,
            ]);

            $this->command->info('Menu "outils" created successfully.');
        }
    }
}
