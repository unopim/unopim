<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\PublishPassportApiRequest;
use Webkul\AdminApi\Http\Requests\Catalog\RedactPassportApiRequest;
use Webkul\AdminApi\Http\Resources\PublicationResource;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\ProductPassport\Http\Controllers\PublicationController as PassportFeature;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Services\PublicAccessGate;
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
            ->paginate(min(100, max(1, (int) request()->input('limit', 10))));

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
     * Queue passport publication for a product on the given channel/locales.
     */
    public function publish(
        PublishPassportApiRequest $request,
        string $sku,
        PublicAccessGate $publicAccess,
        PassportReadinessService $readiness,
    ): JsonResponse {
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

        if (! $publicAccess->enabledForChannel($channel->code)) {
            return $this->publishRefusedResponse('channel_id', trans('passport::app.publications.public-access-disabled'));
        }

        $localeIds = $request->collect('locale_ids')->map(fn ($id): int => (int) $id)->values()->all();

        $blockedLocales = $this->blockedLocales($readiness, $product, $channel, $localeIds);

        if ($blockedLocales->isNotEmpty()) {
            return $this->publishRefusedResponse(
                'locale_ids',
                trans('passport::app.catalog.products.edit.passport.publish-blocked'),
                ['blocked_locales' => $blockedLocales],
            );
        }

        if (! $this->publicationAcceptsPublishing($product->id, $channel->id)) {
            return $this->publishRefusedResponse('status', trans('passport::app.publications.publish-withdrawn'));
        }

        PublishPassportForProductChannelJob::dispatch(
            $product->id,
            $channel->id,
            'dpp',
            $localeIds,
            auth()->guard('api')->id(),
        );

        return $this->successResponse(trans('passport::app.publications.publish-queued'), 202);
    }

    /**
     * The requested locales the product cannot be published in yet, with the reason for each.
     *
     * @param  array<int, int>  $localeIds
     * @return Collection<int, array<string, mixed>>
     */
    private function blockedLocales(PassportReadinessService $readiness, Product $product, Channel $channel, array $localeIds): Collection
    {
        $locales = $channel->locales()->whereIn('locales.id', $localeIds)->get();

        $assessments = $readiness->assessMany($product, $channel, $locales);

        return $locales
            ->reject(fn ($locale): bool => $assessments->get($locale->id)->isReady())
            ->map(function ($locale) use ($assessments): array {
                $assessment = $assessments->get($locale->id);

                return [
                    'locale_code'    => $locale->code,
                    'status'         => $assessment->template === null ? 'missing_template' : 'missing_fields',
                    'missing_fields' => $assessment->missingFields
                        ->map(fn ($field): array => [
                            'code'   => $field->code,
                            'label'  => $field->getTranslatedValueWithFallback('label', $locale->code) ?: $field->code,
                            'source' => $field->source_type->value,
                        ])
                        ->all(),
                ];
            })
            ->values();
    }

    private function publicationAcceptsPublishing(int $productId, int $channelId): bool
    {
        $status = Publication::query()
            ->where('product_id', $productId)
            ->where('channel_id', $channelId)
            ->where('type', 'dpp')
            ->value('status');

        return ! $status instanceof PublicationStatus || $status->acceptsNewVersions();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function publishRefusedResponse(string $field, string $message, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => [$field => [$message]],
            ...$extra,
        ], 422);
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

        try {
            $publisher->withdraw($publication);

            return $this->successResponse(trans('passport::app.publications.withdrawn'));
        } catch (InvalidPublicationTransitionException) {
            return $this->validateErrorResponse(
                ['status' => [trans('passport::app.publications.withdraw-invalid')]],
                trans('passport::app.publications.withdraw-invalid')
            );
        }
    }

    /**
     * Return a withdrawn publication to Published.
     */
    public function reinstate(int $id, Publisher $publisher): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $publication = Publication::find($id);
        if (! $publication) {
            return $this->modelNotFoundResponse(trans('passport::app.publications.not-found', ['id' => $id]));
        }

        try {
            $publisher->reinstate($publication);

            return $this->successResponse(trans('passport::app.publications.reinstated'));
        } catch (InvalidPublicationTransitionException) {
            return $this->validateErrorResponse(
                ['status' => [trans('passport::app.publications.reinstate-invalid')]],
                trans('passport::app.publications.reinstate-invalid')
            );
        }
    }

    /**
     * GDPR erasure: redact every current version and flip the publication to Redacted.
     */
    public function redact(RedactPassportApiRequest $request, int $id, Publisher $publisher): JsonResponse
    {
        abort_unless(PassportFeature::featureEnabled(), 404);

        $publication = Publication::find($id);
        if (! $publication) {
            return $this->modelNotFoundResponse(trans('passport::app.publications.not-found', ['id' => $id]));
        }

        try {
            $publisher->redactAll($publication, (int) auth()->guard('api')->id(), (string) $request->input('reason'));

            return $this->successResponse(trans('passport::app.publications.redacted'));
        } catch (InvalidPublicationTransitionException) {
            return $this->validateErrorResponse(
                ['status' => [trans('passport::app.publications.redact-invalid')]],
                trans('passport::app.publications.redact-invalid')
            );
        }
    }
}
