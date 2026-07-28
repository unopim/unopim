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
