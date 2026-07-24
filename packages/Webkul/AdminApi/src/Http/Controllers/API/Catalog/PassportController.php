<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\PublishPassportApiRequest;
use Webkul\AdminApi\Http\Resources\PublicationResource;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\ProductPassport\Http\Controllers\PassportMappingController;
use Webkul\ProductPassport\Http\Controllers\PublicationController as PassportFeature;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Services\Publisher;

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

    /**
     * Queue passport publication for a product on the given channel/locales.
     */
    public function publish(PublishPassportApiRequest $request, string $sku): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $product = $this->productRepository->findByField('sku', $sku)->first();
        if (! $product) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.products.product-not-found', ['sku' => $sku]));
        }

        $channel = ChannelProxy::modelClass()::findOrFail($request->integer('channel_id'));

        abort_unless(
            (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false),
            403,
        );

        PublishPassportForProductChannelJob::dispatch(
            $product->id,
            $channel->id,
            'dpp',
            $request->collect('locale_ids')->map(fn ($id): int => (int) $id)->all(),
            auth()->guard('api')->id(),
        );

        return $this->successResponse(trans('passport::app.publications.publish-queued'), 202);
    }

    /**
     * Withdraw a publication, making its passport unresolvable.
     */
    public function withdraw(int $id, Publisher $publisher): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $publication = Publication::find($id);
        if (! $publication) {
            return $this->modelNotFoundResponse(trans('passport::app.publications.not-found', ['id' => $id]));
        }

        $publisher->withdraw($publication);

        return $this->successResponse(trans('passport::app.publications.withdrawn'));
    }
}
