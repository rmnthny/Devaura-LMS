<?php

namespace Database\Factories;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Publisher> */
class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition(): array
    {
        return [
            'publisher_name' => fake()->unique()->company(),
            'publisher_type' => 'Organization',
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (): array => [
            'publisher_name' => fake()->unique()->name(),
            'publisher_type' => 'Individual',
        ]);
    }
}
