<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\LibraryPersonnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryPersonnel>
 */
class LibraryPersonnelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'account_id' => User::factory(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
        ];
    }
}
