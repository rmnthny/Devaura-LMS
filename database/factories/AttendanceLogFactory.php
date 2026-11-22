<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\UserAccount;
use App\Models\WalkInVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceLog> */
class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        return [
            // Exactly ONE of account_id/visitor_id must be set (MySQL CHECK constraint).
            'account_id' => UserAccount::factory()->withMemberProfile('student'),
            'visitor_id' => null,
            'user_role' => 'student',
            'time_in_at' => now()->subHour(),
            'time_out_at' => null,
        ];
    }

    public function faculty(): static
    {
        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->withMemberProfile('faculty'),
            'visitor_id' => null,
            'user_role' => 'faculty',
        ]);
    }

    public function personnel(string $role = 'staff'): static
    {
        if (! in_array($role, ['superadmin', 'admin', 'staff', 'maintenance'], true)) {
            throw new \InvalidArgumentException('Invalid personnel role.');
        }

        return $this->state(fn (): array => [
            'account_id' => UserAccount::factory()->withPersonnelProfile($role),
            'visitor_id' => null,
            'user_role' => $role,
        ]);
    }

    public function walkIn(): static
    {
        return $this->state(fn (): array => [
            'account_id' => null,
            'visitor_id' => WalkInVisitor::factory(),
            'user_role' => 'visitor',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'time_in_at' => now()->subHours(2),
            'time_out_at' => now()->subHour(),
        ]);
    }
}
