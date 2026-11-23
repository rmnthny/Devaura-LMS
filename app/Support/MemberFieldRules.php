<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

final class MemberFieldRules
{
    public const NAME_PATTERN = '(?:\p{L}\p{M}*)+(?: +(?:\p{L}\p{M}*)+)*';
    public const INITIAL_PATTERN = '\p{L}\p{M}*\.?';
    public const EMAIL_PATTERN = '[^@\s]+@[^@\s]+\.[^@\s]+';
    public const PASSWORD_PATTERN = '(?=.*\p{Lu})(?=.*\p{Ll})(?=.*\p{N}).{8,64}';
    public const PASSWORD_HINT = 'Use 8 to 64 characters with uppercase and lowercase letters and a number.';

    public static function names(bool $requireMiddleInitial = false): array
    {
        $pattern = 'regex:/\A'.self::NAME_PATTERN.'\z/u';

        return [
            'last_name' => ['required', 'string', 'max:50', $pattern],
            'first_name' => ['required', 'string', 'max:50', $pattern],
            'middle_initial' => [$requireMiddleInitial ? 'required' : 'nullable', 'string', 'max:5', 'regex:/\A'.self::INITIAL_PATTERN.'\z/u'],
        ];
    }

    public static function formatMiddleInitial(mixed $initial): mixed
    {
        if (! is_string($initial) || ! preg_match('/\A'.self::INITIAL_PATTERN.'\z/u', $initial)) {
            return $initial;
        }

        return mb_convert_case(rtrim($initial, '.'), MB_CASE_UPPER_SIMPLE, 'UTF-8').'.';
    }

    public static function email(): array
    {
        return ['required', 'string', 'max:254', 'email:rfc', 'regex:/\A'.self::EMAIL_PATTERN.'\z/u'];
    }

    public static function newPassword(): array
    {
        return [
            'bail', 'string', 'max:64', Password::min(8)->mixedCase()->numbers(),
            function (string $attribute, string $password, Closure $fail): void {
                // PHP's bcrypt accepts at most 72 bytes.
                if (strlen($password) > 72) {
                    $fail('The password is too long when encoded. Use fewer multibyte characters.');
                }
            },
        ];
    }

    public static function messages(): array
    {
        return [
            'first_name.regex' => 'First name may contain only letters and spaces.',
            'last_name.regex' => 'Last name may contain only letters and spaces.',
            'middle_initial.regex' => 'Enter a single middle initial, such as V.',
            'email.regex' => 'Enter a complete email address, such as name@example.com.',
        ];
    }
}
