<?php

namespace Database\Factories;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserAccountFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'reference_number' => 'TEST-'.fake()->unique()->numerify('##########'),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            // Test-only password; do not use these identities as real users.
            'password_hash' => Hash::make('TestOnlyPassword!2026'),
            'account_status' => 'Active',
            'user_qr_value' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['account_status' => 'Pending']);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['account_status' => 'Inactive']);
    }

    // Requires the Spatie roles to be seeded and UserAccount to use HasRoles (guard: web).
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $account) use ($role): void {
            $account->syncRoles([$role]);
        });
    }

    public function student(): static
    {
        return $this->withRole('student')->state(fn () => ['reference_number' => fake()->unique()->numerify('############')]);
    }

    public function faculty(): static
    {
        return $this->withRole('faculty')->state(fn () => ['reference_number' => fake()->unique()->numerify('#######')]);
    }
    public function staff(): static { return $this->withRole('staff'); }
    public function admin(): static { return $this->withRole('admin'); }
    public function superadmin(): static { return $this->withRole('superadmin'); }
    public function maintenance(): static { return $this->withRole('maintenance'); }

    // Use for borrower test accounts so the account also has its required profile.
    // Do NOT use as the account_id factory inside LibraryMemberFactory: that factory creates the profile.
    public function withMemberProfile(string $role = 'student'): static
    {
        if (! in_array($role, ['student', 'faculty'], true)) {
            throw new \InvalidArgumentException('Member profile requires student or faculty role.');
        }

        return $this->{$role}()->afterCreating(function (User $account): void {
            LibraryMember::factory()->forAccount($account)->create();
        });
    }

    public function withPersonnelProfile(string $role = 'staff'): static
    {
        if (! in_array($role, ['superadmin', 'admin', 'staff', 'maintenance'], true)) {
            throw new \InvalidArgumentException('Personnel profile requires a personnel role.');
        }

        return $this->withRole($role)->afterCreating(function (User $account) use ($role): void {
            $factory = LibraryPersonnel::factory()->forAccount($account);
            if ($role === 'maintenance') {
                $factory = $factory->state(['department_id' => null]);
            }
            $factory->create();
        });
    }
}
