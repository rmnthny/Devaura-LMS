<?php

namespace App\Http\Requests\Members;

use App\Models\LibraryMember;
use App\Models\User;
use App\Support\MemberFieldRules;
use App\Support\Rbac;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMemberRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['last_name', 'first_name'] as $field) {
            if (is_string($this->input($field))) {
                $merged[$field] = preg_replace('/\s+/u', ' ', trim($this->input($field)));
            }
        }
        if (is_string($this->input('email'))) {
            $merged['email'] = trim($this->input('email'));
        }
        if ($this->has('middle_initial')) {
            $merged['middle_initial'] = MemberFieldRules::formatMiddleInitial($this->input('middle_initial'));
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function authorize(): bool
    {
        return $this->route('member') !== null || $this->user()?->hasRole(Rbac::SUPERADMIN) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var LibraryMember|null $member */
        $member = $this->route('member');
        $digits = $this->input('member_role') === LibraryMember::ROLE_FACULTY ? 7 : 12;

        return [
            'member_role' => ['required', Rule::in([LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY])],
            'department_id' => ['nullable', 'required_if:member_role,Faculty', 'prohibited_unless:member_role,Faculty', 'exists:department,department_id'],
            'reference_number' => [
                'required',
                'string',
                "digits:{$digits}",
                Rule::unique('user_account', 'reference_number')->ignore($member?->account_id, 'account_id'),
            ],
            ...MemberFieldRules::names(),
            'email' => [
                ...MemberFieldRules::email(),
                Rule::unique('user_account', 'email')->ignore($member?->account_id, 'account_id'),
            ],
            'account_status' => $member
                ? ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_LOCKED])]
                : ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...MemberFieldRules::messages(),
            'reference_number.digits' => $this->input('member_role') === LibraryMember::ROLE_FACULTY
                ? 'DepEd Employee Number must be strictly 7 digits.'
                : 'Student LRN must be strictly 12 digits.',
            'reference_number.unique' => 'This reference number belongs to an existing or archived account.',
            'email.unique' => 'This email address belongs to an existing or archived account.',
        ];
    }
}
