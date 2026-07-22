<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;

/**
 * Minimal PayloadBuilder used by the Publisher tests, standing in for the
 * real DPP builder (Task 10). Stamps meta.built_at on every rebuild, on
 * purpose, so the checksum's exclusion of that key is actually exercised.
 */
class StubPayloadBuilder implements PayloadBuilder
{
    public function build(Product $product, Channel $channel, Locale $locale): array
    {
        return [
            'material' => data_get($product->values, "locale_specific.{$locale->code}.dpp_material_composition"),
            'meta'     => [
                'built_at' => now()->toISOString(),
            ],
        ];
    }
}
