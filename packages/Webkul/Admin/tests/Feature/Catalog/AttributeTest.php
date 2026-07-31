<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Enums\SwatchTypeEnum;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should return the Attribute index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.index.title'));
});

it('should render the swatch type field in the attribute quick create modal', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.index'));

    $response->assertStatus(200)
        ->assertSee('name="swatch_type"', false)
        ->assertSeeText(trans('admin::app.catalog.attributes.create.swatch'));
});

it('should render the creation-only toggles in the attribute quick create modal', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.index'));

    $response->assertStatus(200)
        ->assertSee('id="is_unique"', false)
        ->assertSee('id="value_per_locale"', false)
        ->assertSee('id="value_per_channel"', false);
});

it('should create a select attribute with swatch type and creation-only flags from the quick create payload', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code'              => 'quick_select_attribute',
        'type'              => 'select',
        'swatch_type'       => 'color',
        'value_per_locale'  => 1,
        'value_per_channel' => 1,
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $response->assertStatus(302);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'code'              => 'quick_select_attribute',
        'type'              => 'select',
        'swatch_type'       => 'color',
        'value_per_locale'  => 1,
        'value_per_channel' => 1,
    ]);
});

it('should return a clear reserved code message when the attribute code is reserved', function () {
    $this->loginAsAdmin();

    $response = postJson(route('admin.catalog.attributes.store'), [
        'code'       => 'type',
        'type'       => 'text',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath(
            'errors.code.0',
            trans('core::validation.not-supported', [
                'attribute'   => 'code',
                'unsupported' => implode(', ', NotSupportedAttributes::ATTRIBUTE_CODES),
            ])
        );
});

it('should create the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code' => 'testAttribute',
        'type' => 'text',
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $attribute);

    $attributeId = Attribute::where('code', $attribute['code'])?->value('id');

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', ['id' => $attributeId]));
});

it('should create the gallery type Attribute', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code' => 'testAttribute',
        'type' => 'gallery',
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $attribute);

    $attributeId = Attribute::where('code', $attribute['code'])?->value('id');

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', ['id' => $attributeId]));
});

it('should return the attribute datagrid', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.attributes.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should show the create attribute form', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.create'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.create.title'));
});

it('should show the edit attribute form', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.edit.title'));
});

it('should update the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'is_required' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the value per channel property in Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'              => $attribute->code,
        'type'              => $attribute->type,
        'is_required'       => 1,
        'value_per_channel' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the value per locale property in Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'is_required'      => 1,
        'value_per_locale' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the type and code of Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'             => 'updated'.$attribute->code,
        'type'             => $attribute->type == 'text' ? 'textarea' : 'text',
        'is_required'      => 1,
        'value_per_locale' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should delete the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = deleteJson(route('admin.catalog.attributes.delete', $attribute->id));

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.delete-success'),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
});

it('should not delete the sku Attribute', function () {
    $this->loginAsAdmin();

    $sku = Attribute::where('code', 'sku');

    $response = deleteJson(route('admin.catalog.attributes.delete', $sku->first()->id));

    $response->assertStatus(400)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.delete-failed'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $sku->first()->id]);
});

it('should mass delete attributes', function () {
    $this->loginAsAdmin();

    $attributes = Attribute::factory()->count(3)->create();

    $attributeIds = $attributes->pluck('id')->toArray();

    $response = postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $attributeIds]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
        ]);

    foreach ($attributeIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $id]);
    }
});

it('should not delete sku with mass delete attributes', function () {
    $this->loginAsAdmin();

    $attributes = Attribute::factory()->count(3)->create();
    $sku = Attribute::where('code', 'sku')?->first()?->id;

    $attributeIds = $attributes->pluck('id')->toArray();
    $attributeIds[] = $sku;

    $response = postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $attributeIds]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
        ]);

    foreach ($attributeIds as $id) {
        if ($id === $sku) {
            $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $id]);

            continue;
        }

        $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $id]);
    }
});

it('should skip attributes in use for configurable products during mass delete without leaking a database error', function () {
    $this->loginAsAdmin();

    $deletable = Attribute::factory()->count(2)->create();
    $inUse = Attribute::factory()->create();

    $product = Product::factory()->create();

    DB::table('product_super_attributes')->insert([
        'product_id'   => $product->id,
        'attribute_id' => $inUse->id,
    ]);

    $attributeIds = $deletable->pluck('id')->push($inUse->id)->toArray();

    $response = postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $attributeIds]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-partial', ['count' => 1]),
        ]);

    foreach ($deletable as $attribute) {
        $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
    }

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $inUse->id]);
});

it('should update attribute options', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);
    $swatchType = collect(SwatchTypeEnum::cases())->random()->value;
    $locales = Locale::where('status', 1)->limit(1);
    $locale = $locales->first()->toArray();
    $option = $attribute->options()->first();

    $updatedData = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => $swatchType,
        'options'     => [
            $option->id => [
                'isNew'         => false,
                'isDelete'      => false,
                $locale['code'] => ['label' => fake()->word()],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));
});

it('should delete an attribute option', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);
    $swatchType = collect(SwatchTypeEnum::cases())->random()->value;
    $option = $attribute->options()->first();

    $updatedData = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => $swatchType,
        'options'     => [
            $option->id => [
                'isNew'    => false,
                'isDelete' => true,
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);
    $this->assertDatabaseMissing('attribute_options', ['id' => $option->id]);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));
});

it('should enable Wysiwyg in textarea type attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'textarea']);

    $updatedData = [
        'code'           => $attribute->code,
        'type'           => $attribute->type,
        'enable_wysiwyg' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $updatedData);
});

it('should set enabled to ai_translate field at attribute creation', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code'            => 'testAttribute',
        'type'            => 'text',
        'value_per_locale'=> 1,
        'ai_translate'    => 1,
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', ['id' => Attribute::where('code', 'testAttribute')->first()->id]));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $attribute);
});

it('should not set enabled to ai_translate field at attribute creation', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code'         => 'testAttribute',
        'type'         => 'gallery',
        'ai_translate' => 1,
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', ['id' => Attribute::where('code', 'testAttribute')->first()->id]));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), [
        'code'         => 'testAttribute',
        'ai_translate' => 1,
    ]);
});

it('should update the ai_translate property in Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'is_required'      => 1,
        'ai_translate'     => ($attribute->type === 'text' || $attribute->type === 'textarea') && $attribute->value_per_locale == 1 ? 1 : 0,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $updatedData);
});

it('should create attribute option with color swatch_value for select type', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);
    $locale = Locale::where('status', 1)->first();
    $color = fake()->hexColor();
    $label = fake()->word();
    $optionCode = 'opt_'.Str::random(10);
    $data = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => 'color',
        'options'     => [
            'option_1' => [
                'isNew'         => true,
                'isDelete'      => false,
                'code'          => $optionCode,
                'swatch_value'  => $color,
                $locale->code   => ['label' => $label],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $data);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas('attributes', [
        'id'          => $attribute->id,
        'type'        => 'select',
        'swatch_type' => 'color',
    ]);
    $this->assertDatabaseHas('attribute_options', [
        'attribute_id' => $attribute->id,
        'swatch_value' => $color,
    ]);
});

it('should update text type swatch label per locale for select attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create([
        'type'        => 'select',
        'swatch_type' => 'text',
    ]);

    $option = $attribute->options()->first();
    $locale = Locale::where('status', 1)->first();
    $label = fake()->word();

    $data = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => 'text',
        'options'     => [
            $option->id => [
                'isNew'       => false,
                'isDelete'    => false,
                $locale->code => ['label' => $label],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $data);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas('attributes', [
        'id'          => $attribute->id,
        'type'        => 'select',
        'swatch_type' => 'text',
    ]);

    $this->assertDatabaseHas('attribute_option_translations', [
        'attribute_option_id' => $option->id,
        'locale'              => $locale->code,
        'label'               => $label,
    ]);

    $this->assertDatabaseMissing('attribute_options', [
        'id'           => $option->id,
        'swatch_value' => $label,
    ]);
});

it('should create attribute option with image swatch_value for select type', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create([
        'type'        => 'select',
        'swatch_type' => 'image',
    ]);

    $locale = Locale::where('status', 1)->first();

    $imageUrl = fake()->imageUrl(100, 100);
    $label = fake()->word();
    $optionCode = 'opt_'.Str::random(10);

    $data = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => 'image',
        'options'     => [
            'option_1' => [
                'isNew'        => true,
                'isDelete'     => false,
                'code'         => $optionCode,
                'swatch_value' => $imageUrl,
                $locale->code  => ['label' => $label],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $data);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas('attributes', [
        'id'          => $attribute->id,
        'type'        => 'select',
        'swatch_type' => 'image',
    ]);

    $this->assertDatabaseHas('attribute_options', [
        'attribute_id' => $attribute->id,
        'swatch_value' => $imageUrl,
    ]);
});

it('should not allow swatch_value for non-select attributes', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'text']);
    $locale = Locale::where('status', 1)->first();

    $color = fake()->hexColor();
    $label = fake()->word();
    $data = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => 'color',
        'options'     => [
            'option_1' => [
                'isNew'         => true,
                'isDelete'      => false,
                'swatch_value'  => $color,
                $locale->code   => ['label' => $label],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $data);

    $response->assertStatus(422)->assertJsonValidationErrors(['swatch_type']);

    $this->assertDatabaseMissing('attribute_options', [
        'attribute_id' => $attribute->id,
        'swatch_value' => $color,
    ]);
});

it('should render add-option modal with a unified layout for image swatch attributes', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create([
        'type'        => 'multiselect',
        'swatch_type' => 'image',
    ]);

    $response = get(route('admin.catalog.attributes.edit', $attribute->id));

    $response->assertOk();

    $content = $response->getContent();

    // The modal content should NOT have two separate disconnected grid containers
    // (a bare `<div class="grid">` for swatch followed by `<div class="grid grid-cols-3`)
    // They must be merged into a single unified wrapper so the layout is coherent.
    $this->assertStringNotContainsString(
        '</div>'.PHP_EOL.PHP_EOL.'                            <div class="grid grid-cols-3',
        $content,
        'The add-option modal must not split swatch input and form fields into two disconnected grid divs'
    );
});
