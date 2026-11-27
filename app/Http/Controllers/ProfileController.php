<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\LibraryAlerts;
use App\Support\Rbac;
use App\Support\MemberFieldRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $account = $request->user();

        return view('members.profile', [
            'account' => $account,
            'profile' => $account->member ?? $account->personnel,
            'departments' => $this->hasSubjectDepartment($account) ? Department::orderBy('department_name')->get() : collect(),
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request, LibraryAlerts $alerts)
    {
        $user = $request->user();
        $isFaculty = $this->hasSubjectDepartment($user);
        $data = $request->validate([
            ...MemberFieldRules::names(),
            'department_id' => $isFaculty ? ['sometimes', 'required', 'exists:department,department_id'] : ['prohibited'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', ...MemberFieldRules::newPassword(), 'confirmed'],
        ], MemberFieldRules::messages());

        if (array_key_exists('middle_initial', $data)) {
            $data['middle_initial'] = MemberFieldRules::formatMiddleInitial($data['middle_initial']);
        }

        DB::transaction(function () use ($user, $data, $isFaculty, $alerts): void {
            $member = $user->member()->lockForUpdate()->first();
            $account = User::lockForUpdate()->findOrFail($user->getKey());
            $passwordChanged = false;
            if (! empty($data['password'])) {
                if (! Hash::check($data['current_password'], $account->getAuthPassword())) {
                    throw ValidationException::withMessages(['current_password' => 'Your current password has changed. Try again.']);
                }
                $passwordChanged = ! Hash::check($data['password'], $account->getAuthPassword());
                if ($passwordChanged) {
                    $account->password_hash = $data['password'];
                    $account->save();
                }
            }

            $names = collect($data)->only(['last_name', 'first_name', 'middle_initial'])->all();
            $member?->update($names);
            if ($isFaculty && array_key_exists('department_id', $data)) {
                $names['department_id'] = $data['department_id'] ?: null;
            }
            if ($isFaculty && array_key_exists('department_id', $names)) {
                $personnel = $account->personnel()->withTrashed()->firstOrNew();
                $personnel->fill($names);
                $personnel->deleted_at = null;
                $personnel->save();
            } else {
                $personnel = $account->personnel;
                $personnel?->update($names);
            }

            if ($passwordChanged || $member?->wasChanged() || $personnel?->wasChanged() || $personnel?->wasRecentlyCreated) {
                $alerts->accountUpdated($account, $passwordChanged
                    ? 'The password for your library account was changed.'
                    : 'Your library profile was updated.', $passwordChanged);
            }
        });

        return $request->expectsJson() ? response()->json(['message' => 'Personal information updated.'])
            : to_route('profile.edit')->with('status', 'Personal information updated.');
    }

    private function hasSubjectDepartment(\App\Models\User $user): bool
    {
        return $user->member?->member_role === LibraryMember::ROLE_FACULTY
            || $user->hasAnyRole([Rbac::FACULTY, Rbac::ADMIN, Rbac::SUPERADMIN]);
    }
}
