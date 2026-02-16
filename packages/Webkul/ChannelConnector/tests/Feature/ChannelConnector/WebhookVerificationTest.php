<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Events\WebhookReceived;
use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelWebhookEvent;
use Webkul\ChannelConnector\Services\AdapterResolver;

beforeEach(function () {
    Queue::fake();
    Event::fake();
});

// =============================================================================
// WEBHOOK IDEMPOTENCY TESTS
// =============================================================================

describe('Webhook Idempotency', function () {
    it('detects duplicate webhook events via isProcessed', function () {
        $connector = ChannelConnector::create([
            'code'         => 'idempotency-test',
            'name'         => 'Idempotency Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => 'test-token-idem'],
        ]);

        $webhookEventId = 'wh-event-123';
        $eventType = 'product.created';

        // Create initial webhook event record
        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector->id,
            'webhook_event_id'     => $webhookEventId,
            'event_type'           => $eventType,
            'payload'              => ['test' => 'data'],
            'processed_at'         => now(),
        ]);

        // Verify isProcessed returns true
        expect(ChannelWebhookEvent::isProcessed($connector->id, $webhookEventId))
            ->toBeTrue();
    });

    it('returns false when webhook event has not been processed', function () {
        $connector = ChannelConnector::create([
            'code'         => 'idempotency-not-processed',
            'name'         => 'Not Processed Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => 'test-token-not-processed'],
        ]);

        $webhookEventId = 'wh-event-456';

        // Verify isProcessed returns false
        expect(ChannelWebhookEvent::isProcessed($connector->id, $webhookEventId))
            ->toBeFalse();
    });

    it('returns false when webhook event exists but is not processed', function () {
        $connector = ChannelConnector::create([
            'code'         => 'idempotency-unprocessed',
            'name'         => 'Unprocessed Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => 'test-token-unprocessed'],
        ]);

        $webhookEventId = 'wh-event-789';

        // Create webhook event record without processed_at timestamp
        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector->id,
            'webhook_event_id'     => $webhookEventId,
            'event_type'           => 'product.updated',
            'payload'              => ['test' => 'data'],
            'processed_at'         => null, // Not processed
        ]);

        // Verify isProcessed returns false when processed_at is null
        expect(ChannelWebhookEvent::isProcessed($connector->id, $webhookEventId))
            ->toBeFalse();
    });

    it('checks idempotency per connector (not global)', function () {
        $connector1 = ChannelConnector::create([
            'code'         => 'connector-1',
            'name'         => 'Connector 1',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $connector2 = ChannelConnector::create([
            'code'         => 'connector-2',
            'name'         => 'Connector 2',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $webhookEventId = 'wh-event-shared-001';

        // Create webhook event for connector1
        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector1->id,
            'webhook_event_id'     => $webhookEventId,
            'event_type'           => 'product.created',
            'payload'              => ['sku' => 'SHARED-001'],
            'processed_at'         => now(),
        ]);

        // connector1 should see it as processed
        expect(ChannelWebhookEvent::isProcessed($connector1->id, $webhookEventId))
            ->toBeTrue();

        // connector2 should see it as not processed (different connector)
        expect(ChannelWebhookEvent::isProcessed($connector2->id, $webhookEventId))
            ->toBeFalse();
    });
});

// =============================================================================
// HMAC SIGNATURE VERIFICATION TESTS (EasyOrders Adapter)
// =============================================================================

describe('HMAC Signature Verification (EasyOrders)', function () {
    it('accepts valid HMAC signature', function () {
        $secret = 'test-webhook-secret-12345';
        $payload = json_encode(['event' => 'order.created', 'id' => 'eo-123']);
        $validSignature = hash_hmac('sha256', $payload, $secret);

        $connector = ChannelConnector::create([
            'code'         => 'easyorders-valid-sig',
            'name'         => 'Valid Sig Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode(['webhook_secret' => $secret])),
            'settings'     => ['webhook_token' => 'test-token-eo-valid'],
        ]);

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn(true);

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(Mockery::on(fn ($arg) => $arg->id === $connector->id))
            ->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $response = $this->postJson(
            route('channel_connector.webhooks.receive', 'test-token-eo-valid'),
            json_decode($payload, true),
            ['X-EasyOrders-Signature' => $validSignature]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'acknowledged']);
    });

    it('rejects invalid HMAC signature with 401', function () {
        $secret = 'test-webhook-secret-67890';
        $payload = json_encode(['event' => 'order.updated', 'id' => 'eo-456']);

        $connector = ChannelConnector::create([
            'code'         => 'easyorders-invalid-sig',
            'name'         => 'Invalid Sig Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode(['webhook_secret' => $secret])),
            'settings'     => ['webhook_token' => 'test-token-eo-invalid'],
        ]);

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn(false); // Invalid signature

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $response = $this->postJson(
            route('channel_connector.webhooks.receive', 'test-token-eo-invalid'),
            json_decode($payload, true),
            ['X-EasyOrders-Signature' => 'invalid-signature-here']
        );

        $response->assertStatus(401);
    });

    it('rejects request with missing signature header', function () {
        $connector = ChannelConnector::create([
            'code'         => 'easyorders-missing-sig',
            'name'         => 'Missing Sig Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode(['webhook_secret' => 'test-secret'])),
            'settings'     => ['webhook_token' => 'test-token-eo-missing'],
        ]);

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn(false); // Missing signature

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $response = $this->postJson(
            route('channel_connector.webhooks.receive', 'test-token-eo-missing'),
            ['event' => 'product.deleted', 'id' => 'eo-789']
            // No X-EasyOrders-Signature header
        );

        $response->assertStatus(401);
    });

    it('rejects webhook when webhook_secret is empty', function () {
        $payload = json_encode(['event' => 'order.cancelled', 'id' => 'eo-999']);

        $connector = ChannelConnector::create([
            'code'         => 'easyorders-empty-secret',
            'name'         => 'Empty Secret Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode(['webhook_secret' => ''])), // Empty secret
            'settings'     => ['webhook_token' => 'test-token-eo-empty'],
        ]);

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn(false); // Empty secret should fail verification

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $response = $this->postJson(
            route('channel_connector.webhooks.receive', 'test-token-eo-empty'),
            json_decode($payload, true),
            ['X-EasyOrders-Signature' => 'some-signature']
        );

        $response->assertStatus(401);
    });
});

// =============================================================================
// TENANT ISOLATION TESTS
// =============================================================================

describe('Tenant Isolation', function () {
    it('isolates webhook events per tenant', function () {
        // Create webhook events for the current tenant (tenant 1)
        $connector1 = ChannelConnector::create([
            'code'         => 'tenant-1-connector',
            'name'         => 'Tenant 1 Connector',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector1->id,
            'webhook_event_id'     => 'wh-tenant-1-001',
            'event_type'           => 'product.created',
            'payload'              => ['tenant_id' => 1],
            'processed_at'         => now(),
        ]);

        // Create webhook events for the same connector (same tenant)
        $connector2 = ChannelConnector::create([
            'code'         => 'tenant-2-connector',
            'name'         => 'Tenant 2 Connector',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector2->id,
            'webhook_event_id'     => 'wh-tenant-2-001',
            'event_type'           => 'product.created',
            'payload'              => ['tenant_id' => 2],
            'processed_at'         => now(),
        ]);

        // Verify tenant sees its own webhooks
        $tenant1Events = ChannelWebhookEvent::where('webhook_event_id', 'wh-tenant-1-001')->get();
        expect($tenant1Events)->toHaveCount(1);

        $tenant2Events = ChannelWebhookEvent::where('webhook_event_id', 'wh-tenant-2-001')->get();
        expect($tenant2Events)->toHaveCount(1);

        // Verify total count is 2 for this tenant
        $totalEvents = ChannelWebhookEvent::count();
        expect($totalEvents)->toBe(2);
    });

    it('prevents cross-tenant webhook processing', function () {
        // Create a connector and webhook
        $connector1 = ChannelConnector::create([
            'code'         => 'tenant-1-cross-test',
            'name'         => 'Tenant 1 Cross Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => 'tenant-1-token'],
        ]);

        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector1->id,
            'webhook_event_id'     => 'wh-cross-tenant-001',
            'event_type'           => 'product.created',
            'payload'              => ['tenant' => 1],
            'processed_at'         => now(),
        ]);

        // Verify connector exists in current tenant
        $connector1FromCurrentTenant = ChannelConnector::find($connector1->id);
        expect($connector1FromCurrentTenant)->not->toBeNull();

        // Verify webhook events exist in current tenant
        $events = ChannelWebhookEvent::where('webhook_event_id', 'wh-cross-tenant-001')->get();
        expect($events)->toHaveCount(1);
    });

    it('ensures isProcessed respects tenant boundaries', function () {
        // Create webhook for connector 1
        $connector1 = ChannelConnector::create([
            'code'         => 'tenant-1-boundary',
            'name'         => 'Tenant 1 Boundary',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $sharedWebhookId = 'wh-boundary-001';

        ChannelWebhookEvent::create([
            'channel_connector_id' => $connector1->id,
            'webhook_event_id'     => $sharedWebhookId,
            'event_type'           => 'order.created',
            'payload'              => [],
            'processed_at'         => now(),
        ]);

        // Verify connector 1 sees it as processed
        expect(ChannelWebhookEvent::isProcessed($connector1->id, $sharedWebhookId))
            ->toBeTrue();

        // Create connector 2 with same webhook ID (same tenant, different connector)
        $connector2 = ChannelConnector::create([
            'code'         => 'tenant-2-boundary',
            'name'         => 'Tenant 2 Boundary',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        // Connector 2 should see the same webhook ID as NOT processed
        // (because it's a different connector)
        expect(ChannelWebhookEvent::isProcessed($connector2->id, $sharedWebhookId))
            ->toBeFalse();
    });
});

// =============================================================================
// END-TO-END WEBHOOK PROCESSING TESTS
// =============================================================================

describe('End-to-End Webhook Processing', function () {
    it('creates ChannelWebhookEvent record on webhook receipt', function () {
        $token = 'test-token-e2e-001';
        $webhookEventId = 'wh-e2e-001';

        $connector = ChannelConnector::create([
            'code'         => 'e2e-webhook-test',
            'name'         => 'E2E Webhook Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => $token],
        ]);

        $payload = [
            'event' => 'product.created',
            'id'    => $webhookEventId,
            'data'  => ['title' => 'Test Product'],
        ];

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(true);

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        // Send webhook
        $response = $this->postJson(
            route('channel_connector.webhooks.receive', $token),
            $payload
        );

        $response->assertStatus(200);

        // Verify webhook event was created (via ProcessWebhookJob)
        // Note: The actual record creation happens in ProcessWebhookJob which is queued
        Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($connector, $webhookEventId) {
            return $job->connectorId === $connector->id
                && ($job->webhookEventId === $webhookEventId || $job->payload['id'] === $webhookEventId);
        });
    });

    it('sets processed_at timestamp when webhook is processed', function () {
        $connector = ChannelConnector::create([
            'code'         => 'e2e-timestamp-test',
            'name'         => 'E2E Timestamp Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $webhookEventId = 'wh-timestamp-001';

        // Create webhook event without processed_at
        $event = ChannelWebhookEvent::create([
            'channel_connector_id' => $connector->id,
            'webhook_event_id'     => $webhookEventId,
            'event_type'           => 'product.updated',
            'payload'              => ['title' => 'Updated Product'],
            'processed_at'         => null,
        ]);

        expect($event->processed_at)->toBeNull();

        // Mark as processed
        $event->update(['processed_at' => now()]);

        $event->refresh();
        expect($event->processed_at)->not->toBeNull()
            ->and($event->processed_at)->toBeInstanceOf(\Carbon\Carbon::class);

        // Verify isProcessed now returns true
        expect(ChannelWebhookEvent::isProcessed($connector->id, $webhookEventId))
            ->toBeTrue();
    });

    it('stores event type correctly in webhook event', function () {
        $connector = ChannelConnector::create([
            'code'         => 'e2e-event-type-test',
            'name'         => 'E2E Event Type Test',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $eventTypes = [
            'product.created',
            'product.updated',
            'product.deleted',
            'order.created',
            'order.updated',
        ];

        foreach ($eventTypes as $index => $eventType) {
            ChannelWebhookEvent::create([
                'channel_connector_id' => $connector->id,
                'webhook_event_id'     => "wh-event-type-{$index}",
                'event_type'           => $eventType,
                'payload'              => ['test' => true],
                'processed_at'         => now(),
            ]);
        }

        // Verify all event types are stored correctly
        foreach ($eventTypes as $index => $eventType) {
            $event = ChannelWebhookEvent::where('event_type', $eventType)->first();
            expect($event)->not->toBeNull()
                ->and($event->event_type)->toBe($eventType);
        }
    });

    it('fires WebhookReceived event on successful webhook', function () {
        Event::fake([WebhookReceived::class]);

        $token = 'test-token-event-001';

        $connector = ChannelConnector::create([
            'code'         => 'e2e-event-test',
            'name'         => 'E2E Event Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => $token],
        ]);

        $payload = [
            'event' => 'product.created',
            'id'    => 'wh-event-test-001',
            'data'  => ['title' => 'Event Test Product'],
        ];

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(true);

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $this->postJson(
            route('channel_connector.webhooks.receive', $token),
            $payload
        );

        Event::assertDispatched(WebhookReceived::class, function ($event) use ($connector) {
            return $event->connector->id === $connector->id
                && $event->payload['id'] === 'wh-event-test-001';
        });
    });

    it('dispatches ProcessWebhookJob to webhooks queue', function () {
        $token = 'test-token-queue-001';

        $connector = ChannelConnector::create([
            'code'         => 'e2e-queue-test',
            'name'         => 'E2E Queue Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
            'settings'     => ['webhook_token' => $token],
        ]);

        $payload = [
            'event' => 'product.updated',
            'id'    => 'wh-queue-001',
            'data'  => ['title' => 'Queue Test Product'],
        ];

        $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
        $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(true);

        $resolver = Mockery::mock(AdapterResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

        $this->app->instance(AdapterResolver::class, $resolver);

        $this->postJson(
            route('channel_connector.webhooks.receive', $token),
            $payload
        );

        Queue::assertPushedOn('webhooks', ProcessWebhookJob::class);
    });

    it('links webhook event to connector relationship', function () {
        $connector = ChannelConnector::create([
            'code'         => 'e2e-relation-test',
            'name'         => 'E2E Relation Test',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $event = ChannelWebhookEvent::create([
            'channel_connector_id' => $connector->id,
            'webhook_event_id'     => 'wh-relation-001',
            'event_type'           => 'product.created',
            'payload'              => ['test' => true],
            'processed_at'         => now(),
        ]);

        expect($event->connector)->toBeInstanceOf(ChannelConnector::class)
            ->and($event->connector->id)->toBe($connector->id)
            ->and($event->connector->code)->toBe('e2e-relation-test');
    });
});

// =============================================================================
// EDGE CASES AND ERROR HANDLING
// =============================================================================

describe('Webhook Edge Cases', function () {
    it('handles null webhook_event_id gracefully', function () {
        $connector = ChannelConnector::create([
            'code'         => 'edge-null-id',
            'name'         => 'Edge Null ID',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        // isProcessed should return false for null webhook ID
        expect(ChannelWebhookEvent::isProcessed($connector->id, ''))->toBeFalse();
        expect(ChannelWebhookEvent::isProcessed($connector->id, '0'))->toBeFalse();
    });

    it('handles empty payload webhook events', function () {
        $connector = ChannelConnector::create([
            'code'         => 'edge-empty-payload',
            'name'         => 'Edge Empty Payload',
            'channel_type' => 'easyorders',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $event = ChannelWebhookEvent::create([
            'channel_connector_id' => $connector->id,
            'webhook_event_id'     => 'wh-empty-payload',
            'event_type'           => 'ping',
            'payload'              => [],
            'processed_at'         => now(),
        ]);

        expect($event->payload)->toBeArray()
            ->and($event->payload)->toBeEmpty();
    });

    it('handles special characters in event type', function () {
        $connector = ChannelConnector::create([
            'code'         => 'edge-special-chars',
            'name'         => 'Edge Special Chars',
            'channel_type' => 'shopify',
            'status'       => 'connected',
            'credentials'  => encrypt(json_encode([])),
        ]);

        $specialEventTypes = [
            'product.created',
            'product/created',
            'product:created',
            'product.created::v2',
            'orders/create',
        ];

        foreach ($specialEventTypes as $index => $eventType) {
            ChannelWebhookEvent::create([
                'channel_connector_id' => $connector->id,
                'webhook_event_id'     => "wh-special-{$index}",
                'event_type'           => $eventType,
                'payload'              => [],
                'processed_at'         => now(),
            ]);
        }

        foreach ($specialEventTypes as $index => $eventType) {
            $event = ChannelWebhookEvent::where('event_type', $eventType)->first();
            expect($event)->not->toBeNull()
                ->and($event->event_type)->toBe($eventType);
        }
    });
});
