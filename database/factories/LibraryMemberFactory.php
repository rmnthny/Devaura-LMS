<?php

namespace Database\Factories;

use App\Models\LibraryMember;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LibraryMember> */
class LibraryMemberFactory extends Factory
{
    protected $model = LibraryMember::class;

    public function definition(): array
    {
        return [
            // This creates one matching account (role assigned) and then this member profile.
            'account_id' => UserAccount::factory()->student(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_initial' => strtoupper(fake()->randomLetter()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function faculty(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->faculty(),
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->student(),
        ]);
    }

    public function forAccount(UserAccount $account): static
    {
        return $this->state(fn (): array => ['account_id' => $account->account_id]);
    }
}
