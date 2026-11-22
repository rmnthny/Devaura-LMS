<?php

namespace Database\Factories;

use App\Models\WalkInVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WalkInVisitor> */
class WalkInVisitorFactory extends Factory
{
    protected $model = WalkInVisitor::class;

    public function definition(): array
    {
        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
