<?php

use Webkul\Product\Type\Configurable;
use Webkul\Product\Type\Simple;

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
];
