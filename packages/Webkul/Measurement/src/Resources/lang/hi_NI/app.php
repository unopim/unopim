<?php

return [

    'attribute' => [
        'measurement' => 'माप',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'मापन परिवार बनाएँ',
            'code'     => 'कोड',
            'standard' => 'मानक इकाई कोड',
            'symbol'   => 'प्रतीक',
            'save'     => 'सहेजें',
        ],

        'edit' => [
            'measurement_edit' => 'मापन परिवार संपादित करें',
            'back'             => 'वापस',
            'save'             => 'सहेजें',
            'general'          => 'सामान्य',
            'code'             => 'कोड',
            'label'            => 'लेबल',
            'units'            => 'इकाइयाँ',
            'create_units'     => 'इकाइयाँ बनाएँ',
        ],

        'unit' => [
            'edit_unit'   => 'इकाई संपादित करें',
            'create_unit' => 'इकाई बनाएँ',
            'symbol'      => 'प्रतीक',
            'save'        => 'सहेजें',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'मापन परिवार',
        'measurement_family'   => 'मापन परिवार',
        'measurement_unit'     => 'मापन इकाई',
    ],

    'datagrid' => [
        'labels'        => 'लेबल',
        'code'          => 'कोड',
        'standard_unit' => 'मानक इकाई',
        'unit_count'    => 'इकाइयों की संख्या',
        'is_standard'   => 'मानक इकाई चिह्नित करें',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'मापन परिवार सफलतापूर्वक अपडेट किया गया।',
            'deleted'      => 'मापन परिवार सफलतापूर्वक हटाया गया।',
            'mass_deleted' => 'चयनित मापन परिवार सफलतापूर्वक हटाए गए।',
        ],

        'unit' => [
            'not_found'         => 'मापन परिवार नहीं मिला।',
            'already_exists'    => 'इकाई कोड पहले से मौजूद है।',
            'not_foundd'        => 'इकाई नहीं मिली।',
            'deleted'           => 'इकाई सफलतापूर्वक हटाई गई।',
            'no_items_selected' => 'कोई आइटम चयनित नहीं है।',
            'mass_deleted'      => 'चयनित मापन इकाइयाँ सफलतापूर्वक हटाई गईं।',
        ],
    ],

];
