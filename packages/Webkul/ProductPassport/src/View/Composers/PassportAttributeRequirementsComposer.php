<?php

namespace Webkul\ProductPassport\View\Composers;

use Illuminate\View\View;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Services\PassportAttributeRequirements;

class PassportAttributeRequirementsComposer
{
    public function __construct(
        private readonly PassportAttributeRequirements $requirements,
    ) {}

    public function compose(View $view): void
    {
        $data = $view->getData();
        $product = $data['product'] ?? null;
        $channel = $data['currentChannel'] ?? core()->getRequestedChannel();
        $locale = $data['currentLocale'] ?? core()->getRequestedLocale();

        if (! $product instanceof Product || ! $channel instanceof Channel || ! $locale instanceof Locale) {
            $view->with('attributeRequirements', []);

            return;
        }

        $view->with('attributeRequirements', $this->requirements->for($product, $channel, $locale));
    }
}
