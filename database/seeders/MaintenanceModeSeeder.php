<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vide la table pour s'assurer qu'il n'y a qu'un seul enregistrement.
        DB::table('maintenance_mode')->truncate();

        // Insère l'enregistrement par défaut.
        DB::table('maintenance_mode')->insert([
            'is_active'    => false,
            'message'      => 'Le site est actuellement en maintenance. Merci de revenir plus tard.',
            'activated_at' => null,
            'activated_by' => null,
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);
    }
}
