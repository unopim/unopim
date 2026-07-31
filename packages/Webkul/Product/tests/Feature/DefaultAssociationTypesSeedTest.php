<?php

use Webkul\Product\Models\AssociationType;

it('seeds the three default association types as non-user-defined', function () {
    foreach (['related_products', 'up_sells', 'cross_sells'] as $code) {
        $type = AssociationType::where('code', $code)->first();
        expect($type)->not->toBeNull()
            ->and((bool) $type->is_user_defined)->toBeFalse();
    }
});
