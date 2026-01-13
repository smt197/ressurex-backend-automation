<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $availableLanguages = [
            ['code' => 'en', 'name' => 'English', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/GB.svg'],
            ['code' => 'fr', 'name' => 'Français', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/FR.svg'],
            ['code' => 'pt', 'name' => 'Portuguese', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/PT.svg'],
        ];

        // Insert languages into the database
        DB::table('languages')->insert($availableLanguages);
    }
}
