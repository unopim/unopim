<?php

return [

    'attribute' => [
        'calculated' => 'Calculated',
    ],

    'formula' => [
        'title'            => 'Formula',
        'label'            => 'Formula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'The value is derived from this formula every time the product is shown, exported or returned by the API. It is never stored and cannot be imported.',
        'reference-hint'   => 'Reference other attributes by their code.',
        'available'        => 'Available functions',
        'examples'         => 'Examples',
        'example-concat'   => 'Join two attributes, skipping the ones that are empty',
        'example-category' => 'The category label path, joined with #',
        'example-fallback' => 'Fall back to a second attribute when the first is empty',
        'read-only'        => 'Calculated',
        'read-only-hint'   => 'This value is calculated from other attributes and cannot be edited.',

        'variables' => [
            'title'      => 'Available variables',
            'sku'        => 'the product SKU',
            'type'       => 'the product type',
            'status'     => 'the product status',
            'family'     => 'the attribute family code',
            'parent'     => 'the parent SKU, for variants',
            'categories' => 'the assigned category codes',
            'channel'    => 'the channel being rendered',
            'locale'     => 'the locale being rendered',
        ],

        'errors' => [
            'syntax'              => 'The formula could not be parsed: :detail',
            'unknown-variable'    => 'Unknown attribute or variable ":name" in the formula.',
            'disallowed-function' => 'Function ":name" is not allowed in a formula.',
            'reserved-variable'   => 'The name ":name" is reserved and cannot be used in a formula.',
            'too-long'            => 'The formula may not be longer than :max characters.',
            'empty'               => 'A calculated attribute needs a formula.',
            'circular'            => 'The formula creates a circular dependency: :cycle',
        ],
    ],

];
