<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Rbac;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request, OtpService $otpService)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'username' => $this->generateUniqueUsername($data['email']),
                'email' => $data['email'],
                'password' => $data['password'],
                'account_status' => User::STATUS_ACTIVE,
            ]);

            $spatieRole = $data['member_role'] === LibraryMember::ROLE_FACULTY
                ? Rbac::FACULTY
                : Rbac::STUDENT;

            $user->assignRole($spatieRole);

            LibraryMember::create([
                'account_id' => $user->id,
                'department_id' => $data['member_role'] === LibraryMember::ROLE_FACULTY ? ($data['department_id'] ?? null) : null,
                'member_role' => $data['member_role'],
                'reference_number' => $data['reference_number'],
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'middle_initial' => $data['middle_initial'] ?? null,
            ]);

            return $user;
        });

        $otpService->issue($user);
        $request->session()->put('otp_user_id', $user->id);

        return response()->json(['message' => 'Account created! Enter the 6-digit verification code sent to '.$user->email.'.'], 201);
    }

    private function generateUniqueUsername(string $email): string
    {
        $base = Str::of(Str::before($email, '@'))
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]/', '')
            ->substr(0, 40)
            ->value() ?: 'member';

        $candidate = $base;
        $suffix = 1;

        while (User::withTrashed()->where('username', $candidate)->exists()) {
            $candidate = substr($base, 0, 40).$suffix++;
        }

        return $candidate;
    }
}
