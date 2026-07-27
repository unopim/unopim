<?php

use Webkul\ElasticSearch\Enums\FilterOperators;

return [
    'groups' => [
        'measurement' => [
            'types'         => ['measurement'],
            'control'       => 'number',
            'range_control' => 'number_range',
            'operators'     => [
                ['operator' => FilterOperators::EQUAL->value, 'label' => 'equals'],
                ['operator' => FilterOperators::LESS_THAN->value, 'label' => 'less_than'],
                ['operator' => FilterOperators::LESS_THAN_OR_EQUAL->value, 'label' => 'less_than_equal'],
                ['operator' => FilterOperators::GREATER_THAN->value, 'label' => 'greater_than'],
                ['operator' => FilterOperators::GREATER_THAN_OR_EQUAL->value, 'label' => 'greater_than_equal'],
                ['operator' => FilterOperators::RANGE->value, 'label' => 'between'],
                ['operator' => FilterOperators::IS_EMPTY->value, 'label' => 'empty'],
                ['operator' => FilterOperators::IS_NOT_EMPTY->value, 'label' => 'not_empty'],
            ],
        ],
    ],
];
