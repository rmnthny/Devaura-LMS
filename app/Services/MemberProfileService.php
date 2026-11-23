<?php

namespace App\Services;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;

class MemberProfileService
{
    public function save(User $user, array $data): LibraryMember
    {
        $names = [
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_initial' => $data['middle_initial'] ?? null,
        ];
        $member = $user->member()->updateOrCreate([], $names);

        // Faculty departments are stored on the linked personnel profile.
        $personnel = $user->personnel()->withTrashed()->first();
        if ($data['member_role'] === LibraryMember::ROLE_FACULTY) {
            $personnel ??= new LibraryPersonnel(['account_id' => $user->getKey()]);
            $personnel->fill([...$names, 'department_id' => $data['department_id']]);
            $personnel->deleted_at = null;
            $personnel->save();
        } elseif ($personnel && ! $personnel->trashed()) {
            $personnel->update([...$names, 'department_id' => null]);
        }

        return $member->setRelation('user', $user);
    }
}
