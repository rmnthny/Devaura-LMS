<?php

namespace App\Http\Requests\Auth;

use App\Models\LibraryMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $digits = $this->input('member_role') === LibraryMember::ROLE_FACULTY ? 7 : 12;

        return [
            'member_role' => ['required', Rule::in([LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY])],
            'department_id' => ['nullable', 'required_if:member_role,Faculty', 'exists:department,department_id'],
            'reference_number' => ['required', 'string', "digits:{$digits}", 'unique:library_member,reference_number'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'member_role.in' => 'Public registration is restricted to Student and Faculty accounts only.',
            'reference_number.digits' => $this->input('member_role') === LibraryMember::ROLE_FACULTY
                ? 'DepEd Employee Number must be strictly 7 digits.'
                : 'Student LRN must be strictly 12 digits.',
            'reference_number.unique' => 'This LRN / DepEd Employee Number is already registered.',
        ];
    }
}
