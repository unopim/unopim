<?php

use Webkul\Admin\Fields\FieldConfig;

it('normalizes a config field into what the browser consumes', function () {
    $field = app(FieldConfig::class)->field([
        'name'       => 'channels',
        'title'      => 'admin::app.settings.data-transfer.exports.create.code',
        'type'       => 'multiselect',
        'required'   => true,
        'async'      => true,
        'list_route' => 'admin.settings.data_transfer.exports.filters.channels',
        'track_by'   => 'code',
    ]);

    expect($field['name'])->toBe('channels')
        ->and($field['type'])->toBe('multiselect')
        ->and($field['required'])->toBeTrue()
        ->and($field['label'])->not->toContain('::')
        ->and($field['list_route'])->toStartWith('http');
});

it('falls back to text for a field with no type', function () {
    expect(app(FieldConfig::class)->field(['name' => 'sku'])['type'])->toBe('text');
});

it('carries depends_on through, so scoping stays config driven', function () {
    $sets = app(FieldConfig::class)->payload(config('exporters'))['sets'];

    $byName = collect($sets['products'])->keyBy('name');

    expect($byName['locales']['depends_on'])->toBe(['field' => 'channels', 'as' => 'channels'])
        ->and($byName['channels']['depends_on'])->toBeNull();
});

it('declares attribute conditions as an ordinary config field, route and exclusions resolved', function () {
    $payload = app(FieldConfig::class)->payload(config('exporters'));

    $field = collect($payload['sets']['products'])->firstWhere('name', 'custom_attributes');

    expect($field)->not->toBeNull()
        ->and($field['type'])->toBe('attribute-conditions')
        ->and($field['list_route'])->toStartWith('http')
        ->and($field['list_route'])->toContain('filters/attributes')
        ->and($field['query_params'])->toBe(['exclude' => ['sku']])
        ->and($payload['types'])->toContain('attribute-conditions');
});

it('keeps attribute conditions off the entities whose config does not ask for them', function () {
    $sets = app(FieldConfig::class)->payload(config('exporters'))['sets'];

    expect(array_column($sets['categories'], 'name'))->not->toContain('custom_attributes');
});

it('memoizes the payload, so five cards do not rebuild it five times', function () {
    $config = app(FieldConfig::class);

    expect($config->payload(config('exporters')))->toBe($config->payload(config('exporters')));
});

it('keys the memo by content, so a different config cannot hit a stale entry', function () {
    $config = app(FieldConfig::class);

    $first = $config->payload(['products' => ['filters' => ['fields' => [['name' => 'sku']]]]]);
    $second = $config->payload(['products' => ['filters' => ['fields' => [['name' => 'status']]]]]);

    expect($first['sets']['products'][0]['name'])->toBe('sku')
        ->and($second['sets']['products'][0]['name'])->toBe('status');
});

it('takes the config as an array or as a json string', function () {
    $config = app(FieldConfig::class);
    $exporters = config('exporters');

    expect($config->payload(json_encode($exporters)))->toBe($config->payload($exporters))
        ->and($config->payload('')['sets'])->toBe([]);
});

it('reports the types a config needs, so only those widgets get loaded', function () {
    $types = app(FieldConfig::class)->payload(config('exporters'))['types'];

    expect($types)->toContain('multiselect')
        ->and($types)->toContain('boolean');
});

it('is a singleton, or the memo would be thrown away each time', function () {
    expect(app(FieldConfig::class))->toBe(app(FieldConfig::class));
});
