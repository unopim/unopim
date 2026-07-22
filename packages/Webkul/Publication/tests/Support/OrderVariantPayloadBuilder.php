<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Returns the same logical content as `StubPayloadBuilder`, but with array
 * keys inserted in a different order depending on `$order`. Used to prove
 * `Publisher` canonicalises key order before hashing, rather than trusting
 * insertion order.
 */
class OrderVariantPayloadBuilder implements PayloadBuilder
{
    public static string $order = 'a';

    public function build(Product $product, PublicationContext $context): array
    {
        $attributes = self::$order === 'a'
            ? ['weight' => '1.2kg', 'color' => 'green']
            : ['color' => 'green', 'weight' => '1.2kg'];

        $payload = self::$order === 'a'
            ? ['material' => 'Recycled cotton', 'attributes' => $attributes]
            : ['attributes' => $attributes, 'material' => 'Recycled cotton'];

        $payload['meta'] = [
            'built_at' => now()->toISOString(),
            'uuid'     => $context->uuid,
            'url'      => $context->url,
        ];

        return $payload;
    }
}
