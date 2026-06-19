<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ApiValidator;
use Illuminate\Http\Request;

trait ValidatesApiInput
{
    protected function validateApi(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return ApiValidator::validate($request, $rules, $messages, $attributes);
    }

    protected function validateApiQuery(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return ApiValidator::validateQuery($request, $rules, $messages, $attributes);
    }
}
