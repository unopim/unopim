<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\ProductPassport\Http\Requests\BulkPublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\MassPublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\PublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\RepublishPassportVersionRequest;
use Webkul\ProductPassport\Jobs\BulkPublishPassportsJob;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersionProxy;
use Webkul\Publication\Services\Publisher;

class PublicationController extends Controller
{
    public function index(): View|JsonResponse|BinaryFileResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        if (! $this->featureEnabled()) {
            abort(404);
        }

        if (request()->ajax()) {
            // toJson() returns a JsonResponse for the grid payload, or a BinaryFileResponse for an export.
            return resolve(PublicationDataGrid::class)->toJson();
        }

        return view('passport::admin.index');
    }

    /**
     * The passport feature is opt-in: its admin surface (grid, menu, product
     * panel) is present only while enabled at any scope (global or per-channel).
     * Queried directly rather than via getConfigData so a value saved at the
     * global (null-channel) scope is honoured regardless of channel fallback.
     */
    public static function featureEnabled(): bool
    {
        return CoreConfig::query()
            ->where('code', 'catalog.product_passport.settings.enabled')
            ->where('value', '1')
            ->exists();
    }

    /**
     * One job dispatch per admin action, not one per locale — the job
     * itself loops requested locales.
     */
    public function publish(PublishPassportRequest $request, Product $product): JsonResponse
    {
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
            auth()->guard('admin')->id(),
        );

        return new JsonResponse([
            'message'      => trans('passport::app.publications.publish-queued'),
            'redirect_url' => route('admin.catalog.passports.index'),
        ]);
    }

    /**
     * Publish every locale of the requested channel for each selected product,
     * one job dispatch per product (each job loops the channel's locales).
     */
    public function massPublish(MassPublishPassportRequest $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            abort(404);
        }

        $channel = ChannelProxy::modelClass()::query()
            ->where('code', core()->getRequestedChannelCode())
            ->with('locales')
            ->first();

        abort_if($channel === null, 404);

        abort_unless(
            (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false),
            403,
        );

        $localeIds = $channel->locales->pluck('id')->all();

        $productIds = $request->collect('indices')->map(fn ($id): int => (int) $id);

        $adminId = auth()->guard('admin')->id();

        foreach ($productIds as $productId) {
            PublishPassportForProductChannelJob::dispatch($productId, $channel->id, 'dpp', $localeIds, $adminId);
        }

        return new JsonResponse([
            'message' => trans('passport::app.publications.mass-publish.queued', ['count' => $productIds->count()]),
        ]);
    }

    /**
     * Publish the selected passport rows across each publication's own channel
     * locales. Fans out through a chunking orchestrator job so the request
     * returns immediately regardless of how many rows were selected.
     */
    public function bulkPublish(BulkPublishPassportRequest $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            abort(404);
        }

        BulkPublishPassportsJob::dispatch(
            $request->collect('indices')->map(fn ($id): int => (int) $id)->all(),
            auth()->guard('admin')->id(),
        );

        return new JsonResponse([
            'message' => trans('passport::app.publications.bulk-publish-queued'),
        ]);
    }

    public function withdraw(Publication $publication, Publisher $publisher): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.withdraw'), 403);

        $publisher->withdraw($publication);

        return new JsonResponse([
            'message'      => trans('passport::app.publications.withdrawn'),
            'redirect_url' => route('admin.catalog.passports.index'),
        ]);
    }

    /**
     * The immutable version history for a passport: every version row for the
     * publication, newest first within each locale. Eager-loads `locale` and
     * `publishedBy` so the listing is a fixed number of queries, never N+1.
     */
    public function versions(Publication $publication): View
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        $publication->load([
            'product',
            'channel',
            'versions' => fn ($query) => $query
                ->with(['locale', 'publishedBy'])
                ->orderBy('locale_id')
                ->orderByDesc('version'),
        ]);

        return view('passport::admin.versions.index', compact('publication'));
    }

    /**
     * Rollback = forward-only republish of an older immutable version. Mints a
     * new current version from the source's frozen payload; the old row is left
     * intact. Refuses on a redacted publication/version.
     */
    public function republish(RepublishPassportVersionRequest $request, Publication $publication, Publisher $publisher): JsonResponse
    {
        $source = PublicationVersionProxy::modelClass()::query()
            ->where('publication_id', $publication->id)
            ->findOrFail($request->integer('version_id'));

        try {
            $version = $publisher->republishFrom($source, auth()->guard('admin')->id());
        } catch (InvalidPublicationTransitionException) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.republish-invalid'),
            ], 422);
        }

        return new JsonResponse([
            'message'      => $version === null
                ? trans('passport::app.publications.republish-noop')
                : trans('passport::app.publications.republished'),
            'redirect_url' => route('admin.catalog.passports.versions', $publication->id),
        ]);
    }
}
