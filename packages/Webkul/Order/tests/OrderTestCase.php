<?php

namespace Webkul\Order\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Models\OrderWebhook;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

class OrderTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed database with essential data
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
    }

    /**
     * Create a test order
     */
    protected function createTestOrder(array $attributes = []): UnifiedOrder
    {
        return UnifiedOrder::factory()->create($attributes);
    }

    /**
     * Create a test order with items
     */
    protected function createOrderWithItems(int $itemCount = 3, array $orderAttributes = []): UnifiedOrder
    {
        $order = $this->createTestOrder($orderAttributes);

        UnifiedOrderItem::factory()
            ->count($itemCount)
            ->for($order)
            ->create();

        return $order->fresh('orderItems');
    }

    /**
     * Create a test channel
     */
    protected function createTestChannel(array $attributes = []): Channel
    {
        return Channel::factory()->create(array_merge([
            'code' => 'test-channel',
            'name' => 'Test Channel',
            'type' => 'salla',
        ], $attributes));
    }

    /**
     * Create admin user with order permissions
     */
    protected function createAdminWithOrderPermissions(array $permissions = []): Admin
    {
        $admin = Admin::factory()->create();

        // Default permissions
        $defaultPermissions = [
            'order.orders.view',
            'order.orders.create',
            'order.orders.edit',
            'order.orders.delete',
        ];

        $allPermissions = array_merge($defaultPermissions, $permissions);

        foreach ($allPermissions as $permission) {
            bouncer()->allow($admin)->to($permission);
        }

        return $admin;
    }

    /**
     * Create admin user with all order permissions
     */
    protected function createAdminWithAllOrderPermissions(): Admin
    {
        $admin = Admin::factory()->create();

        $permissions = [
            'order.orders.view',
            'order.orders.create',
            'order.orders.edit',
            'order.orders.delete',
            'order.orders.mass-update',
            'order.orders.mass-delete',
            'order.orders.export',
            'order.sync.view',
            'order.sync.manual-sync',
            'order.sync.schedule',
            'order.sync.logs',
            'order.sync.retry',
            'order.sync.settings',
            'order.profitability.view',
            'order.profitability.view-costs',
            'order.profitability.view-margins',
            'order.profitability.channel-comparison',
            'order.profitability.export',
            'order.profitability.settings',
            'order.webhooks.view',
            'order.webhooks.create',
            'order.webhooks.edit',
            'order.webhooks.delete',
            'order.webhooks.test',
            'order.webhooks.logs',
            'order.webhooks.retry',
            'order.settings.general',
            'order.settings.sync',
        ];

        foreach ($permissions as $permission) {
            bouncer()->allow($admin)->to($permission);
        }

        return $admin;
    }

    /**
     * Create test webhook
     */
    protected function createTestWebhook(array $attributes = []): OrderWebhook
    {
        return OrderWebhook::factory()->create($attributes);
    }

    /**
     * Create test sync log
     */
    protected function createTestSyncLog(array $attributes = []): OrderSyncLog
    {
        return OrderSyncLog::factory()->create($attributes);
    }

    /**
     * Generate HMAC signature for webhook
     */
    protected function generateWebhookSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Assert order profitability calculation
     */
    protected function assertProfitabilityCalculation(UnifiedOrder $order, float $expectedProfit, float $expectedMargin): void
    {
        $profitability = $order->calculateProfitability();

        $this->assertIsArray($profitability);
        $this->assertArrayHasKey('total_profit', $profitability);
        $this->assertArrayHasKey('margin_percentage', $profitability);
        $this->assertEquals($expectedProfit, $profitability['total_profit'], 'Total profit mismatch', 0.01);
        $this->assertEquals($expectedMargin, $profitability['margin_percentage'], 'Margin percentage mismatch', 0.01);
    }

    /**
     * Mock channel adapter
     */
    protected function mockChannelAdapter(string $adapterClass, array $mockMethods = []): void
    {
        $mock = $this->mock($adapterClass);

        foreach ($mockMethods as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue);
        }
    }
}
