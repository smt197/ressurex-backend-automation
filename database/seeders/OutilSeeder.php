<?php

namespace Database\Seeders;

use App\Models\Outil;
use Illuminate\Database\Seeder;

class OutilSeeder extends Seeder
{
    public function run(): void
    {
        Outil::factory()->count(10)->create();
    }
}
