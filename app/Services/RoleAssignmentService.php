<?php

namespace App\Services;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleAssignmentService
{
    public const ASSIGNABLE = [Rbac::ADMIN, Rbac::STAFF, Rbac::MAINTENANCE, Rbac::STUDENT, Rbac::FACULTY];

    public function bootstrapSuperadmin(string $reference, ?array $firstMember = null): void
    {
        DB::transaction(function () use ($reference, $firstMember): void {
            $this->lockHeadLibrarianRole();
            if (User::withTrashed()->role(Rbac::SUPERADMIN)->exists()) {
                $this->fail('A Head Librarian already exists. Use Role assignments to transfer the role.');
            }

            $target = User::where('reference_number', $reference)->lockForUpdate()->first();
            if (! $target && $firstMember !== null && $firstMember['reference_number'] === $reference
                && $firstMember['member_role'] === LibraryMember::ROLE_FACULTY) {
                app(ImportedMember::class)->create($firstMember, app(MemberProfileService::class));
                $target = User::where('reference_number', $reference)->lockForUpdate()->first();
            }
            if (! $target || ! $target->isActive() || ! $target->hasRole(Rbac::FACULTY)
                || $target->member?->member_role !== LibraryMember::ROLE_FACULTY
                || ! $target->personnel?->department_id) {
                $this->fail('Choose a verified Faculty member with a subject department.');
            }

            $target->syncRoles([Rbac::SUPERADMIN]);
            activity('roles')->performedOn($target)->log('Appointed the first Head Librarian from the console');
        });
    }

    public function assign(User $actor, User $target, string $role): void
    {
        DB::transaction(function () use ($actor, $target, $role): void {
            $this->lockHeadLibrarianRole();
            $actor->refresh();
            $target = User::lockForUpdate()->findOrFail($target->getKey());
            if (! $actor->hasRole(Rbac::SUPERADMIN)) {
                $currentRoles = $target->getRoleNames()->all();
                abort_unless($actor->hasRole(Rbac::ADMIN)
                    && $target->member?->member_role === LibraryMember::ROLE_STUDENT
                    && (($currentRoles === [Rbac::STUDENT] && $role === Rbac::STAFF)
                        || ($currentRoles === [Rbac::STAFF] && $role === Rbac::STUDENT)), 403);
            }
            $this->assignRole($actor, $target, $role);
        });
    }

    private function assignRole(User $actor, User $target, string $role): void
    {
        $revokingStaff = $role === Rbac::STUDENT && $target->hasRole(Rbac::STAFF);
        if (! $target->member || (! $target->isActive() && ! $revokingStaff)) {
            $this->fail('Select an active, verified library member.');
        }
        if ($actor->is($target)) {
            $this->fail('You cannot change your own role. Use "Transfer Superadmin" instead.');
        }
        if ($target->hasRole(Rbac::SUPERADMIN)) {
            $this->fail('The superadmin role can only be changed through a transfer.');
        }
        if (! in_array($role, self::ASSIGNABLE, true)) {
            $this->fail('Invalid role selected.');
        }

        $memberRole = $target->member?->member_role;

        if ($role === Rbac::ADMIN && $memberRole !== LibraryMember::ROLE_FACULTY) {
            $this->fail('Only Faculty members can be assigned as Admin (Assigned Teacher).');
        }
        if ($role === Rbac::ADMIN && ! $target->personnel?->department_id) {
            $this->fail('The faculty member must set a subject department before becoming Admin.');
        }
        if ($role === Rbac::STAFF && $memberRole !== LibraryMember::ROLE_STUDENT) {
            $this->fail('Only Student members can be assigned as Staff (Student Staff).');
        }
        if (in_array($role, Rbac::MEMBER_ROLES, true) && $memberRole && strtolower($memberRole) !== $role) {
            $this->fail("This account is registered as {$memberRole}; it can only revert to that member role.");
        }

        $old = $target->getRoleNames()->first();
        $target->syncRoles([$role]);

        if (in_array($role, Rbac::PERSONNEL_ROLES, true)) {
            $this->ensurePersonnel($target);
        } elseif ($role !== Rbac::FACULTY) {
            $target->personnel?->delete();
        }

        activity('roles')->causedBy($actor)->performedOn($target)
            ->withProperties(['from' => $old, 'to' => $role])
            ->log("Changed role from {$old} to {$role}");
    }

    public function transferSuperadmin(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $this->lockHeadLibrarianRole();
            $actor->refresh();
            $target = User::lockForUpdate()->findOrFail($target->getKey());
            $this->transferRole($actor, $target);
        });
    }

    private function transferRole(User $actor, User $target): void
    {
        if (! $actor->hasRole(Rbac::SUPERADMIN)) {
            $this->fail('Only the current superadmin can transfer this privilege.');
        }
        if ($actor->is($target)) {
            $this->fail('You are already the superadmin.');
        }
        if ($target->member?->member_role !== LibraryMember::ROLE_FACULTY) {
            $this->fail('The Head Librarian privilege can only be transferred to a Faculty member.');
        }
        if (! $target->isActive()) {
            $this->fail('The new superadmin must have an active, email-verified account.');
        }
        if (! $target->personnel?->department_id) {
            $this->fail('The new Head Librarian must have a subject department.');
        }

        DB::transaction(function () use ($actor, $target) {
            $target->syncRoles([Rbac::SUPERADMIN]);
            $this->ensurePersonnel($target);

            $actor->member()->firstOrCreate([], [
                'first_name' => $actor->personnel?->first_name ?? $actor->username,
                'last_name' => $actor->personnel?->last_name ?? 'Librarian',
                'middle_initial' => $actor->personnel?->middle_initial,
            ]);
            $actor->syncRoles([Rbac::FACULTY]);

            activity('roles')->causedBy($actor)->performedOn($target)
                ->log('Transferred superadmin (Head Librarian) privilege');
        });
    }

    private function lockHeadLibrarianRole(): void
    {
        // Check the current Head Librarian before another role change can run.
        Role::where('name', Rbac::SUPERADMIN)->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
    }

    private function ensurePersonnel(User $user): void
    {
        $personnel = LibraryPersonnel::withTrashed()->firstOrNew(['account_id' => $user->getKey()]);

        if (! $personnel->exists || $personnel->trashed()) {
            $member = $user->member;
            $personnel->fill([
                'last_name' => $personnel->last_name ?? $member?->last_name ?? $user->username,
                'first_name' => $personnel->first_name ?? $member?->first_name ?? $user->username,
                'middle_initial' => $personnel->middle_initial ?? $member?->middle_initial,
            ]);
            $personnel->deleted_at = null;
        }

        if ($user->member?->member_role === LibraryMember::ROLE_STUDENT) {
            $personnel->department_id = null;
        }

        $personnel->save();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['role' => $message]);
    }
}
