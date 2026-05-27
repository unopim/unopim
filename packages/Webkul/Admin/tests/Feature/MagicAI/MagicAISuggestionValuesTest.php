<?php

use Webkul\Attribute\Models\Attribute;

it('should return attribute suggestions on the magic AI @ mention lookup (Issue #717)', function () {
    $this->loginAsAdmin();

    Attribute::factory()->create(['code' => 'color_'.uniqid(), 'type' => 'text']);

    $response = $this->getJson(route('admin.magic_ai.suggestion_values', ['query' => 'color', 'entity_name' => 'attribute']));

    $response->assertOk();
    expect(is_array($response->json()))->toBeTrue();
    expect(count($response->json()))->toBeGreaterThan(0);
});

it('should return suggestions for an empty query (initial @ press)', function () {
    $this->loginAsAdmin();

    $response = $this->getJson(route('admin.magic_ai.suggestion_values', ['query' => '', 'entity_name' => 'attribute']));

    $response->assertOk();
    expect(count($response->json()))->toBeGreaterThan(0);
});
