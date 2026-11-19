<?php

namespace App\Services;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Superadmin-only privilege changes:
 *  - Faculty -> admin, Student -> staff, any member -> maintenance, or revert to member.
 *  - Single-superadmin transfer (previous superadmin becomes a faculty member).
 */
class RoleAssignmentService
{
    /** Roles the superadmin can grant through "Assign Role". */
    public const ASSIGNABLE = [Rbac::ADMIN, Rbac::STAFF, Rbac::MAINTENANCE, Rbac::STUDENT, Rbac::FACULTY];

    public function assign(User $actor, User $target, string $role, ?int $departmentId = null): void
    {
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
        if ($role === Rbac::STAFF && $memberRole !== LibraryMember::ROLE_STUDENT) {
            $this->fail('Only Student members can be assigned as Staff (Student Staff).');
        }
        if (in_array($role, Rbac::MEMBER_ROLES, true) && $memberRole && strtolower($memberRole) !== $role) {
            $this->fail("This account is registered as {$memberRole}; it can only revert to that member role.");
        }

        DB::transaction(function () use ($actor, $target, $role, $departmentId) {
            $old = $target->getRoleNames()->first();
            $target->syncRoles([$role]);

            in_array($role, Rbac::PERSONNEL_ROLES, true)
                ? $this->ensurePersonnel($target, $departmentId)
                : $target->personnel?->delete();

            activity('roles')->causedBy($actor)->performedOn($target)
                ->withProperties(['from' => $old, 'to' => $role])
                ->log("Changed role from {$old} to {$role}");
        });
    }

    public function transferSuperadmin(User $actor, User $target): void
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
        if (! $target->isActive() || ! $target->email_verified_at) {
            $this->fail('The new superadmin must have an active, email-verified account.');
        }

        DB::transaction(function () use ($actor, $target) {
            $target->syncRoles([Rbac::SUPERADMIN]);
            $this->ensurePersonnel($target, $actor->personnel?->department_id);

            // Previous superadmin becomes a regular faculty library member.
            $actor->syncRoles([Rbac::FACULTY]);
            $actor->personnel?->delete();

            activity('roles')->causedBy($actor)->performedOn($target)
                ->log('Transferred superadmin (Head Librarian) privilege');
        });
    }

    private function ensurePersonnel(User $user, ?int $departmentId): void
    {
        $personnel = LibraryPersonnel::withTrashed()->firstOrNew(['account_id' => $user->id]);

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
        } elseif ($departmentId) {
            $personnel->department_id = $departmentId;
        } elseif (! $personnel->department_id && $user->member?->department_id) {
            $personnel->department_id = $user->member->department_id;
        }

        $personnel->save();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['role' => $message]);
    }
}
