<?php

namespace Database\Factories;

use App\Models\LibraryMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryMember>
 */
class LibraryMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => User::factory(),
            'member_role' => fake()->randomElement([LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY]),
            'reference_number' => fake()->unique()->numerify('1368########'),
            'member_code' => 'UBNHS-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
        ];
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'member_role' => LibraryMember::ROLE_STUDENT,
            'reference_number' => fake()->unique()->numerify('1368########'),
        ]);
    }

    public function faculty(): static
    {
        return $this->state(fn () => [
            'member_role' => LibraryMember::ROLE_FACULTY,
            'reference_number' => fake()->unique()->numerify('#######'),
        ]);
    }
}
