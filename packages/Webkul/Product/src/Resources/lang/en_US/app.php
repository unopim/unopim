<?php

return [
    'acl' => [
        'variant-structures' => 'Variant Structures',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'copy-of-:value',
        'copy-of'                       => 'Copy Of :value',
        'variant-already-exist-message' => 'Variant with same attribute options already exists.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Products of type :type can not be copied',
    ],

    'type'     => [
        'configurable'  => 'Configurable',
        'simple'        => 'Simple',
        'variant-group' => 'Variant Group',
        'variant_group' => 'Variant Group',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Circular template reference detected: :path.',
            'currency-not-allowed'    => 'Only price attributes accept a currency, but ":attribute" is not a price attribute.',
            'currency-not-enabled'    => 'The currency ":currency" used in the template is not enabled for any channel.',
            'currency-not-in-channel' => 'The currency ":currency" is not enabled for the channel ":channel".',
            'filter-failed'           => 'The filter ":filter" could not be applied to the value of ":attribute".',
            'filter-not-supported'    => 'The filter ":filter" cannot be applied to the attribute ":attribute".',
            'format-date-position'    => 'The format_date filter must be the first filter in ":expression".',
            'inside-html-tag'         => 'Template expressions cannot be placed inside HTML tags or attributes such as links and images.',
            'invalid-filter-argument' => 'The filter ":filter" in ":expression" has a missing or invalid argument.',
            'invalid-reference'       => 'The template stored in ":attribute" is invalid and could not be rendered.',
            'invalid-syntax'          => 'The template expression ":expression" is invalid. Use an attribute code followed by optional filters, for example: name | upper.',
            'max-depth'               => 'Template references are nested more than :max levels deep at ":attribute".',
            'missing-value'           => 'The attribute ":attribute" has no value for this channel and locale.',
            'not-in-family'           => 'The attribute ":attribute" used in the template does not belong to the product family.',
            'output-too-large'        => 'The rendered template is larger than :max bytes and was cut off.',
            'self-reference'          => 'The template cannot reference its own attribute ":attribute".',
            'single-brace'            => 'Found ":text" in single curly braces. Template expressions need double curly braces.',
            'source-too-long'         => 'A template can be at most :max bytes long.',
            'too-complex'             => 'The template is too complex to check. Shorten or simplify it.',
            'too-many-expressions'    => 'A template can contain at most :max expressions.',
            'too-many-filters'        => 'The template expression ":expression" can use at most :max filters.',
            'unclosed-expression'     => 'A template expression was opened but never closed.',
            'unknown-attribute'       => 'The attribute ":attribute" used in the template does not exist.',
            'unknown-currency'        => 'The currency ":currency" used in the template does not exist.',
            'unknown-filter'          => 'The filter ":filter" is not supported. Available filters: :filters.',
            'unsupported-attribute'   => 'The attribute ":attribute" cannot be used in a template because its type is not supported.',
        ],
    ],
];
