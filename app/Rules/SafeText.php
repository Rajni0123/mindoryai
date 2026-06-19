<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects control characters and obvious script injection in user-facing text fields.
 */
class SafeText implements ValidationRule
{
    public function __construct(
        private readonly bool $allowNewlines = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $fail('The :attribute contains invalid control characters.');
            return;
        }

        if (!$this->allowNewlines && preg_match('/[\r\n]/', $value)) {
            $fail('The :attribute must not contain line breaks.');
            return;
        }

        if (preg_match('/<script|javascript:|on\w+\s*=/i', $value)) {
            $fail('The :attribute contains disallowed content.');
        }
    }
}
