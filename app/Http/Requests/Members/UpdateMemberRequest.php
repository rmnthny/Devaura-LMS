<?php

namespace App\Http\Requests\Members;

use App\Models\LibraryMember;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var LibraryMember $member */
        $member = $this->route('member');
        $digits = $this->input('member_role') === LibraryMember::ROLE_FACULTY ? 7 : 12;

        return [
            'member_role' => ['required', Rule::in([LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY])],
            'department_id' => ['nullable', 'required_if:member_role,Faculty', 'exists:department,department_id'],
            'reference_number' => [
                'required',
                'string',
                "digits:{$digits}",
                Rule::unique('library_member', 'reference_number')->ignore($member->member_id, 'member_id'),
            ],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'email' => [
                'required',
                'string',
                'email',
                'max:254',
                Rule::unique('users', 'email')->ignore($member->account_id),
            ],
            'account_status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_LOCKED])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reference_number.digits' => $this->input('member_role') === LibraryMember::ROLE_FACULTY
                ? 'DepEd Employee Number must be strictly 7 digits.'
                : 'Student LRN must be strictly 12 digits.',
            'reference_number.unique' => 'This LRN / DepEd Employee Number is already registered to another member.',
        ];
    }
}
