<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\LibraryPersonnel;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LibraryPersonnel> */
class LibraryPersonnelFactory extends Factory
{
    protected $model = LibraryPersonnel::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'account_id' => UserAccount::factory()->staff(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->admin(),
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->superadmin(),
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->maintenance(),
            'department_id' => null,
        ]);
    }

    public function forAccount(UserAccount $account): static
    {
        return $this->state(fn (): array => ['account_id' => $account->account_id]);
    }
}
