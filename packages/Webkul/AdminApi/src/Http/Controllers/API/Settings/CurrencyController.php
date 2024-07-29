<?php

namespace Webkul\AdminApi\Http\Controllers\API\Settings;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\CurrencyDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;

class CurrencyController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(CurrencyDataSource::class)->toJson();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get($code): JsonResponse
    {
        try {
            return response()->json(app(CurrencyDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
