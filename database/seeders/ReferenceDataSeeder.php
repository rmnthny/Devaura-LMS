<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('department')) {
            return;
        }

        foreach ([
            'English',
            'Mathematics',
            'Science',
            'Filipino',
            'Araling Panlipunan',
            'MAPEH',
            'TLE',
            'EsP',
            'ICT',
        ] as $name) {
            Department::firstOrCreate(['department_name' => $name]);
        }
    }
}
