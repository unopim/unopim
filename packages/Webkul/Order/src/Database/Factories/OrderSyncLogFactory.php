<?php

namespace Webkul\Order\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;

class OrderSyncLogFactory extends Factory
{
    protected $model = OrderSyncLog::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'running', 'completed', 'failed']);
        $startedAt = $this->faker->dateTimeBetween('-7 days', 'now');

        return [
            'tenant_id' => 1,
            'channel_id' => Channel::factory(),
            'status' => $status,
            'resource_type' => $this->faker->randomElement(['order', 'product', 'customer']),
            'resource_id' => $this->faker->optional()->numberBetween(1, 1000),
            'direction' => $this->faker->randomElement(['import', 'export']),
            'started_at' => $startedAt,
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween($startedAt, 'now') : null,
            'failed_at' => $status === 'failed' ? $this->faker->dateTimeBetween($startedAt, 'now') : null,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'items_processed' => $status === 'completed' ? $this->faker->numberBetween(0, 1000) : 0,
            'items_failed' => $status === 'failed' ? $this->faker->numberBetween(1, 100) : 0,
            'metadata' => [
                'synced_count' => $this->faker->numberBetween(0, 100),
                'created_count' => $this->faker->numberBetween(0, 50),
                'updated_count' => $this->faker->numberBetween(0, 50),
                'skipped_count' => $this->faker->numberBetween(0, 10),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the sync is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * Indicate that the sync is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * Indicate that the sync is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = now()->subMinutes($this->faker->numberBetween(1, 30));

            return [
                'status' => 'completed',
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes($this->faker->numberBetween(1, 20)),
                'failed_at' => null,
                'error_message' => null,
                'items_processed' => $this->faker->numberBetween(10, 1000),
            ];
        });
    }

    /**
     * Indicate that the sync failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = now()->subMinutes($this->faker->numberBetween(1, 30));

            return [
                'status' => 'failed',
                'started_at' => $startedAt,
                'completed_at' => null,
                'failed_at' => $startedAt->copy()->addMinutes($this->faker->numberBetween(1, 10)),
                'error_message' => $this->faker->randomElement([
                    'Connection timeout',
                    'Authentication failed',
                    'Invalid API response',
                    'Rate limit exceeded',
                    'Network error',
                ]),
                'items_failed' => $this->faker->numberBetween(1, 100),
            ];
        });
    }

    /**
     * Indicate that the sync is for a specific channel.
     */
    public function forChannel(int $channelId): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_id' => $channelId,
        ]);
    }

    /**
     * Indicate that the sync is for orders.
     */
    public function forOrders(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'order',
        ]);
    }

    /**
     * Indicate that the sync is an import.
     */
    public function import(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'import',
        ]);
    }

    /**
     * Indicate that the sync is an export.
     */
    public function export(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'export',
        ]);
    }
}
