<?php

namespace Webkul\Publication\Contracts;

use Webkul\Product\Models\Product;
use Webkul\Publication\DataTransferObjects\PublicationContext;

interface PayloadBuilder
{
    /**
     * Builds the self-contained public payload for one product, renderable
     * without further catalog access. Identity fields stamped from $context
     * (uuid, canonical url, ...) must go under the `meta` key — Publisher::publish()
     * excludes `meta` from the content checksum, so stamping identity elsewhere
     * would make an unchanged rebuild hash differently.
     *
     * @return array{meta?: array<string, mixed>, ...}
     */
    public function build(Product $product, PublicationContext $context): array;
}
