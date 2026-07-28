<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;
use Webkul\Product\Repositories\ProductRepository;

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
    $relatedProduct = Product::factory()->withInitialValues()->create();

    // The product must link to the type for it to load on edit (only linked
    // types are sent to the view; the rest are attached via the picker).
    app(ProductAssociationRepository::class)->syncTypeWithData($product->id, $customType->id, [
        ['related_product_id' => $relatedProduct->id, 'position' => 1, 'additional_data' => null],
    ]);

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

it('sends only the types this product links to on edit -- unlinked active types are attached via the picker (scalability)', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);

    $unlinkedType = $associationTypeRepository->create([
        'code'            => 'unlinked_type_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Unlinked Type'],
        'fields'          => [],
    ]);

    $product = Product::factory()->withInitialValues()->create();

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    $response->assertViewHas('associationTypes', function ($associationTypes) use ($unlinkedType) {
        expect(collect($associationTypes)->pluck('code'))->not->toContain($unlinkedType->code);

        return true;
    });

    // It remains reachable through the async picker search endpoint.
    $search = $this->json('GET', route('admin.catalog.association_types.search', ['query' => 'Unlinked Type']))->assertOk();
    expect(collect($search->json('data'))->pluck('code'))->toContain($unlinkedType->code);
});

it('renders the dynamic association type panel, its field label, and the field-editor control for an existing link', function () {
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

    // The type's translated name and the field's translated label render as
    // plain, human-readable Blade output (stable regardless of how the Vue
    // data blob below happens to be JS-escaped).
    $response->assertSee('Bundle Kit');
    $response->assertSee('Quantity');

    // The field-editor control (`link-fields.blade.php`) renders entirely at
    // Vue runtime now: it loops the link's fields and binds each control's
    // name/value off the runtime `field` object (field definitions arrive via
    // the `association-types` prop below, not inlined per field).
    $response->assertSee('v-for="assocField in (type.fields || [])"', false);
    $response->assertSee('assocFieldName(type.code, index, assocField)', false);

    // The exact `associationTypes` array the controller built (Task 4) --
    // including this existing link's stored `additional_data` (quantity
    // "2") -- is embedded verbatim as the `v-product-links` component's
    // `association-types` prop. Reproducing Laravel's own `@json()`
    // directive (a plain `json_encode()` with its default HEX flags -- see
    // `Illuminate\View\Compilers\Concerns\CompilesJson`) on the SAME array
    // the view received proves, byte-for-byte, that the value survived all
    // the way into the page (not just into `assertViewHas`).
    $associationTypes = $response->original->getData()['associationTypes'];

    $response->assertSee(
        json_encode($associationTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
        false
    );

    $customTypePayload = collect($associationTypes)->firstWhere('code', $customType->code);
    $link = collect($customTypePayload['links'])->firstWhere('sku', $relatedProduct->sku);

    expect($link['additional_data']['common']['quantity'])->toBe('2');
});

it('persists a new link and its custom field value submitted through the unified associations payload via the real update route', function () {
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
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        'common' => ['quantity' => '5'],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    $link = $links->firstWhere('related_product_id', $relatedProduct->id);

    expect($link)->not->toBeNull()
        ->and($link->additional_data)->toBe(['common' => ['quantity' => '5']]);
});

it('never emits a bracket-array `name` for a checkbox association field (regression for the array-serialization bug)', function () {
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
                'code'        => 'flavor',
                'type'        => 'checkbox',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Flavor'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                    ['code' => 'blue', 'sort_order' => 2, 'en_US' => ['label' => 'Blue']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $customType->id, [
        [
            'related_product_id' => $relatedProduct->id,
            'position'           => 1,
            'additional_data'    => ['common' => ['flavor' => 'red']],
        ],
    ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    // Scope the assertion to the `v-product-links` text/x-template block --
    // several unrelated, legitimate features on this same page (media
    // uploaders, category tree checkboxes) do use `+ '[]'` on purpose.
    // Before the fix, `link-fields.blade.php`'s checkbox case bound
    // `::name="(assocFieldName(...)) + '[]'"`, so `+ '[]'` appeared for
    // EVERY checkbox option -- native `FormData(form)` submission (see
    // `onAjaxSubmit` in `packages/Webkul/Admin/src/Resources/assets/js/app.js`)
    // then serializes multiple checked boxes sharing that array-style name
    // into a PHP ARRAY for `additional_data.common.flavor`, which
    // `AssociationValidator::fieldTypeRules()`'s `'string'` rule rejects,
    // throwing a `ValidationException` that aborts the entire product save.
    $content = $response->getContent();
    $templateStart = strpos($content, 'id="v-product-links-template"');
    $templateEnd = strpos($content, '</script>', $templateStart);
    $template = substr($content, $templateStart, $templateEnd - $templateStart);

    expect($template)->not->toContain("+ '[]'");

    // The single authoritative hidden input for the field (carrying the real,
    // unified `associations[...]` name) reads the comma-joined value back via
    // `assocFieldValue()`. Field definitions now come from the `type.fields`
    // Vue loop variable (not inlined per-field JSON), so the call references
    // the runtime `field` object.
    expect($template)->toContain('assocFieldValue(link, assocField)');
});

it('persists a checkbox association field with multiple selected options as a comma-joined string via the real update route', function () {
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
                'code'        => 'flavor',
                'type'        => 'checkbox',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Flavor'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                    ['code' => 'blue', 'sort_order' => 2, 'en_US' => ['label' => 'Blue']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        // Comma-joined string, the shape the fixed checkbox
                        // markup's single hidden input now submits (never a
                        // PHP array).
                        'common' => ['flavor' => 'red,blue'],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    $link = $links->firstWhere('related_product_id', $relatedProduct->id);

    expect($link)->not->toBeNull()
        ->and($link->additional_data)->toBe(['common' => ['flavor' => 'red,blue']]);
});

it('rejects and never persists a checkbox association field submitted as a PHP array -- the exact shape the old buggy `name[]` checkbox markup produced', function () {
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
                'code'        => 'flavor',
                'type'        => 'checkbox',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Flavor'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                    ['code' => 'blue', 'sort_order' => 2, 'en_US' => ['label' => 'Blue']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        // The old, buggy shape: an array of checked option
                        // codes, exactly what `name="...[]"` + native
                        // `FormData(form)` submission used to produce.
                        'common' => ['flavor' => ['red', 'blue']],
                    ],
                ],
            ],
        ],
    ];

    // The controller's exception handler flashes a single `error` string
    // (not a Laravel `errors` MessageBag) and redirects back -- confirmed by
    // inspecting the actual session payload: `error` =>
    // "The additional data.common.flavor must be a string.", thrown by
    // `AssociationValidator::fieldTypeRules()`'s `'string'` rule via
    // `Validator::make(...)->validate()`.
    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('error', 'The additional data.common.flavor must be a string.');

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    expect($links->firstWhere('related_product_id', $relatedProduct->id))->toBeNull();
});

it('persists a select association field as a single option-code string via the real update route', function () {
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
                'code'        => 'color',
                'type'        => 'select',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Color'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                    ['code' => 'blue', 'sort_order' => 2, 'en_US' => ['label' => 'Blue']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        'common' => ['color' => 'red'],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    $link = $links->firstWhere('related_product_id', $relatedProduct->id);

    expect($link)->not->toBeNull()
        ->and($link->additional_data)->toBe(['common' => ['color' => 'red']]);
});

it('persists a multiselect association field as a comma-joined string via the real update route', function () {
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
                'code'        => 'tags',
                'type'        => 'multiselect',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Tags'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                    ['code' => 'blue', 'sort_order' => 2, 'en_US' => ['label' => 'Blue']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        'common' => ['tags' => 'red,blue'],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    $link = $links->firstWhere('related_product_id', $relatedProduct->id);

    expect($link)->not->toBeNull()
        ->and($link->additional_data)->toBe(['common' => ['tags' => 'red,blue']]);
});

it('persists a boolean association field as a "true"/"false" string via the real update route', function () {
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
                'code'        => 'is_featured',
                'type'        => 'boolean',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Is Featured'],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => [
                [
                    'sku'             => $relatedProduct->sku,
                    'additional_data' => [
                        'common' => ['is_featured' => 'true'],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $links = $productAssociationRepository->getLinksForProduct($product->id);

    $link = $links->firstWhere('related_product_id', $relatedProduct->id);

    expect($link)->not->toBeNull()
        ->and($link->additional_data)->toBe(['common' => ['is_featured' => 'true']]);
});

it('emits an `associations[<typeCode>][__present]=1` sentinel input for every RENDERED (linked) type, and none for types the product does not link to (scalability)', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);
    $productAssociationRepository = app(ProductAssociationRepository::class);

    // A custom type WITH an existing link.
    $customType = $associationTypeRepository->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [],
    ]);

    $product = Product::factory()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $customType->id, [
        ['related_product_id' => $relatedProduct->id, 'position' => 1, 'additional_data' => null],
    ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    // The panel is Vue-rendered, so the concrete per-type `__present` input name
    // is bound at runtime; the template carries the sentinel binding once,
    // emitted for every rendered (linked) type. It stays rendered even after the
    // user removes its last link client-side, so a present-but-empty payload
    // still prunes the type's rows on save (verified by the remove-last-link
    // test below).
    $response->assertSee(":name=\"'associations[' + type.code + '][__present]'\"", false);

    // Only linked types are sent to the view. The product links to the custom
    // type, so it is present; it does not link to `up_sells` (a legacy section),
    // so -- unlike the old design that rendered every active type -- `up_sells`
    // is absent (attachable on demand via the async picker), and no type's links
    // are ever pruned by simply not rendering it.
    $response->assertViewHas('associationTypes', function ($associationTypes) use ($customType) {
        $codes = collect($associationTypes)->pluck('code');

        expect($codes)->toContain($customType->code)
            ->and($codes)->not->toContain('up_sells');

        return true;
    });
});

it('prunes all `product_associations` rows for a CUSTOM type via the real update route when the submitted payload carries only the `__present` sentinel (Important 2, remove-last-link)', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);
    $productAssociationRepository = app(ProductAssociationRepository::class);

    $customType = $associationTypeRepository->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [],
    ]);

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $customType->id, [
        ['related_product_id' => $relatedProduct->id, 'position' => 1, 'additional_data' => null],
    ]);

    expect($productAssociationRepository->getLinksForProduct($product->id))->toHaveCount(1);

    // Emulates the browser submitting `links.blade.php`'s form after the
    // user removed the only link of this type in the UI: no numeric link
    // rows are present for the type, only the always-present sentinel.
    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            $customType->code => ['__present' => '1'],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    expect($productAssociationRepository->getLinksForProduct($product->id))->toHaveCount(0);
});

it('prunes rows AND clears the legacy JSON list for a LEGACY section (up_sells) via the real update route when present-but-empty (Important 2, remove-last-link)', function () {
    $this->loginAsAdmin();

    $associationTypeRepository = app(AssociationTypeRepository::class);
    $productAssociationRepository = app(ProductAssociationRepository::class);
    $productRepository = app(ProductRepository::class);

    $upSellsType = $associationTypeRepository->findByCode('up_sells');

    $product = Product::factory()->simple()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $upSellsType->id, [
        ['related_product_id' => $relatedProduct->id, 'position' => 1, 'additional_data' => null],
    ]);
    $product->values = array_merge($product->values, ['associations' => ['up_sells' => [$relatedProduct->sku]]]);
    $product->save();

    $data = [
        'sku'          => $product->sku,
        'values'       => $product->values,
        'associations' => [
            'up_sells' => ['__present' => '1'],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $reloaded = $productRepository->find($product->id);

    expect($reloaded->values['associations']['up_sells'] ?? null)->toBe([]);

    $row = DB::table('product_associations')
        ->where('product_id', $product->id)
        ->where('association_type_id', $upSellsType->id)
        ->first();

    expect($row)->toBeNull();
});

it('drops the redundant empty-value fallback hidden input for checkbox association fields, keeping only the authoritative comma-joined one (Minor 3)', function () {
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
                'code'        => 'flavor',
                'type'        => 'checkbox',
                'validation'  => null,
                'is_required' => 0,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Flavor'],
                'options'     => [
                    ['code' => 'red', 'sort_order' => 1, 'en_US' => ['label' => 'Red']],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->withInitialValues()->create();
    $relatedProduct = Product::factory()->withInitialValues()->create();

    $productAssociationRepository->syncTypeWithData($product->id, $customType->id, [
        [
            'related_product_id' => $relatedProduct->id,
            'position'           => 1,
            'additional_data'    => ['common' => ['flavor' => 'red']],
        ],
    ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    $content = $response->getContent();
    $templateStart = strpos($content, 'id="v-product-links-template"');
    $templateEnd = strpos($content, '</script>', $templateStart);
    $template = substr($content, $templateStart, $templateEnd - $templateStart);

    // Before the fix, the checkbox case emitted a redundant, always-present
    // `value=""` hidden fallback input (relying on PHP's last-key-wins for a
    // duplicate `name`) in addition to the authoritative comma-joined hidden
    // input. Only the latter should remain.
    expect($template)->not->toContain('value=""');
});
