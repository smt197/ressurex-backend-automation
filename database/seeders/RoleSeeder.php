<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    private const ROLES = [
        'user',
        'admin',
        'manager',
        'system',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::ROLES as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
            ]);
        }
    }
}
