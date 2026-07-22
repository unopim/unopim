<?php

namespace Webkul\Publication\Contracts;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

interface PayloadBuilder
{
    /**
     * Build the self-contained public payload for one product, channel and locale.
     *
     * The returned array must be renderable without any further catalog access.
     *
     * @return array<string, mixed>
     */
    public function build(Product $product, Channel $channel, Locale $locale): array;
}
