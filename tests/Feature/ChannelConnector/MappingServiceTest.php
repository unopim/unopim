<?php

use Illuminate\Support\Facades\Cache;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\ChannelConnector\Services\MappingService;
use Webkul\Tenant\Cache\TenantCache;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'mapping-svc-test',
        'name'         => 'Mapping Service Store',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => ['locale_mapping' => ['en_US' => 'en', 'ar_SA' => 'ar']],
        'status'       => 'connected',
    ]);
});

// ─── validateMappings() ──────────────────────────────────────────────

it('validates mappings with no errors for valid input', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export'],
        ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'both'],
        ['unopim_attribute_code' => 'price', 'channel_field' => 'price', 'direction' => 'import'],
    ]);

    expect($errors)->toBeEmpty();
});

it('detects duplicate attribute and field combinations', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export'],
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export'],
    ]);

    expect($errors)->toHaveKey('mappings.1');
    expect($errors['mappings.1'])->toContain('Duplicate');
});

it('reports error for empty unopim_attribute_code', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => '', 'channel_field' => 'sku', 'direction' => 'export'],
    ]);

    expect($errors)->toHaveKey('mappings.0');
    expect($errors['mappings.0'])->toContain('required');
});

it('reports error for empty channel_field', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => 'sku', 'channel_field' => '', 'direction' => 'export'],
    ]);

    expect($errors)->toHaveKey('mappings.0');
    expect($errors['mappings.0'])->toContain('required');
});

it('reports error for invalid direction value', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'bidirectional'],
    ]);

    expect($errors)->toHaveKey('mappings.0.direction');
    expect($errors['mappings.0.direction'])->toContain('Invalid');
});

it('allows valid directions export import and both', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export'],
        ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'import'],
        ['unopim_attribute_code' => 'price', 'channel_field' => 'price', 'direction' => 'both'],
    ]);

    expect($errors)->toBeEmpty();
});

it('returns empty errors for empty mappings array', function () {
    $service = app(MappingService::class);

    $errors = $service->validateMappings([]);

    expect($errors)->toBeEmpty();
});

// ─── saveMappings() ──────────────────────────────────────────────────

it('deletes existing mappings before creating new ones', function () {
    // Create initial mappings
    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'old_attr',
        'channel_field'         => 'old_field',
        'direction'             => 'export',
        'sort_order'            => 0,
    ]);

    $service = app(MappingService::class);

    $service->saveMappings($this->connector, [
        ['unopim_attribute_code' => 'new_attr', 'channel_field' => 'new_field', 'direction' => 'export'],
    ]);

    $mappings = ChannelFieldMapping::where('channel_connector_id', $this->connector->id)->get();

    expect($mappings)->toHaveCount(1);
    expect($mappings->first()->unopim_attribute_code)->toBe('new_attr');
});

it('creates mappings with correct attributes and sort order', function () {
    $service = app(MappingService::class);

    $service->saveMappings($this->connector, [
        ['unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export', 'transformation' => ['type' => 'uppercase']],
        ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'both', 'locale_mapping' => ['en_US' => 'en']],
        ['unopim_attribute_code' => 'price', 'channel_field' => 'price', 'direction' => 'import'],
    ]);

    $mappings = ChannelFieldMapping::where('channel_connector_id', $this->connector->id)
        ->orderBy('sort_order')
        ->get();

    expect($mappings)->toHaveCount(3);
    expect($mappings[0]->sort_order)->toBe(0);
    expect($mappings[1]->sort_order)->toBe(1);
    expect($mappings[2]->sort_order)->toBe(2);
    expect($mappings[0]->transformation)->toBe(['type' => 'uppercase']);
    expect($mappings[1]->locale_mapping)->toBe(['en_US' => 'en']);
    expect($mappings[2]->transformation)->toBeNull();
});

it('invalidates both mapping caches after save', function () {
    Cache::spy();

    $service = app(MappingService::class);
    $service->saveMappings($this->connector, []);

    $mappingsKey = TenantCache::key("channel_connector.{$this->connector->id}.mappings");
    $fieldsKey = TenantCache::key("channel_connector.{$this->connector->id}.channel_fields");

    Cache::shouldHaveReceived('forget')->with($mappingsKey);
    Cache::shouldHaveReceived('forget')->with($fieldsKey);
});

it('handles empty mappings array by clearing all', function () {
    // Create initial mappings
    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 0,
    ]);

    $service = app(MappingService::class);
    $service->saveMappings($this->connector, []);

    $count = ChannelFieldMapping::where('channel_connector_id', $this->connector->id)->count();

    expect($count)->toBe(0);
});

// ─── getLocaleMapping() ──────────────────────────────────────────────

it('extracts locale_mapping from connector settings', function () {
    $service = app(MappingService::class);

    $localeMapping = $service->getLocaleMapping($this->connector);

    expect($localeMapping)->toBe(['en_US' => 'en', 'ar_SA' => 'ar']);
});

it('returns empty array when locale_mapping not in settings', function () {
    $connector = ChannelConnector::create([
        'code'         => 'no-locale-mapping',
        'name'         => 'No Locale',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [],
        'status'       => 'connected',
    ]);

    $service = app(MappingService::class);

    $localeMapping = $service->getLocaleMapping($connector);

    expect($localeMapping)->toBe([]);
});

// ─── getMappingsForConnector() ───────────────────────────────────────

it('returns mappings from repository', function () {
    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 0,
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'name',
        'channel_field'         => 'title',
        'direction'             => 'both',
        'sort_order'            => 1,
    ]);

    $service = app(MappingService::class);
    $mappings = $service->getMappingsForConnector($this->connector);

    expect($mappings)->toHaveCount(2);
});
