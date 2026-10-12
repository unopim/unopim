<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Download Allow-Listed Roots
    |--------------------------------------------------------------------------
    |
    | Root segments `MediaController::download()` is allowed to serve from,
    | mapped to the permission that governs each one.
    |
    */
    'downloadable_roots' => [
        'product'          => 'catalog.products',
        'category'         => 'catalog.categories',
        'attribute_option' => 'catalog.attributes',
    ],
];
