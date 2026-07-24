<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Webkul\ProductPassport\Http\Requests\UpdatePassportMappingRequest;

/**
 * Reuses the admin request's rules and cross-class type-mismatch guard;
 * authorization is enforced by the api.scope middleware and validation errors
 * use the API's JSON envelope.
 */
class PassportMappingApiRequest extends UpdatePassportMappingRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
