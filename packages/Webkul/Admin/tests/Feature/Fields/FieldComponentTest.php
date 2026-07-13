<?php

use Illuminate\Support\Facades\Blade;
use Webkul\Admin\Fields\FieldConfig;

$views = __DIR__.'/../../../src/Resources/views';
$fields = $views.'/components/form/fields';

beforeEach(function () {
    $this->loginAsAdmin();
});

it('dispatches by type instead of a hardcoded switch, and falls back when a type is unknown', function () use ($fields) {
    $inputs = file_get_contents($fields.'/inputs.blade.php');

    expect($inputs)->toContain(':is="componentFor(field.type)"')
        ->and($inputs)->toContain("'v-field-' + type")
        ->and($inputs)->toContain("'v-field-text'")
        ->and($inputs)->not->toContain("field.type == 'select'");
});

it('registers the wrapper under v-form-field, not v-field, which vee-validate owns', function () use ($fields) {
    $inputs = file_get_contents($fields.'/inputs.blade.php');

    expect($inputs)->toContain("app.component('v-form-field'")
        ->and($inputs)->not->toContain("app.component('v-field',");
});

it('routes every input through setValue, so none can skip dirty tracking', function () use ($fields) {
    $inputs = file_get_contents($fields.'/inputs.blade.php');

    expect($inputs)->toContain("new CustomEvent('unsaved-changes:touch'")
        ->and($inputs)->toContain('bubbles: true')
        ->and($inputs)->toContain('mixins: [window.unopim.fieldBase]');
});

it('forwards undeclared caller attributes down to the type component', function () use ($fields) {
    $inputs = file_get_contents($fields.'/inputs.blade.php');

    expect($inputs)->toContain('inheritAttrs: false')
        ->and($inputs)->toContain('v-bind="$attrs"')
        ->and(strpos($inputs, 'v-bind="$attrs"'))
        ->toBeLessThan(strpos($inputs, ':is="componentFor(field.type)"'));
});

it('never emits a script outside a push stack, or it would break a host x-template', function () use ($views, $fields) {
    $entryPoints = [
        $views.'/components/form/field.blade.php',
        $views.'/components/form/fields.blade.php',
        $fields.'/load.blade.php',
        $fields.'/inputs.blade.php',
    ];

    foreach ($entryPoints as $entryPoint) {
        $depth = 0;

        foreach (explode("\n", file_get_contents($entryPoint)) as $number => $line) {
            if (preg_match('/@(pushOnce|push|prepend)\b/', $line)) {
                $depth++;
            }

            if (preg_match('/@(endPushOnce|endpush|endprepend)\b/i', $line)) {
                $depth--;

                continue;
            }

            expect($depth > 0 || ! str_contains($line, '<script'))->toBeTrue(
                basename($entryPoint).' emits an inline <script> on line '.($number + 1)
            );
        }
    }
});

it('renders a single field from a connector-style call', function () {
    $html = Blade::render('<x-admin::form.field type="text" name="sku" label="SKU" />@stack(\'scripts\')');

    expect($html)->toContain('<v-form-field')
        ->toContain("app.component('v-form-field'")
        ->toContain('id="v-field-text-template"');
});

it('loads a heavy widget only when a field actually asks for it', function () {
    $plain = Blade::render('<x-admin::form.field type="text" name="sku" />@stack(\'scripts\')');
    $tree = Blade::render('<x-admin::form.field type="category-tree" name="categories" />@stack(\'scripts\')');

    expect($plain)->not->toContain("app.component('v-field-category-tree'")
        ->and($tree)->toContain("app.component('v-field-category-tree'");
});

it('renders a field set from an array of fields', function () {
    $html = Blade::render('<x-admin::form.fields :fields="$fields" />@stack(\'scripts\')', ['fields' => [
        ['name' => 'sku', 'type' => 'text', 'label' => 'SKU'],
        ['name' => 'status', 'type' => 'boolean', 'label' => 'Status'],
    ]]);

    expect($html)->toContain('<v-field-set')
        ->toContain("app.component('v-field-set'")
        ->toContain("app.component('v-field-boolean'")
        ->toContain('id="v-field-text-template"');
});

it('renders the export create page through the shared components', function () {
    $response = $this->get(route('admin.settings.data_transfer.exports.create'));

    $response->assertOk()
        ->assertSee("app.component('v-form-field'", false)
        ->assertSee("app.component('v-field-set'", false)
        ->assertSee("app.component('v-field-attribute-conditions'", false);
});

it('ships a template for every field type the exporter config can reach', function () {
    $html = $this->get(route('admin.settings.data_transfer.exports.create'))->getContent();

    $types = app(FieldConfig::class)->payload(config('exporters'))['types'];

    expect($types)->not->toBeEmpty();

    foreach ($types as $type) {
        expect($html)->toContain('id="v-field-'.$type.'-template"');
    }
});

it('renders the import create page through the shared components', function () {
    $this->get(route('admin.settings.data_transfer.imports.create'))
        ->assertOk()
        ->assertSee("app.component('v-form-field'", false)
        ->assertSee("app.component('v-field-set'", false);
});

it('registers every component the rendered pages actually reference', function () {
    foreach (['exports', 'imports'] as $entity) {
        $html = $this->get(route("admin.settings.data_transfer.{$entity}.create"))->getContent();

        preg_match_all('/<(v-form-field|v-field-set|v-field-[a-z-]+)[\s>]/', $html, $matches);

        foreach (array_unique($matches[1]) as $component) {
            expect($html)->toContain("app.component('{$component}'");
        }
    }
});

it('points the export page at the sets-key the shim publishes', function () {
    $html = $this->get(route('admin.settings.data_transfer.exports.create'))->getContent();

    preg_match("/window\.unopim\.fieldSets\['([a-f0-9]+)'\]/", $html, $registry);
    preg_match('/const setsKey = "([a-f0-9]+)"/', $html, $page);

    expect($registry[1] ?? null)->not->toBeNull()
        ->and($page[1] ?? null)->toBe($registry[1]);
});

it('publishes the field sets once per page rather than inlining them per card', function () use ($views) {
    $html = $this->get(route('admin.settings.data_transfer.exports.create'))->getContent();

    expect(substr_count($html, 'window.unopim.fieldSets['))->toBe(1);

    foreach (['filter-fields', 'import-setting-fields'] as $shim) {
        expect(file_get_contents($views."/components/data-transfer/{$shim}.blade.php"))
            ->toContain('sets-key="{{ $setsKey }}"');
    }
});

it('drives the datagrid filters through the same field component', function () use ($views) {
    $grid = $views.'/components/datagrid';

    expect(file_get_contents($grid.'/filters.blade.php'))
        ->toContain('<v-form-field')
        ->toContain('context="filter"')
        ->toContain('filterFields[column.index]');

    expect(file_get_contents($grid.'/index.blade.php'))
        ->toContain('<x-admin::form.fields.load')
        ->toContain('filterFields()');

    $this->get(route('admin.settings.data_transfer.exports.index'))
        ->assertOk()
        ->assertSee("app.component('v-form-field'", false);
});

it('leaves the multi-value datagrid filters on their own dropdowns', function () use ($views) {
    $filters = file_get_contents($views.'/components/datagrid/filters.blade.php');

    expect($filters)->toContain('v-datagrid-searchable-dropdown')
        ->and($filters)->toContain('v-datagrid-sync-dropdown')
        ->and($filters)->toContain('removeAppliedColumnValue');
});

it('renders every type standalone', function () {
    $types = ['text', 'number', 'textarea', 'boolean', 'select', 'multiselect', 'date', 'datetime', 'date-range', 'datetime-range', 'price', 'tags', 'category-tree', 'attribute-conditions'];

    foreach ($types as $type) {
        $html = Blade::render(
            '<x-admin::form.field :type="$type" name="x" />@stack(\'scripts\')',
            ['type' => $type],
        );

        expect($html)->toContain('id="v-field-'.$type.'-template"')
            ->and($html)->toContain("app.component('v-field-".$type."'");
    }

    // the calendar picker must survive on the date widgets
    foreach (['date', 'datetime', 'date-range', 'datetime-range'] as $type) {
        $html = Blade::render('<x-admin::form.field :type="$type" name="x" />@stack(\'scripts\')', ['type' => $type]);

        expect($html)->toContain(str_contains($type, 'datetime') ? '<v-datetime-picker' : '<v-date-picker');
    }
});
