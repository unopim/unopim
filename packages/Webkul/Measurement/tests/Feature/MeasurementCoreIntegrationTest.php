<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

function measurementAttribute(): Attribute
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'units' => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter']],
        ],
    ]);

    $attribute = Attribute::factory()->create([
        'code' => 'depth_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return $attribute;
}

it('renders the bulk edit page with the measurement spreadsheet component', function () {
    $attribute = measurementAttribute();

    $product = Product::factory()->withInitialValues()->create();

    $this->withSession([
        'bulk_edit_product_ids'   => [$product->id],
        'bulk_edit_attribute_ids' => [$attribute->id],
    ])
        ->get(route('admin.catalog.products.bulkedit'))
        ->assertOk()
        ->assertSee('v-spreadsheet-measurement-template', false)
        ->assertSee("case 'measurement': return 'v-spreadsheet-measurement';", false);
});

it('builds a measurement column as a price-type filter with unit options and the numeric operators', function () {
    $attribute = measurementAttribute();

    $datagrid = app(ProductDataGrid::class);

    $method = new ReflectionMethod($datagrid, 'buildColumnDefinition');
    $method->setAccessible(true);
    $column = $method->invoke($datagrid, $attribute);

    expect($column['type'])->toBe('price')
        ->and($column['attribute_type'])->toBe('measurement')
        ->and($column['options'])->not->toBeEmpty();

    expect(collect($column['operators'])->pluck('value')->all())
        ->toContain('eq', 'gt', 'gte', 'lt', 'lte', 'within_range', 'blank', 'not_blank');
});

it('injects the measurement panel into the attribute edit page', function () {
    $attribute = measurementAttribute();

    $this->get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk();
});

it('renders the measurement family index page', function () {
    $this->get(route('admin.measurement.families.index'))->assertOk();
});

it('emits the same measurement column through the filter-picker controller', function () {
    $attribute = measurementAttribute();

    $controller = app(ProductController::class);

    $method = new ReflectionMethod($controller, 'buildColumnDefinition');
    $method->setAccessible(true);
    $column = $method->invoke($controller, $attribute);

    expect($column['type'])->toBe('price')
        ->and($column['attribute_type'])->toBe('measurement')
        ->and(collect($column['operators'])->pluck('value')->all())->toContain('within_range', 'gte');
});

it('surfaces the measurement precision section in the system settings hub', function () {
    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(trans('measurement::app.config.catalog.measurement.title'), false);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.measurement']))
        ->assertOk()
        ->assertSee(trans('measurement::app.config.catalog.measurement.precision.strategy-round'), false)
        ->assertSee(trans('measurement::app.config.catalog.measurement.precision.strategy-trim'), false)
        ->assertSee('strategy', false)
        ->assertSee('amount', false)
        ->assertSee('base', false)
        ->assertSee('max_value:10', false);
});

it('persists measurement precision saved from the system settings hub', function () {
    $this->put(route('admin.settings.system.update', ['key' => 'system.measurement']), [
        'system' => ['measurement' => [
            'strategy' => 'trim',
            'amount'   => '5',
            'base'     => '7',
        ]],
    ])->assertRedirect(route('admin.settings.system.edit', ['key' => 'system.measurement']));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'system.measurement.strategy',
        'value' => 'trim',
    ]);

    $this->assertDatabaseHas('core_config', [
        'code'  => 'system.measurement.base',
        'value' => '7',
    ]);
});

it('keeps the measurement units when another package also decorates the product grid', function () {
    $attribute = measurementAttribute();

    $datagrid = app(ProductDataGrid::class);

    $method = new ReflectionMethod($datagrid, 'buildColumnDefinition');
    $method->setAccessible(true);
    $column = $method->invoke($datagrid, $attribute);

    expect($column['type'])->toBe('price')
        ->and(collect($column['options'])->pluck('value')->all())->toContain('meter', 'cm');

    $prepare = new ReflectionMethod($datagrid, 'prepareMassActions');
    $prepare->setAccessible(true);
    $prepare->invoke($datagrid);

    expect($datagrid->getMassActions())->not->toBeEmpty();
});

it('leaves a plain price attribute filtering by currency, not units', function () {
    $price = Attribute::factory()->create(['code' => 'cost_'.uniqid(), 'type' => 'price']);

    $datagrid = app(ProductDataGrid::class);

    $method = new ReflectionMethod($datagrid, 'buildColumnDefinition');
    $method->setAccessible(true);
    $column = $method->invoke($datagrid, $price);

    expect($column['type'])->toBe('price')
        ->and($column['attribute_type'])->toBe('price')
        ->and(collect($column['options'])->pluck('value')->all())->not->toContain('meter');
});
