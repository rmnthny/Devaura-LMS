<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rbac::PERMISSIONS as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Keep the seeded grants in sync with the role matrix used by the routes.
        foreach (Rbac::rolePermissions() as $name => $permissions) {
            Role::findOrCreate($name, $guard)->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
