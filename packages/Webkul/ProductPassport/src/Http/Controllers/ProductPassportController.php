<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\Publication\Models\PublicationProxy;

class ProductPassportController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.view'), 403);

        $channel = ChannelProxy::modelClass()::query()
            ->where('code', core()->getRequestedChannelCode())
            ->with('locales')
            ->firstOrFail();

        $publication = PublicationProxy::modelClass()::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->where('type', 'dpp')
            ->with(['versions' => fn ($query) => $query->where('is_current', true)->with('locale')])
            ->first();

        $currentByLocale = $publication?->versions->keyBy('locale_id') ?? collect();

        $rows = $channel->locales->map(fn ($locale): array => [
            'locale_code' => $locale->code,
            'version'     => $currentByLocale->get($locale->id)?->version,
        ]);

        return new JsonResponse(['rows' => $rows]);
    }
}
