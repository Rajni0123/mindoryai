<?php

namespace App\Support;

use App\Rules\SafeText;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiValidator
{
    public static function validate(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return self::run($request, $request->all(), $rules, $messages, $attributes);
    }

    public static function validateQuery(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return self::run($request, $request->query(), $rules, $messages, $attributes);
    }

    public static function validateRoute(Request $request, array $data, array $rules, array $messages = []): array
    {
        return self::run($request, $data, $rules, $messages);
    }

    private static function run(Request $request, array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            self::logFailure($request, $validator->errors()->toArray());
            self::throwResponse($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    public static function logFailure(Request $request, array $errors): void
    {
        Log::warning('API validation failed', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'errors' => $errors,
        ]);
    }

    public static function throwResponse(array $errors, string $message = 'Invalid input.'): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 400));
    }

    public static function safeString(int $max, bool $required = false, bool $allowNewlines = false): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:' . $max, new SafeText($allowNewlines)];

        return $rules;
    }

    public static function paginationLimit(int $default = 20, int $max = null): array
    {
        $max = $max ?? config('api-validation.limits.pagination_max', 100);

        return ['nullable', 'integer', 'min:1', 'max:' . $max];
    }

    public static function inConfig(string $configKey): string
    {
        $values = config('api-validation.' . $configKey, []);

        return 'nullable|string|in:' . implode(',', $values);
    }

    public static function requiredInConfig(string $configKey): string
    {
        $values = config('api-validation.' . $configKey, []);

        return 'required|string|in:' . implode(',', $values);
    }

    public static function mobileIndia(bool $required = true): string
    {
        return ($required ? 'required' : 'nullable') . '|string|regex:/^[6-9]\d{9}$/';
    }

    public static function slug(bool $required = true): string
    {
        $max = config('api-validation.limits.slug_max', 100);
        $prefix = $required ? 'required' : 'nullable';

        return "{$prefix}|string|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*\$/|max:{$max}";
    }

    public static function roomCode(): string
    {
        return 'required|string|regex:/^[A-Za-z0-9]{6}$/';
    }

    public static function configKey(): string
    {
        return 'regex:/^[a-z][a-z0-9_.]{0,99}$/';
    }
}
