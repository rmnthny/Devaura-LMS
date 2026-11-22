<?php

namespace Database\Factories;

use App\Models\SchedulePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchedulePeriod>
 * Normally use the seeded Morning/Afternoon/Whole Day values.
 * This factory generates test-only labels, not production shift definitions.
 */
class SchedulePeriodFactory extends Factory
{
    protected $model = SchedulePeriod::class;

    public function definition(): array
    {
        return ['period_name' => 'Test-'.fake()->unique()->bothify('????-####')];
    }
}
