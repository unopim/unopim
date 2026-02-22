<?php

namespace Webkul\Order\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderWebhook;

class OrderWebhookFactory extends Factory
{
    protected $model = OrderWebhook::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'channel_code' => $this->faker->randomElement(['salla', 'shopify', 'woocommerce', 'magento']),
            'event_types' => $this->faker->randomElements(
                ['order.created', 'order.updated', 'order.cancelled', 'order.shipped', 'order.delivered'],
                $this->faker->numberBetween(1, 3)
            ),
            'secret_key' => Str::random(32),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'delivery_attempts' => $this->faker->numberBetween(0, 10),
            'last_delivery_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'additional_headers' => [],
            'retry_config' => [
                'max_attempts' => 3,
                'retry_delay' => 300,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the webhook is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the webhook is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the webhook is for Salla.
     */
    public function forSalla(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_code' => 'salla',
            'event_types' => ['order.created', 'order.updated', 'order.cancelled'],
        ]);
    }

    /**
     * Indicate that the webhook is for Shopify.
     */
    public function forShopify(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_code' => 'shopify',
            'event_types' => ['orders/create', 'orders/updated', 'orders/cancelled'],
        ]);
    }

    /**
     * Indicate that the webhook is for WooCommerce.
     */
    public function forWooCommerce(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_code' => 'woocommerce',
            'event_types' => ['order.created', 'order.updated', 'order.deleted'],
        ]);
    }

    /**
     * Indicate that the webhook is for a specific channel.
     */
    public function forChannel(int $channelId): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_id' => $channelId,
        ]);
    }

    /**
     * Indicate that the webhook has been delivered recently.
     */
    public function recentlyDelivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_delivery_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'delivery_attempts' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the webhook has many failed deliveries.
     */
    public function manyFailedDeliveries(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_attempts' => $this->faker->numberBetween(10, 100),
            'last_delivery_at' => now()->subHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the webhook has a specific secret key.
     */
    public function withSecret(string $secret): static
    {
        return $this->state(fn (array $attributes) => [
            'secret_key' => $secret,
        ]);
    }

    /**
     * Indicate that the webhook listens to specific events.
     */
    public function forEvents(array $events): static
    {
        return $this->state(fn (array $attributes) => [
            'event_types' => $events,
        ]);
    }

    /**
     * Indicate that the webhook has custom headers.
     */
    public function withHeaders(array $headers): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_headers' => $headers,
        ]);
    }
}
