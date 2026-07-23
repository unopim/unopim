<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;

return [
    'default_group' => 'text',

    'valueless_operators' => [
        FilterOperators::IS_EMPTY->value,
        FilterOperators::IS_NOT_EMPTY->value,
    ],

    'groups' => [
        'option' => [
            'types' => [
                Attribute::SELECT_FIELD_TYPE,
                Attribute::MULTISELECT_FIELD_TYPE,
                Attribute::CHECKBOX_FIELD_TYPE,
            ],
            'control'   => 'options',
            'operators' => [
                ['operator' => FilterOperators::IN->value, 'label' => 'in'],
                ['operator' => FilterOperators::NOT_IN->value, 'label' => 'not_in'],
                ['operator' => FilterOperators::IS_EMPTY->value, 'label' => 'empty'],
                ['operator' => FilterOperators::IS_NOT_EMPTY->value, 'label' => 'not_empty'],
            ],
        ],

        'boolean' => [
            'types'     => [Attribute::BOOLEAN_FIELD_TYPE],
            'control'   => 'boolean',
            'operators' => [
                ['operator' => FilterOperators::EQUAL->value, 'label' => 'equals'],
            ],
        ],

        'numeric' => [
            'types' => [
                Attribute::PRICE_FIELD_TYPE,
                'integer',
                'decimal',
            ],
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

        'date' => [
            'types' => [
                Attribute::DATE_FIELD_TYPE,
                Attribute::DATETIME_FIELD_TYPE,
            ],
            'control'       => 'date',
            'range_control' => 'date_range',
            'operators'     => [
                ['operator' => FilterOperators::LESS_THAN->value, 'label' => 'before'],
                ['operator' => FilterOperators::GREATER_THAN->value, 'label' => 'after'],
                ['operator' => FilterOperators::RANGE->value, 'label' => 'between'],
                ['operator' => FilterOperators::IS_EMPTY->value, 'label' => 'empty'],
                ['operator' => FilterOperators::IS_NOT_EMPTY->value, 'label' => 'not_empty'],
            ],
        ],

        'text' => [
            'types' => [
                Attribute::TEXT_TYPE,
                Attribute::TEXTAREA_TYPE,
            ],
            'control'   => 'text',
            'operators' => [
                ['operator' => FilterOperators::CONTAINS->value, 'label' => 'contains'],
                ['operator' => FilterOperators::EQUAL->value, 'label' => 'equals'],
                ['operator' => FilterOperators::IS_EMPTY->value, 'label' => 'empty'],
                ['operator' => FilterOperators::IS_NOT_EMPTY->value, 'label' => 'not_empty'],
            ],
        ],
    ],
];
