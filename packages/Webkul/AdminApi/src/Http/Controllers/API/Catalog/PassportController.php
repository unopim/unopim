<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Resources\PublicationResource;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\ProductPassport\Http\Controllers\PassportMappingController;
use Webkul\ProductPassport\Http\Controllers\PublicationController as PassportFeature;
use Webkul\Publication\Models\Publication;

class PassportController extends ApiController
{
    public function __construct(
        protected ProductRepository $productRepository,
    ) {}

    /**
     * List publications, newest first, optionally filtered by product sku or status.
     */
    public function index(): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $publications = Publication::query()
            ->with(['product', 'channel'])
            ->when(request()->filled('sku'), function ($query): void {
                $query->whereHas('product', fn ($q) => $q->where('sku', request('sku')));
            })
            ->when(request()->filled('status'), fn ($query) => $query->where('status', request('status')))
            ->orderByDesc('id')
            ->paginate((int) request()->input('limit', 10));

        return PublicationResource::collection($publications)->response();
    }

    /**
     * All publications for a single product, identified by sku.
     */
    public function get(string $sku): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $product = $this->productRepository->findByField('sku', $sku)->first();
        if (! $product) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.products.product-not-found', ['sku' => $sku]));
        }

        $publications = Publication::query()
            ->with(['product', 'channel'])
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->get();

        return PublicationResource::collection($publications)->response();
    }

    /**
     * The passport field-to-source attribute mapping configuration.
     */
    public function mapping(): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        return response()->json([
            'data' => app(PassportMappingController::class)->mappingData(),
        ]);
    }
}
