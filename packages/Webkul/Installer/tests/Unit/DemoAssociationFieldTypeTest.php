<?php

/**
 * The association field builder offers only the types listed in
 * `association_field_types`, so demo data may not define a field the operator
 * could never have created — and could not edit back to a valid state.
 */
function demoAssociations(): array
{
    return require __DIR__.'/../../src/Database/Data/Demo/associations.php';
}

function demoAssociationFields(): array
{
    $fields = [];

    foreach (demoAssociations()['types'] as $type) {
        foreach ($type['fields'] ?? [] as $field) {
            $fields[] = $field + ['association_type' => $type['code']];
        }
    }

    return $fields;
}

it('defines association fields only in types the field builder offers', function () {
    $offered = array_keys(config('association_field_types', []));

    expect($offered)->not->toBeEmpty();

    $unsupported = [];

    foreach (demoAssociationFields() as $field) {
        if (! in_array($field['type'], $offered, true)) {
            $unsupported[] = "{$field['association_type']}.{$field['code']} is a {$field['type']} field";
        }
    }

    expect($unsupported)->toBe([]);
});

it('carries options only on field types that render them', function () {
    $stray = [];

    foreach (demoAssociationFields() as $field) {
        if (isset($field['options']) && ! in_array($field['type'], ['select', 'multiselect'], true)) {
            $stray[] = "{$field['association_type']}.{$field['code']}";
        }
    }

    expect($stray)->toBe([]);
});

it('keeps every link value pointed at a field its association type declares', function () {
    $data = demoAssociations();

    $codesByType = [];

    foreach ($data['types'] as $type) {
        $codesByType[$type['code']] = array_column($type['fields'] ?? [], 'code');
    }

    $unknown = [];

    foreach ($data['links'] as $sku => $byType) {
        foreach ($byType as $typeCode => $targets) {
            foreach ($targets as $target) {
                if (! is_array($target)) {
                    continue;
                }

                $used = array_merge(
                    array_keys($target['data'] ?? []),
                    ...array_map('array_keys', array_values($target['locale_data'] ?? []))
                );

                foreach ($used as $code) {
                    if (! in_array($code, $codesByType[$typeCode] ?? [], true)) {
                        $unknown[] = "$sku uses $typeCode.$code";
                    }
                }
            }
        }
    }

    expect(array_values(array_unique($unknown)))->toBe([]);
});
