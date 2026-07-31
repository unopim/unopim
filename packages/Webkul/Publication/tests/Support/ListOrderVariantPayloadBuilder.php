<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Returns the same logical `fields` list content on every call, but with the
 * items shuffled into a different array order. Used to prove Publisher
 * sorts content lists by each item's `code` before hashing, so a reordered
 * (not re-content) list does not mint a spurious version.
 */
class ListOrderVariantPayloadBuilder implements PayloadBuilder
{
    public static string $order = 'a';

    public function build(Product $product, PublicationContext $context): array
    {
        $fields = [
            ['code' => 'alpha', 'value' => 1],
            ['code' => 'beta', 'value' => 2],
            ['code' => 'gamma', 'value' => 3],
        ];

        if (self::$order === 'b') {
            $fields = array_reverse($fields);
        }

        return [
            'fields' => $fields,
            'meta'   => [
                'built_at' => now()->toISOString(),
                'uuid'     => $context->uuid,
                'url'      => $context->url,
            ],
        ];
    }
}
