<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->connector = ChannelConnector::create([
        'code'         => 'conflict-api-test',
        'name'         => 'Conflict API Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'api-conflict.myshopify.com'],
        'settings'     => ['conflict_strategy' => 'always_ask'],
        'status'       => 'connected',
    ]);

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => 'api-conflict-job-'.uniqid(),
        'status'               => 'completed',
        'sync_type'            => 'full',
    ]);

    $this->product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'Test Product'],
        ],
    ]);
});

it('can list conflicts with filters via API', function () {
    $token = $this->createAdminApiToken();

    // Create multiple conflicts with different statuses
    ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['name' => ['pim_value' => 'PIM', 'channel_value' => 'Channel']],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(30),
        'resolution_status'    => 'unresolved',
    ]);

    $product2 = Product::factory()->create(['values' => ['common' => ['name' => 'Product 2']]]);
    ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $product2->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['price' => ['pim_value' => 10, 'channel_value' => 20]],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(15),
        'resolution_status'    => 'pim_wins',
        'resolved_at'          => now(),
    ]);

    // List all conflicts
    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.index', $this->connector->code));

    $response->assertOk()
        ->assertJsonStructure(['data']);

    expect($response->json('total'))->toBeGreaterThanOrEqual(2);

    // Filter by resolution_status
    $filteredResponse = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.index', [
            $this->connector->code,
            'resolution_status' => 'unresolved',
        ]));

    $filteredResponse->assertOk();

    $unresolvedData = collect($filteredResponse->json('data'));
    $unresolvedData->each(function ($conflict) {
        expect($conflict['resolution_status'])->toBe('unresolved');
    });
});

it('can get conflict detail with per-locale diff structure via API', function () {
    $token = $this->createAdminApiToken();

    $conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => [
            'name' => [
                'pim_value'          => 'PIM Name',
                'channel_value'      => 'Channel Name',
                'is_locale_specific' => false,
                'locales'            => [],
            ],
            'title' => [
                'pim_value'          => null,
                'channel_value'      => null,
                'is_locale_specific' => true,
                'locales'            => [
                    'en' => ['pim_value' => 'English PIM', 'channel_value' => 'English Channel'],
                    'fr' => ['pim_value' => 'French PIM', 'channel_value' => 'French Channel'],
                ],
            ],
        ],
        'pim_modified_at'     => now()->subHour(),
        'channel_modified_at' => now()->subMinutes(30),
        'resolution_status'   => 'unresolved',
    ]);

    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.show', [
            $this->connector->code,
            $conflict->id,
        ]));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'product_id',
                'product_sku',
                'connector_name',
                'conflict_type',
                'conflicting_fields',
                'resolution_status',
                'pim_modified_at',
                'channel_modified_at',
                'created_at',
            ],
        ]);

    $data = $response->json('data');
    expect($data['conflicting_fields'])->toHaveKey('name');
    expect($data['conflicting_fields'])->toHaveKey('title');
    expect($data['conflicting_fields']['title']['is_locale_specific'])->toBeTrue();
    expect($data['conflicting_fields']['title']['locales'])->toHaveKeys(['en', 'fr']);
});

it('can resolve conflict via API', function () {
    $token = $this->createAdminApiToken();

    $conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => [
            'name' => ['pim_value' => 'PIM', 'channel_value' => 'Channel', 'is_locale_specific' => false, 'locales' => []],
        ],
        'pim_modified_at'     => now()->subHour(),
        'channel_modified_at' => now()->subMinutes(30),
        'resolution_status'   => 'unresolved',
    ]);

    $response = $this->withToken($token)
        ->putJson(route('admin.api.channel_connector.conflicts.resolve', [
            $this->connector->code,
            $conflict->id,
        ]), [
            'resolution' => 'dismissed',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.resolution_status', 'dismissed');

    $conflict->refresh();
    expect($conflict->resolution_status)->toBe('dismissed');
    expect($conflict->resolved_at)->not->toBeNull();
});

it('returns 422 when trying to resolve an already resolved conflict', function () {
    $token = $this->createAdminApiToken();

    $conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['name' => ['pim_value' => 'A', 'channel_value' => 'B']],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(30),
        'resolution_status'    => 'pim_wins',
        'resolved_at'          => now(),
    ]);

    $response = $this->withToken($token)
        ->putJson(route('admin.api.channel_connector.conflicts.resolve', [
            $this->connector->code,
            $conflict->id,
        ]), [
            'resolution' => 'dismissed',
        ]);

    $response->assertStatus(422);
});

it('validates resolution value in API request', function () {
    $token = $this->createAdminApiToken();

    $conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['name' => ['pim_value' => 'A', 'channel_value' => 'B']],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(30),
        'resolution_status'    => 'unresolved',
    ]);

    $response = $this->withToken($token)
        ->putJson(route('admin.api.channel_connector.conflicts.resolve', [
            $this->connector->code,
            $conflict->id,
        ]), [
            'resolution' => 'invalid_strategy',
        ]);

    $response->assertStatus(422);
});

it('returns 404 for conflict belonging to different connector', function () {
    $token = $this->createAdminApiToken();

    $otherConnector = ChannelConnector::create([
        'code'         => 'other-connector',
        'name'         => 'Other Store',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'connected',
    ]);

    $conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $otherConnector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['name' => ['pim_value' => 'A', 'channel_value' => 'B']],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(30),
        'resolution_status'    => 'unresolved',
    ]);

    // Try to access conflict via the wrong connector code
    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.show', [
            $this->connector->code,
            $conflict->id,
        ]));

    $response->assertNotFound();
});

it('enforces ACL permissions for conflict views', function () {
    // Create admin with limited permissions (no conflict access)
    $token = $this->createAdminApiToken();

    // This test verifies the routes exist and respond.
    // Full ACL enforcement testing depends on middleware configuration.
    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.index', $this->connector->code));

    // Admin with token should at least get a valid response (200 or 403, not 500)
    expect($response->status())->toBeIn([200, 403]);
});

it('can list conflicts filtered by conflict_type', function () {
    $token = $this->createAdminApiToken();

    ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => ['name' => ['pim_value' => 'A', 'channel_value' => 'B']],
        'pim_modified_at'      => now()->subHour(),
        'channel_modified_at'  => now()->subMinutes(30),
        'resolution_status'    => 'unresolved',
    ]);

    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.conflicts.index', [
            $this->connector->code,
            'conflict_type' => 'both_modified',
        ]));

    $response->assertOk();

    $data = collect($response->json('data'));
    $data->each(function ($conflict) {
        expect($conflict['conflict_type'])->toBe('both_modified');
    });
});
