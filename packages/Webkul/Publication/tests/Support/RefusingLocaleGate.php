<?php

namespace Webkul\Publication\Tests\Support;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PublicationGate;

class RefusingLocaleGate implements PublicationGate
{
    public static ?string $refusedLocaleCode = null;

    public function passes(Product $product, Channel $channel, Locale $locale): bool
    {
        return $locale->code !== self::$refusedLocaleCode;
    }
}
