<?php

namespace Webkul\AdminApi\Http\Controllers\API\Settings;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\ChannelDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;

class ChannelController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(ChannelDataSource::class)->toJson();
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
            return response()->json(app(ChannelDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
