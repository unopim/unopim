<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Services\PassportFeature;
use Webkul\ProductPassport\Services\PassportPayloadBuilder;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\DataTransferObjects\PublicationContext;
use Webkul\Publication\Models\PublicationProxy;

class ProductPassportController extends Controller
{
    public function __construct(
        private readonly PassportFeature $feature,
        private readonly PassportReadinessService $readiness,
    ) {}

    public function show(Product $product): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        $channel = ChannelProxy::modelClass()::query()
            ->where('code', core()->getRequestedChannelCode())
            ->with('locales')
            ->firstOrFail();

        abort_unless($this->feature->enabledFor($channel), 404);

        $publication = PublicationProxy::modelClass()::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->where('type', 'dpp')
            ->with(['versions' => fn ($query) => $query->where('is_current', true)->with('locale')])
            ->first();

        $currentByLocale = $publication?->versions->keyBy('locale_id') ?? collect();

        $assessments = $this->readiness->assessMany($product, $channel, $channel->locales);

        $rows = $channel->locales->map(function ($locale) use ($currentByLocale, $assessments): array {
            $assessment = $assessments->get($locale->id);

            return [
                'locale_code'   => $locale->code,
                'version'       => $currentByLocale->get($locale->id)?->version,
                'ready'         => $assessment->isReady(),
                'missing_count' => $assessment->template === null ? null : $assessment->missingFields->count(),
            ];
        });

        return new JsonResponse(['rows' => $rows]);
    }

    /**
     * Renders the passport from the product's CURRENT data, admin-only, with
     * zero side effects: no Publication/version is created, no public URL is
     * minted, no view is counted, and no asset-disk write occurs. The preview
     * uses the SAME payload builder as real publishing (via a preview-mode
     * PublicationContext) so what the merchant sees matches what will be served.
     */
    public function preview(Request $request, Product $product): Response
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        abort_unless($this->feature->globallyEnabled(), 404);

        $channel = ChannelProxy::modelClass()::query()
            ->when(
                $request->filled('channel_id'),
                fn ($query) => $query->whereKey($request->integer('channel_id')),
                fn ($query) => $query->where('code', core()->getRequestedChannelCode()),
            )
            ->with('locales')
            ->firstOrFail();

        abort_unless($this->feature->enabledFor($channel), 404);

        $locale = $request->filled('locale_id')
            ? $channel->locales->firstWhere('id', $request->integer('locale_id'))
            : $channel->locales->first();

        abort_if($locale === null, 404);

        $context = new PublicationContext(
            uuid: 'preview',
            channel: $channel,
            locale: $locale,
            url: route('admin.catalog.products.passport.preview', [
                'product'    => $product->id,
                'channel_id' => $channel->id,
                'locale_id'  => $locale->id,
            ]),
            preview: true,
        );

        $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

        app()->setLocale($locale->code);

        $html = view('passport::public.passport', [
            'payload'   => $payload,
            'withdrawn' => false,
            'preview'   => true,
            'uuid'      => $context->uuid,
            'locale'    => $locale->code,
            'locales'   => $channel->locales,
        ])->render();

        return response($html)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }
}
