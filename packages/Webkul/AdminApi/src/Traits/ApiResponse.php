<?php

namespace Webkul\AdminApi\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

trait ApiResponse
{
    /**
     * This function is responsible for creating a success response in JSON format.
     *
     * @return \Illuminate\Http\JsonResponse *
     */
    protected function successResponse(string $message = 'Operation completed successfully', int $code = 200, array $data = [])
    {
        $responseData = [
            'success' => true,
            'message' => $message,
        ];

        if (! empty($data)) {
            $responseData['data'] = $data;
        }

        return response()->json($responseData, $code);
    }

    /**
     * This function is used to return a JSON response when a requested model is not found.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function modelNotFoundResponse(string $message = 'Data not found.', int $code = 404)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    /**
     * Handles and returns a validation error response.
     *
     * @return \Illuminate\Http\JsonResponse .
     */
    protected function validateErrorResponse(mixed $validator, string $message = 'Validation failed.', int $code = 422)
    {
        $errors = $validator instanceof \Illuminate\Validation\Validator ? (new \Illuminate\Validation\ValidationException($validator))->errors() : $validator;

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Handles and logs exceptions that occur during the execution of the application
     *
     * @param  \Exception|ModelNotFoundException  $e  The exception to be handled.
     * @return \Illuminate\Http\JsonResponse*
     */
    protected function storeExceptionLog($e)
    {
        if ($e instanceof ModelNotFoundException || $e instanceof UnprocessableEntityHttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        Log::error(
            $e->getMessage(),
            [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' => 'Internal Server Error',
        ], 500);
    }
}
