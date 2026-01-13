<?php

namespace Database\Seeders;

use App\Models\Permissions;
use App\Models\Roles;
use Illuminate\Database\Seeder;

class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        // Assigner toutes les permissions au rôle admin
        $adminRole = Roles::where('name', 'admin')->firstOrFail();
        $permissions = Permissions::all();
        $adminRole->permissions()->sync($permissions->pluck('id')->all());

        // Assigner des permissions spécifiques au rôle user
        $userRole = Roles::where('name', 'user')->firstOrFail();
        $userPermissions = Permissions::whereIn('name', [
            'browse_admin_read',
        ])->get();
        $userRole->permissions()->sync($userPermissions->pluck('id')->all());
    }
}
