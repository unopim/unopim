<?php

namespace Webkul\Publication\Contracts;

use Webkul\Product\Models\Product;
use Webkul\Publication\DataTransferObjects\PublicationContext;

interface PayloadBuilder
{
    /**
     * Build the self-contained public payload for one product in the
     * channel/locale/publication identity carried by $context.
     *
     * The returned array must be renderable without any further catalog
     * access. Any identity fields stamped from $context (uuid, canonical
     * url, ...) must be placed under the `meta` key: Publisher::publish()
     * excludes the whole `meta` key from the content checksum, so stamping
     * identity anywhere else would make the first publish and every later
     * rebuild hash differently even when the content itself is unchanged.
     *
     * @return array<string, mixed>
     */
    public function build(Product $product, PublicationContext $context): array;
}
