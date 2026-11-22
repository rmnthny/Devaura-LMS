<?php

namespace Database\Factories;

use App\Models\LibraryPersonnel;
use App\Models\PersonnelSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<PersonnelSchedule>
 * Run ReferenceDataSeeder first. This is a composite-PK table with no timestamps.
 */
class PersonnelScheduleFactory extends Factory
{
    protected $model = PersonnelSchedule::class;

    public function definition(): array
    {
        $morningId = DB::table('schedule_period')
            ->where('period_name', 'Morning')
            ->value('period_id');

        if ($morningId === null) {
            throw new \RuntimeException('Seed schedule_period before using PersonnelScheduleFactory.');
        }

        return [
            'personnel_id' => LibraryPersonnel::factory(),
            'day_id' => 1, // Monday is a fixed seeded schedule_day ID.
            'period_id' => $morningId,
        ];
    }
}
