<?php

return [

    'acl' => [
        'unauthorized' => 'You do not have permission to perform this action.',
    ],

    'attribute' => [
        'measurement' => 'Measurement',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Create Measurements',
            'code'                  => 'Code',
            'standard'              => 'Standard Unit Code',
            'symbol'                => 'Symbol',
            'save'                  => 'Save',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',

        ],

        'edit' => [
            'measurement_edit'      => 'Edit Measurement',
            'back'                  => 'Back',
            'save'                  => 'Save',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'General',
            'code'                  => 'Code',
            'label'                 => 'Label',
            'units'                 => 'Units',
            'create_units'          => 'Create Units',
        ],
        'unit' => [
            'edit_unit'             => 'Edit Unit',
            'create_unit'           => 'Create Unit',
            'symbol'                => 'Symbol',
            'save'                  => 'Save',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'conversion_value'      => 'Value',
            'conversion_operator'   => 'Operator',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Measurements',
        'measurement_family'   => 'Measurement Family',
        'measurement_unit'     => 'Measurement Unit',
    ],

    'datagrid' => [
        'labels'          => 'Name',
        'code'            => 'Code',
        'standard_unit'   => 'Standard Unit',
        'unit_count'      => 'Number Of Units',
        'is_standard'     => 'Mark Standard Unit',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => 'The unit ":unit" is not a valid unit for the ":attribute" measurement attribute.',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => 'Measurement family created successfully.',
            'updated'      => 'Measurement Family updated successfully.',
            'deleted'      => 'Measurement family deleted successfully.',
            'mass_deleted' => 'Selected measurement families deleted successfully.',
        ],

        'unit' => [
            'not_found'              => 'Measurement Family not found.',
            'already_exists'         => 'Unit code already exists.',
            'units_not_found'        => 'Unit not found.',
            'deleted'                => 'Unit deleted successfully.',
            'no_items_selected'      => 'No items selected.',
            'mass_deleted'           => 'Selected measurement Units deleted successfully.',
        ],
    ],
];
