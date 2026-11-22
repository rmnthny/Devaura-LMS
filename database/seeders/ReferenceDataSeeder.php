<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Names preserved from the team's existing ReferenceDataSeeder.
        $departments = [
            'English',
            'Mathematics',
            'Science',
            'Filipino',
            'Araling Panlipunan',
            'MAPEH',
            'TLE',
            'EsP',
            'ICT',
        ];

        foreach ($departments as $departmentName) {
            DB::table('department')->insertOrIgnore([
                'department_name' => $departmentName,
            ]);
        }

        // Fixed IDs: schedule_day.day_id is a TINYINT UNSIGNED PK without AUTO_INCREMENT.
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        foreach ($days as $dayId => $dayName) {
            DB::table('schedule_day')->updateOrInsert(
                ['day_id' => $dayId],
                ['day_name' => $dayName],
            );
        }

        // PROVISIONAL labels: confirm with library client before configuring schedules.
        // No start/end hours exist in the current ERD, only period_name.
        foreach (['Morning', 'Afternoon', 'Whole Day'] as $periodName) {
            DB::table('schedule_period')->insertOrIgnore([
                'period_name' => $periodName,
            ]);
        }

        // No genres/authors/publishers are seeded: the team has not approved a fixed catalog.
    }
}
