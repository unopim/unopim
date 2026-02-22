<?php

namespace Webkul\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Pricing\Models\ProductCost;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

class ProductCostFactory extends Factory
{
    protected $model = ProductCost::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'cost_type' => $this->faker->randomElement(['cogs', 'operational', 'shipping', 'overhead', 'marketing']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency_code' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'SAR', 'AED']),
            'effective_from' => now()->subDays(rand(1, 30)),
            'effective_to' => null,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => Admin::factory(),
        ];
    }

    public function cogs(): self
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'cogs',
        ]);
    }

    public function operational(): self
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'operational',
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subDay(),
            'effective_to' => now()->addMonth(),
        ]);
    }
}
