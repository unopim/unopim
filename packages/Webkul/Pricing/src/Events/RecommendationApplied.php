<?php

namespace Webkul\Pricing\Events;

class RecommendationApplied
{
    public function __construct(
        public readonly int $productId,
        public readonly ?int $channelId,
        public readonly string $tier,
        public readonly float $price
    ) {}
}
