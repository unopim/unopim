<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\ProductPassport\Http\Requests\BulkPublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\MassPublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\MassTransitionPassportRequest;
use Webkul\ProductPassport\Http\Requests\PublishPassportRequest;
use Webkul\ProductPassport\Http\Requests\RepublishPassportVersionRequest;
use Webkul\ProductPassport\Jobs\BulkPublishPassportsJob;
use Webkul\ProductPassport\Jobs\BulkTransitionPassportsJob;
use Webkul\ProductPassport\Services\PassportFeature;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Enums\PublishAttemptStatus;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationPublishAttempt;
use Webkul\Publication\Models\PublicationPublishAttemptProxy;
use Webkul\Publication\Models\PublicationVersionProxy;
use Webkul\Publication\Services\Publisher;

class PublicationController extends Controller
{
    public function __construct(
        private readonly PassportFeature $feature,
        private readonly PassportReadinessService $readiness,
    ) {}

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
        return resolve(PassportFeature::class)->globallyEnabled();
    }

    /**
     * One job dispatch per admin action, not one per locale — the job
     * itself loops requested locales.
     */
    public function publish(PublishPassportRequest $request, Product $product): JsonResponse
    {
        $channel = ChannelProxy::modelClass()::findOrFail($request->integer('channel_id'));

        abort_unless(
            $this->feature->enabledFor($channel),
            403,
            trans('passport::app.catalog.products.edit.passport.publishing-disabled'),
        );

        $localeIds = $request->collect('locale_ids')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $locales = $channel->locales()
            ->whereIn('locales.id', $localeIds)
            ->get();

        $assessments = $this->readiness->assessMany($product, $channel, $locales);

        $blockedLocales = $locales
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
                    'ready' => $assessment->isReady(),
                ];
            })
            ->where('ready', false)
            ->values()
            ->map(function (array $locale): array {
                unset($locale['ready']);

                return $locale;
            });

        if ($blockedLocales->isNotEmpty()) {
            return new JsonResponse([
                'message'         => trans('passport::app.catalog.products.edit.passport.publish-blocked'),
                'errors'          => [
                    'locale_ids' => [trans('passport::app.catalog.products.edit.passport.publish-blocked')],
                ],
                'blocked_locales' => $blockedLocales,
            ], 422);
        }

        if (! $this->publicationAcceptsPublishing($product->id, $channel->id)) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.publish-withdrawn'),
            ], 422);
        }

        $adminId = auth()->guard('admin')->id();

        $attempt = PublicationPublishAttemptProxy::modelClass()::create([
            'product_id'      => $product->id,
            'channel_id'      => $channel->id,
            'type'            => 'dpp',
            'locale_ids'      => $localeIds,
            'status'          => PublishAttemptStatus::Queued,
            'requested_by_id' => $adminId,
        ]);

        PublishPassportForProductChannelJob::dispatch(
            $product->id,
            $channel->id,
            'dpp',
            $localeIds,
            $adminId,
            $attempt->id,
        );

        return new JsonResponse([
            'message'     => trans('passport::app.publications.publish-queued'),
            'attempt_url' => route('admin.catalog.passports.publish_attempt', $attempt->id),
        ]);
    }

    /**
     * What the queued publish did, for the panel that is waiting on it: the
     * settled status plus the live version of every locale it covered.
     */
    public function publishAttempt(PublicationPublishAttempt $attempt): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        $versions = PublicationProxy::modelClass()::query()
            ->where('product_id', $attempt->product_id)
            ->where('channel_id', $attempt->channel_id)
            ->where('type', $attempt->type)
            ->first()
            ?->versions()
            ->where('is_current', true)
            ->whereIn('locale_id', $attempt->locale_ids)
            ->get()
            ->keyBy('locale_id');

        return new JsonResponse([
            'status'   => $attempt->status->value,
            'refused'  => $attempt->wasRefused(),
            'settled'  => $attempt->status->isSettled(),
            'locales'  => collect($attempt->locale_ids)
                ->map(fn (int $localeId): array => [
                    'locale_id'    => $localeId,
                    'version'      => $versions?->get($localeId)?->version,
                    'published_at' => (string) $versions?->get($localeId)?->published_at,
                    'published'    => in_array($localeId, $attempt->publishedLocaleIds(), true),
                ])
                ->values(),
        ]);
    }

    /**
     * Publish every locale of the requested channel for each selected product,
     * one job dispatch per product (each job loops the channel's locales).
     *
     * A product whose passport is withdrawn or redacted is left out: publishing
     * it would be refused downstream, so reporting it as queued would be a lie.
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
            $this->feature->enabledFor($channel),
            403,
            trans('passport::app.catalog.products.edit.passport.publishing-disabled'),
        );

        $localeIds = $channel->locales->pluck('id')->all();

        $productIds = $request->collect('indices')->map(fn ($id): int => (int) $id);

        $offline = Publication::query()
            ->whereIn('product_id', $productIds)
            ->where('channel_id', $channel->id)
            ->where('type', 'dpp')
            ->whereNotIn('status', PublicationStatus::publishable())
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id);

        $publishable = $productIds->reject(fn (int $productId): bool => $offline->contains($productId))->values();

        if ($publishable->isEmpty()) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.publish-none-publishable', ['count' => $offline->count()]),
            ], 422);
        }

        $adminId = auth()->guard('admin')->id();

        foreach ($publishable as $productId) {
            PublishPassportForProductChannelJob::dispatch($productId, $channel->id, 'dpp', $localeIds, $adminId);
        }

        return new JsonResponse([
            'message' => $offline->isEmpty()
                ? trans('passport::app.publications.mass-publish.queued', ['count' => $publishable->count()])
                : trans('passport::app.publications.bulk-publish-queued-skipped', ['count' => $offline->count()]),
        ]);
    }

    /**
     * Publish the selected passport rows across each publication's own channel
     * locales. Fans out through a chunking orchestrator job so the request
     * returns immediately regardless of how many rows were selected.
     *
     * Answers 422 when nothing in the selection can publish, so an all-withdrawn
     * selection reads as the no-op it is rather than a queued success.
     */
    public function bulkPublish(BulkPublishPassportRequest $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            abort(404);
        }

        $publicationIds = $request->collect('indices')->map(fn ($id): int => (int) $id)->all();

        $skipped = Publication::query()
            ->whereIn('id', $publicationIds)
            ->whereNotIn('status', PublicationStatus::publishable())
            ->count();

        if ($skipped === count($publicationIds)) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.publish-none-publishable', ['count' => $skipped]),
            ], 422);
        }

        BulkPublishPassportsJob::dispatch($publicationIds, auth()->guard('admin')->id());

        return new JsonResponse([
            'message' => $skipped === 0
                ? trans('passport::app.publications.bulk-publish-queued')
                : trans('passport::app.publications.bulk-publish-queued-skipped', ['count' => $skipped]),
        ]);
    }

    /**
     * Returns a withdrawn passport to Published, making every locale it already
     * holds reachable again. Refuses on anything but a withdrawn publication —
     * redaction is one-way.
     */
    public function reinstate(Publication $publication, Publisher $publisher): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.withdraw'), 403);

        try {
            $publisher->reinstate($publication);
        } catch (InvalidPublicationTransitionException) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.reinstate-invalid'),
            ], 422);
        }

        return new JsonResponse([
            'message' => trans('passport::app.publications.reinstated'),
        ]);
    }

    /**
     * Withdraw or reinstate a grid selection. Fans out through a chunking job so
     * an arbitrarily large selection never runs in the web request, and so each
     * row still transitions through Publisher (events, counters) rather than a
     * blind mass UPDATE.
     */
    public function massTransition(MassTransitionPassportRequest $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            abort(404);
        }

        $publicationIds = $request->collect('indices')->map(fn ($id): int => (int) $id)->all();

        $target = PublicationStatus::from($request->string('value')->value());

        BulkTransitionPassportsJob::dispatch($publicationIds, $target);

        return new JsonResponse([
            'message' => trans(
                $target === PublicationStatus::Withdrawn
                    ? 'passport::app.publications.mass-withdraw-queued'
                    : 'passport::app.publications.mass-reinstate-queued',
                ['count' => count($publicationIds)],
            ),
        ]);
    }

    /**
     * Whether the product's passport for this channel can still take new versions.
     * A passport that does not exist yet is publishable — publish() creates it.
     */
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
     * No `redirect_url`: the grid short-circuits on one, skipping the success
     * flash and reloading the very page the action was fired from. Returning the
     * message alone lets the row refresh in place with the confirmation shown.
     */
    public function withdraw(Publication $publication, Publisher $publisher): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.withdraw'), 403);

        try {
            $publisher->withdraw($publication);
        } catch (InvalidPublicationTransitionException) {
            return new JsonResponse([
                'message' => trans('passport::app.publications.withdraw-invalid'),
            ], 422);
        }

        return new JsonResponse([
            'message' => trans('passport::app.publications.withdrawn'),
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
