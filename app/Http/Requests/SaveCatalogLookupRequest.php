<?php

namespace App\Http\Requests;

use App\Support\MemberFieldRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCatalogLookupRequest extends FormRequest
{
    public const GENRE_PATTERN = '(?:\p{L}\p{M}*)+(?:(?: +| ?[-&] ?)(?:\p{L}\p{M}*)+)*';
    public const INDIVIDUAL_PUBLISHER_PATTERN = '(?:\p{L}\p{M}*)+(?: +(?:\p{L}\p{M}*\.?))+|(?:\p{L}\p{M}*)+';
    public const PUBLISHER_PATTERN = '[\p{L}\p{N}](?:[\p{L}\p{M}\p{N} .,&\'\-\/]*[\p{L}\p{M}\p{N}.])?';

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['genre_name', 'last_name', 'first_name', 'publisher_name'] as $field) {
            if (is_string($this->input($field))) {
                $merged[$field] = preg_replace('/\s+/u', ' ', trim($this->input($field)));
            }
        }

        if ($this->has('middle_initial')) {
            $merged['middle_initial'] = MemberFieldRules::formatMiddleInitial($this->input('middle_initial'));
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        $namePattern = 'regex:/\A'.MemberFieldRules::NAME_PATTERN.'\z/u';

        return match ($this->route('lookup')) {
            'genres' => [
                'genre_name' => [
                    'required', 'string', 'max:100',
                    'regex:/\A'.self::GENRE_PATTERN.'\z/u',
                    Rule::unique('genre', 'genre_name')->ignore($this->route('record'), 'genre_id'),
                ],
            ],
            'authors' => [
                'last_name' => ['nullable', 'required_without:first_name', 'string', 'max:50', $namePattern],
                'first_name' => ['nullable', 'required_without:last_name', 'string', 'max:50', $namePattern],
                'middle_initial' => ['nullable', 'string', 'max:5', 'regex:/\A'.MemberFieldRules::INITIAL_PATTERN.'\z/u'],
            ],
            'publishers' => [
                'publisher_name' => [
                    'required', 'string', 'max:255',
                    'regex:/\A'.($this->input('publisher_type') === 'Individual' ? self::INDIVIDUAL_PUBLISHER_PATTERN : self::PUBLISHER_PATTERN).'\z/u',
                ],
                'publisher_type' => ['nullable', Rule::in(['Individual', 'Organization'])],
            ],
        };
    }

    public function messages(): array
    {
        $isIndividual = $this->input('publisher_type') === 'Individual';

        return [
            ...MemberFieldRules::messages(),
            'genre_name.regex' => 'Genre name may contain only letters, spaces, hyphens, and & (e.g., Non-Fiction, Science & Tech).',
            'publisher_name.regex' => $isIndividual
                ? 'Individual publisher name may contain only letters, spaces, and initials (e.g., Juan M. Dela Cruz).'
                : 'Organization publisher name may contain only letters, numbers, spaces, and standard punctuation (. , & \' - /).',
        ];
    }
}
