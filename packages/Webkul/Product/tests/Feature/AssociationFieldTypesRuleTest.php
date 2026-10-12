<?php

use Webkul\Product\Rules\AssociationFieldTypes;

it('rejects an unknown field type', function () {
    $failed = false;
    (new AssociationFieldTypes)->validate('type', 'not_a_type', function () use (&$failed) {
        $failed = true;

        return new class
        {
            public function translate($replace = [])
            {
                return $this;
            }
        };
    });
    expect($failed)->toBeTrue();
});

it('accepts a known field type', function () {
    $failed = false;
    (new AssociationFieldTypes)->validate('type', 'text', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeFalse();
});

it('accepts only the text and boolean field types', function () {
    expect(array_keys(config('association_field_types')))->toBe(['text', 'boolean']);
});

it('rejects a field type that is no longer offered', function (string $type) {
    $failed = false;
    (new AssociationFieldTypes)->validate('type', $type, function () use (&$failed) {
        $failed = true;

        return new class
        {
            public function translate($replace = [])
            {
                return $this;
            }
        };
    });
    expect($failed)->toBeTrue();
})->with(['textarea', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox']);
