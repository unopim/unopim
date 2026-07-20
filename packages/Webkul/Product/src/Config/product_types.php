<?php

use Webkul\Product\Type\Configurable;
use Webkul\Product\Type\Simple;
use Webkul\Product\Type\VariantGroup;

return [
    'simple'       => [
        'key'   => 'simple',
        'name'  => 'product::app.type.simple',
        'class' => Simple::class,
        'sort'  => 1,
    ],

    'configurable' => [
        'key'   => 'configurable',
        'name'  => 'product::app.type.configurable',
        'class' => Configurable::class,
        'sort'  => 2,
    ],

    'variant_group' => [
        'key'      => 'variant_group',
        'name'     => 'product::app.type.variant-group',
        'class'    => VariantGroup::class,
        'sort'     => 3,
        'internal' => true,
    ],
];
