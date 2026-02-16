<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\MappingService;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('auto-suggests common field pairs', function () {
    $connector = ChannelConnector::create([
        'code'        => 'suggest', 'name' => 'Suggest', 'channel_type' => 'shopify',
        'credentials' => ['access_token' => 'test'], 'status' => 'connected',
    ]);

    $service = app(MappingService::class);
    $suggestions = $service->getAutoSuggestedMappings($connector);

    $skuMapping = collect($suggestions)->firstWhere('unopim_attribute_code', 'sku');
    expect($skuMapping)->not->toBeNull();
    expect($skuMapping['channel_field'])->toBe('sku');
});
