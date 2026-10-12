<?php

namespace Webkul\AdminApi\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * Authorization is enforced by the api.scope middleware, not per-request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the failed-validation payload in the API's standard envelope.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
