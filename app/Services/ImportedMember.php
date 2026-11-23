<?php

namespace App\Services;

use App\Mail\WelcomeMemberMail;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ImportedMember
{
    public function create(array $row, MemberProfileService $profiles): bool
    {
        $duplicate = fn () => User::withTrashed()->where('email', $row['email'])
            ->orWhere('reference_number', $row['reference_number'])->exists();
        if ($duplicate()) {
            return false;
        }

        try {
            return DB::transaction(fn () => activity()->withoutLogs(function () use ($row, $profiles): bool {
                do {
                    $password = Str::random(10);
                } while (! preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])/', $password));
                $account = User::create([
                    'username' => (string) Str::uuid(),
                    'email' => $row['email'],
                    'reference_number' => $row['reference_number'],
                    'password_hash' => $password,
                    'account_status' => User::STATUS_ACTIVE,
                ]);
                $account->assignMemberQr();
                $account->assignRole(strtolower($row['member_role']));
                $member = $profiles->save($account, $row);
                $reference = $row['reference_number'];
                $masked = str_repeat('•', strlen($reference) - 4).substr($reference, -4);
                $department = $row['department_id'] ? Department::findOrFail($row['department_id'])->department_name : null;
                $welcome = new WelcomeMemberMail(
                    $member->fullName(), $account->email, $row['member_role'], $department,
                    $masked, $account->user_qr_value, $password,
                );
                // Save the email job with the account so a rollback removes both.
                Mail::mailer('smtp')->to($account->email)->queue($welcome->onConnection('member-mail')->beforeCommit());

                return true;
            }));
        } catch (UniqueConstraintViolationException $error) {
            if ($duplicate()) {
                return false;
            }
            throw $error;
        }
    }
}
