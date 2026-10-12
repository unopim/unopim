<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Minimal PayloadBuilder used by the Publisher tests, standing in for the
 * real DPP builder (Task 10). Stamps meta.built_at on every rebuild, and
 * meta.uuid/meta.url from the context, on purpose, so the checksum's
 * exclusion of the whole `meta` key is actually exercised.
 */
class StubPayloadBuilder implements PayloadBuilder
{
    public function build(Product $product, PublicationContext $context): array
    {
        return [
            'material' => data_get($product->values, "locale_specific.{$context->locale->code}.dpp_material_composition"),
            'meta'     => [
                'built_at' => now()->toISOString(),
                'uuid'     => $context->uuid,
                'url'      => $context->url,
            ],
        ];
    }
}
