<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference and authorization data only. No demo users or real accounts.
        $this->call([
            ReferenceDataSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
