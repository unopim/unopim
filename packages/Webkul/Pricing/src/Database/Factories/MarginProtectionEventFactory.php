<?php

namespace Webkul\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Pricing\Models\MarginProtectionEvent;
use Webkul\Product\Models\Product;
use Webkul\Core\Models\Channel;
use Webkul\User\Models\Admin;

class MarginProtectionEventFactory extends Factory
{
    protected $model = MarginProtectionEvent::class;

    public function definition(): array
    {
        $proposedPrice = $this->faker->randomFloat(2, 50, 500);
        $breakEvenPrice = $proposedPrice * 0.8;
        $minimumMarginPrice = $proposedPrice * 0.85;
        $targetMarginPrice = $proposedPrice * 1.2;

        return [
            'product_id' => Product::factory(),
            'channel_id' => Channel::factory(),
            'event_type' => $this->faker->randomElement(['blocked', 'approved', 'rejected']),
            'proposed_price' => $proposedPrice,
            'break_even_price' => $breakEvenPrice,
            'minimum_margin_price' => $minimumMarginPrice,
            'target_margin_price' => $targetMarginPrice,
            'margin_percentage' => $this->faker->randomFloat(2, 5, 40),
            'minimum_margin_percentage' => 20.00,
            'reason' => $this->faker->sentence(),
            'approved_by' => null,
            'approved_at' => null,
            'expires_at' => null,
        ];
    }

    public function blocked(): self
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'blocked',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'approved',
            'approved_by' => Admin::factory(),
            'approved_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
