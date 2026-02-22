<?php

namespace Webkul\Order\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Product\Models\Product;

class UnifiedOrderItemFactory extends Factory
{
    protected $model = UnifiedOrderItem::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 5, 500);
        $quantity = $this->faker->numberBetween(1, 10);
        $costBasis = $this->faker->randomFloat(2, $price * 0.4, $price * 0.8);

        return [
            'tenant_id' => 1,
            'unified_order_id' => UnifiedOrder::factory(),
            'product_id' => Product::factory(),
            'sku' => 'SKU-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->words(3, true),
            'price' => $price,
            'quantity' => $quantity,
            'cost_basis' => $costBasis,
            'tax_amount' => $price * $quantity * 0.1,
            'discount_amount' => $this->faker->optional()->randomFloat(2, 0, $price * $quantity * 0.2),
            'additional_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the item has no cost basis.
     */
    public function noCostBasis(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_basis' => null,
        ]);
    }

    /**
     * Indicate that the item has a high margin.
     */
    public function highMargin(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            return [
                'cost_basis' => $price * 0.3, // 70% margin
            ];
        });
    }

    /**
     * Indicate that the item has a low margin.
     */
    public function lowMargin(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            return [
                'cost_basis' => $price * 0.9, // 10% margin
            ];
        });
    }

    /**
     * Indicate that the item is for a specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Indicate that the item has a specific SKU.
     */
    public function withSku(string $sku): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => $sku,
        ]);
    }

    /**
     * Indicate that the item has a large quantity.
     */
    public function largeQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(50, 500),
        ]);
    }

    /**
     * Indicate that the item has a discount.
     */
    public function withDiscount(float $percentage = null): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $price = $attributes['price'];
            $quantity = $attributes['quantity'];
            $discountPercent = $percentage ?? $this->faker->randomFloat(2, 0.05, 0.5);

            return [
                'discount_amount' => $price * $quantity * $discountPercent,
            ];
        });
    }
}
