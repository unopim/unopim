<?php

return [

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
            'measurement_edit'      => 'Edit Measurement Family',
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
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Measurement Families',
        'measurement_family'   => 'Measurement Family',
        'measurement_unit'     => 'Measurement Unit',
    ],

    'datagrid' => [
        'labels'          => 'Labels',
        'code'            => 'Code',
        'standard_unit'   => 'Standard Unit',
        'unit_count'      => 'Number Of Units',
        'is_standard'     => 'Mark Standard Unit',
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
