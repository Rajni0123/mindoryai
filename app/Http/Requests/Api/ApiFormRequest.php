<?php

namespace App\Http\Requests\Api;

use App\Support\ApiValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiValidator::logFailure($this, $validator->errors()->toArray());

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Invalid input.',
            'errors' => $validator->errors(),
        ], 400));
    }
}
