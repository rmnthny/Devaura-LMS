<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\PasswordChangedMail;
use App\Models\Department;
use App\Models\LibraryMember;
use App\Support\Rbac;
use App\Support\MemberFieldRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

    public function update(Request $request)
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

        DB::transaction(function () use ($user, $data, $isFaculty) {
            if (! empty($data['password'])) {
                $user->password_hash = $data['password'];
                $user->save();
            }

            $names = collect($data)->only(['last_name', 'first_name', 'middle_initial'])->all();

            $user->member?->update($names);
            if ($isFaculty && array_key_exists('department_id', $data)) {
                $names['department_id'] = $data['department_id'] ?: null;
            }
            if ($isFaculty && array_key_exists('department_id', $names)) {
                $personnel = $user->personnel()->withTrashed()->firstOrNew();
                $personnel->fill($names);
                $personnel->deleted_at = null;
                $personnel->save();
            } else {
                $user->personnel?->update($names);
            }
        });

        if (! empty($data['password'])) {
            try {
                Mail::to($user->email)->send(new PasswordChangedMail(
                    $user->refresh()->fullName(),
                    now()->format('F j, Y \a\t g:i A'),
                ));
            } catch (\Throwable $error) {
                report($error);
            }
        }

        return $request->expectsJson() ? response()->json(['message' => 'Personal information updated.'])
            : to_route('profile.edit')->with('status', 'Personal information updated.');
    }

    private function hasSubjectDepartment(\App\Models\User $user): bool
    {
        return $user->member?->member_role === LibraryMember::ROLE_FACULTY
            || $user->hasAnyRole([Rbac::FACULTY, Rbac::ADMIN, Rbac::SUPERADMIN]);
    }
}
