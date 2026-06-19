<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBase64Image implements ValidationRule
{
    public function __construct(
        private readonly int $maxKb = 10240,
        private readonly ?array $allowedMimeTypes = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $fail('The :attribute must be a valid base64-encoded image.');
            return;
        }

        $payload = $value;
        if (str_contains($payload, ',')) {
            $payload = explode(',', $payload, 2)[1];
        }

        $payload = preg_replace('/\s+/', '', $payload);
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            $fail('The :attribute must be valid base64 image data.');
            return;
        }

        $maxBytes = $this->maxKb * 1024;
        if (strlen($decoded) > $maxBytes) {
            $fail("The :attribute must not exceed {$this->maxKb}KB.");
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($decoded);
        $allowed = $this->allowedMimeTypes ?? config('api-validation.allowed_image_mime_types', []);

        if (!in_array($mime, $allowed, true)) {
            $fail('The :attribute must be a JPEG, PNG, GIF, or WebP image.');
        }
    }
}
