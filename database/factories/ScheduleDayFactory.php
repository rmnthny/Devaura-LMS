<?php

namespace Database\Factories;

use App\Models\ScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleDay>
 * Only for isolated tests BEFORE official days are seeded.
 * Normal development should reuse ReferenceDataSeeder's Monday–Sunday rows.
 */
class ScheduleDayFactory extends Factory
{
    protected $model = ScheduleDay::class;

    public function definition(): array
    {
        $dayId = fake()->unique()->numberBetween(1, 7);
        $days = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
            4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];

        return ['day_id' => $dayId, 'day_name' => $days[$dayId]];
    }
}
