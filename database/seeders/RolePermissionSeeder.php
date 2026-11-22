<?php

namespace Database\Seeders;

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

        // These permission names are a PROPOSED starting vocabulary based on sprint features.
        // Lead backend / library client must approve both names and grants before deployment.
        $permissions = [
            'accounts.view',
            'accounts.manage',
            'roles.manage',
            'members.view',
            'members.manage',
            'personnel.view',
            'personnel.manage',
            'books.view',
            'books.manage',
            'inventory.view',
            'inventory.report',
            'attendance.record',
            'attendance.view',
            'schedules.manage',
            'borrowing.process',
            'returns.process',
            'loans.view-own',
            'notifications.view-own',
            'reports.view',
            'reports.export',
            'audit.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Seven agreed role names. Visitor has no account in the current design.
        foreach ([
            'superadmin',
            'admin',
            'staff',
            'student',
            'faculty',
            'visitor',
            'maintenance',
        ] as $name) {
            Role::findOrCreate($name, $guard);
        }

        // The only assumed grant: superadmin can perform all seeded actions.
        // All other roles remain unassigned until the approved permission matrix is provided.
        // givePermissionTo() adds missing grants without revoking existing ones on rerun.
        Role::findByName('superadmin', $guard)->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
