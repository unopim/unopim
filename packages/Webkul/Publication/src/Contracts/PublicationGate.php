<?php

namespace Webkul\Publication\Contracts;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

/**
 * Decides whether a product may publish a given channel/locale. A publication
 * type declares its own gate, so the engine never has to know what makes a
 * particular publication complete.
 */
interface PublicationGate
{
    public function passes(Product $product, Channel $channel, Locale $locale): bool;
}
