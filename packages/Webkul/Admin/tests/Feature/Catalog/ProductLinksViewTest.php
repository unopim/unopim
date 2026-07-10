<?php

use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;

it('supplies active association types with fields and this product\'s existing links (with additional_data) to the product edit view', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);
    $productAssociationRepository = app(ProductAssociationRepository::class);

    $customType = $associationTypeRepository->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);

    $product = Product::factory()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $customType->id, [
        [
            'related_product_id'  => $relatedProduct->id,
            'position'            => 1,
            'additional_data'     => ['common' => ['quantity' => '2']],
        ],
    ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    $response->assertViewHas('associationTypes', function ($associationTypes) use ($customType, $relatedProduct) {
        $customTypePayload = collect($associationTypes)->firstWhere('code', $customType->code);

        expect($customTypePayload)->not->toBeNull()
            ->and($customTypePayload['name'])->toBe('Bundle Kit')
            ->and(collect($customTypePayload['fields'])->pluck('code')->all())->toContain('quantity');

        $quantityField = collect($customTypePayload['fields'])->firstWhere('code', 'quantity');

        expect($quantityField['type'])->toBe('text')
            ->and($quantityField['is_required'])->toBeTrue();

        $link = collect($customTypePayload['links'])->firstWhere('sku', $relatedProduct->sku);

        expect($link)->not->toBeNull()
            ->and($link['additional_data']['common']['quantity'])->toBe('2');

        return true;
    });
});

it('still renders the product edit page (no broken include) when no custom association type links exist', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->simple()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.edit.links.title'));
});

it('exposes only active (status = 1) association type fields, filtering out disabled ones', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);

    $customType = $associationTypeRepository->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'active_field',
                'type'        => 'text',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Active Field'],
            ],
            [
                'code'        => 'disabled_field',
                'type'        => 'text',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 0,
                'section'     => 'left',
                'en_US'       => ['name' => 'Disabled Field'],
            ],
        ],
    ]);

    $product = Product::factory()->withInitialValues()->create();

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    $response->assertViewHas('associationTypes', function ($associationTypes) use ($customType) {
        $customTypePayload = collect($associationTypes)->firstWhere('code', $customType->code);

        expect($customTypePayload)->not->toBeNull();

        $fieldCodes = collect($customTypePayload['fields'])->pluck('code')->all();

        expect($fieldCodes)->toContain('active_field')
            ->and($fieldCodes)->not->toContain('disabled_field');

        return true;
    });
});
