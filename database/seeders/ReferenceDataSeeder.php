<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
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

        // Day IDs are fixed so seeding does not change existing schedules.
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

        $genres = [
            'Fiction',
            'Non-Fiction',
            'Science',
            'Mathematics',
            'History',
            'Literature',
            'Filipino',
            'Reference',
            'Biography',
            'Technology & ICT',
            'Arts & Music',
            'Health & Physical Education',
            'Values & Philosophy',
            'Social Studies',
        ];

        foreach ($genres as $genreName) {
            DB::table('genre')->insertOrIgnore([
                'genre_name' => $genreName,
            ]);
        }
    }
}
