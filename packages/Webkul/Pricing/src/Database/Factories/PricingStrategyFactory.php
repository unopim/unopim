<?php

namespace Webkul\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Pricing\Models\PricingStrategy;

class PricingStrategyFactory extends Factory
{
    protected $model = PricingStrategy::class;

    public function definition(): array
    {
        return [
            'scope_type' => 'global',
            'scope_id' => 0,
            'minimum_margin_percentage' => 20.00,
            'target_margin_percentage' => 30.00,
            'premium_margin_percentage' => 40.00,
            'psychological_pricing' => $this->faker->boolean(),
            'round_to' => $this->faker->randomElement(['0.99', '0.95', '0.00', 'none']),
            'is_active' => true,
            'priority' => 100,
        ];
    }

    public function global(): self
    {
        return $this->state(fn (array $attributes) => [
            'scope_type' => 'global',
            'scope_id' => 0,
        ]);
    }

    public function channel(int $channelId): self
    {
        return $this->state(fn (array $attributes) => [
            'scope_type' => 'channel',
            'scope_id' => $channelId,
        ]);
    }

    public function product(int $productId): self
    {
        return $this->state(fn (array $attributes) => [
            'scope_type' => 'product',
            'scope_id' => $productId,
        ]);
    }

    public function category(int $categoryId): self
    {
        return $this->state(fn (array $attributes) => [
            'scope_type' => 'category',
            'scope_id' => $categoryId,
        ]);
    }
}
