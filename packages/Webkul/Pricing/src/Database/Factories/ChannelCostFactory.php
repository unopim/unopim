<?php

namespace Webkul\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Pricing\Models\ChannelCost;
use Webkul\Core\Models\Channel;

class ChannelCostFactory extends Factory
{
    protected $model = ChannelCost::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'commission_percentage' => $this->faker->randomFloat(2, 5, 30),
            'transaction_fee_percentage' => $this->faker->randomFloat(2, 1, 10),
            'listing_fee' => $this->faker->optional()->randomFloat(2, 0, 50),
            'monthly_subscription_fee' => $this->faker->optional()->randomFloat(2, 0, 200),
            'shipping_cost_per_zone' => null,
            'effective_from' => now()->subDays(rand(1, 30)),
            'effective_to' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subDay(),
            'effective_to' => now()->addMonth(),
        ]);
    }
}
