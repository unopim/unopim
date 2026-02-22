<?php

namespace Webkul\Order\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\UnifiedOrder;

class UnifiedOrderFactory extends Factory
{
    protected $model = UnifiedOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'order_number' => 'ORD-' . $this->faker->unique()->numberBetween(10000, 99999),
            'external_id' => $this->faker->optional()->uuid(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'order_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->optional()->phoneNumber(),
            'shipping_address' => $this->faker->optional()->address(),
            'billing_address' => $this->faker->optional()->address(),
            'total_amount' => $this->faker->randomFloat(2, 10, 5000),
            'subtotal' => function (array $attributes) {
                return $attributes['total_amount'] * 0.9;
            },
            'tax_amount' => function (array $attributes) {
                return $attributes['total_amount'] * 0.1;
            },
            'shipping_amount' => $this->faker->randomFloat(2, 0, 50),
            'discount_amount' => $this->faker->randomFloat(2, 0, 100),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'SAR']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'cash_on_delivery']),
            'shipping_method' => $this->faker->randomElement(['standard', 'express', 'overnight']),
            'internal_notes' => $this->faker->optional()->paragraph(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'additional_data' => [],
            'synced_at' => $this->faker->optional()->dateTime(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the order has been synced.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'synced_at' => now(),
        ]);
    }

    /**
     * Indicate that the order has an external ID.
     */
    public function withExternalId(string $externalId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'external_id' => $externalId ?? $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the order is from a specific channel.
     */
    public function forChannel(int $channelId): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_id' => $channelId,
        ]);
    }

    /**
     * Indicate that the order has a high value.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $this->faker->randomFloat(2, 5000, 50000),
        ]);
    }

    /**
     * Indicate that the order has a low value.
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }
}
