<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'browse_admin_create',
        'browse_admin_read',
        'browse_admin_update',
        'browse_admin_delete',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permissioName) {
            Permission::firstOrCreate([
                'name' => $permissioName,
                'guard_name' => 'api',
            ]);
        }
    }
}
