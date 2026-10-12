<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Stands in for Task 10's real builder: stamps a `documents` entry pointing
 * at a path a test has already placed on the asset disk, exactly the shape
 * the real builder will produce (final, already-copied, servable path).
 */
class DocumentStubPayloadBuilder implements PayloadBuilder
{
    public static string $documentPath = '';

    public function build(Product $product, PublicationContext $context): array
    {
        return [
            'documents' => [
                ['code' => 'dpp_certificates', 'label' => 'Certificate', 'path' => self::$documentPath],
            ],
            'meta' => ['uuid' => $context->uuid, 'url' => $context->url],
        ];
    }
}
