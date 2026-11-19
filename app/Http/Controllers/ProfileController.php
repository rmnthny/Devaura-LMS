<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\LibraryMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** Self-service "Update Personal Info" for every authenticated user. */
class ProfileController extends Controller
{
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $isFaculty = $user->member?->member_role === LibraryMember::ROLE_FACULTY || (! $user->member && $user->personnel !== null);

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'department_id' => $isFaculty ? ['nullable', 'exists:department,department_id'] : ['nullable'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($user, $data, $isFaculty) {
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->save();
            }

            $names = collect($data)->only(['last_name', 'first_name', 'middle_initial'])->all();

            if ($isFaculty && array_key_exists('department_id', $data)) {
                $names['department_id'] = $data['department_id'] ?: null;
            }

            $user->member?->update($names);
            $user->personnel?->update($names);
        });

        return response()->json(['message' => 'Personal information updated.']);
    }
}
