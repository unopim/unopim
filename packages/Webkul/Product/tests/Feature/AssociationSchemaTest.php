<?php

use Illuminate\Support\Facades\Schema;

it('creates all association type and field definition tables', function () {
    $tables = [
        'association_types',
        'association_type_translations',
        'association_type_fields',
        'association_type_field_translations',
        'association_type_field_options',
        'association_type_field_option_translations',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Expected table [{$table}] to exist.");
    }
});

it('has the expected key columns on the association_types table', function () {
    expect(Schema::hasColumns('association_types', [
        'code',
        'status',
        'position',
        'is_user_defined',
    ]))->toBeTrue();
});

it('has the expected key columns on the association_type_fields table', function () {
    expect(Schema::hasColumns('association_type_fields', [
        'association_type_id',
        'code',
        'type',
        'validation',
        'is_required',
        'is_unique',
        'value_per_locale',
        'section',
    ]))->toBeTrue();
});
