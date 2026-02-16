<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('should show the shopify settings page', function () {
    $this->loginAsAdmin();

    get(route('admin.shopify.settings', 2))
        ->assertStatus(200)
        ->assertSeeText(trans('shopify::app.shopify.export.setting.title'));
});

it('should update the export setting mapping', function () {
    $this->loginAsAdmin();

    $exportSetting = [
        'enable_tags_attribute' => 1,
        'tagSeprator'           => ':',
        'option_name_label'     => 1,
    ];

    post(route('shopify.export-settings.create'), $exportSetting)
        ->assertStatus(302)
        ->assertSessionHas(['success']);
});

it('should show the shopify export-mappings page', function () {
    $this->loginAsAdmin();

    get(route('admin.shopify.export-mappings', 1))
        ->assertStatus(200)
        ->assertSeeText(trans('shopify::app.shopify.export.mapping.title'));
});

it('should update the export mapping', function () {
    $this->loginAsAdmin();

    $name = Attribute::factory()->create(['type' => 'text']);
    $description = Attribute::factory()->create(['type' => 'textarea']);
    $price = Attribute::factory()->create(['type' => 'price']);
    $weight = Attribute::factory()->create(['type' => 'text']);

    $exportMapping = [
        'title'           => $name->code,
        'descriptionHtml' => $description->code,
        'price'           => $price->code,
        'weight'          => $weight->code,
    ];

    post(route('shopify.export-mappings.create'), $exportMapping)
        ->assertStatus(302)
        ->assertSessionHas(['success']);
});

it('should update the export mapping with metafield mapping', function () {
    $this->loginAsAdmin();

    $name = Attribute::factory()->create(['type' => 'text']);
    $description = Attribute::factory()->create(['type' => 'textarea']);
    $price = Attribute::factory()->create(['type' => 'price']);
    $weight = Attribute::factory()->create(['type' => 'text']);

    $exportMapping = [
        'title'               => $name->code,
        'descriptionHtml'     => $description->code,
        'price'               => $price->code,
        'weight'              => $weight->code,
    ];

    post(route('shopify.export-mappings.create'), $exportMapping)
        ->assertStatus(302)
        ->assertSessionHas(['success']);
});
