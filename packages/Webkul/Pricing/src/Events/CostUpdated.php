<?php

namespace Webkul\Pricing\Events;

use Webkul\Pricing\Models\ProductCost;

class CostUpdated
{
    public function __construct(
        public readonly ProductCost $productCost,
        public readonly float $previousAmount
    ) {}
}
