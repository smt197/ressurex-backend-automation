<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/data/countries.json'));  // Utilisation de `database_path()` pour un chemin fiable
        $data = json_decode($json);

        foreach ($data as $value) {
            Country::updateOrCreate(
                ['country_code' => $value->country_code], // Assurez-vous que le code pays est unique
                [
                    'country_name' => $value->country_name,
                    'image_url' => $value->image_url,
                    'dial_code' => $value->dial_code,
                ]
            );
        }
    }
}
