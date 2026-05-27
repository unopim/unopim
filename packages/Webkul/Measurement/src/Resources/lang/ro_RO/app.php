<?php

return [

    'attribute' => [
        'measurement' => 'Măsurare',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Creează Familie de Măsurare',
            'code'                  => 'Cod',
            'standard'              => 'Cod Unitate Standard',
            'symbol'                => 'Simbol',
            'save'                  => 'Salvează',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Editează Familia de Măsurare',
            'back'                  => 'Înapoi',
            'save'                  => 'Salvează',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'General',
            'code'                  => 'Cod',
            'label'                 => 'Etichetă',
            'units'                 => 'Unități',
            'create_units'          => 'Creează Unități',
        ],

        'unit' => [
            'edit_unit'             => 'Editează Unitate',
            'create_unit'           => 'Creează Unitate',
            'symbol'                => 'Simbol',
            'save'                  => 'Salvează',
            'conversion_operation'  => 'Operație de conversie',
            'add_new_operation'     => 'Adăugați o nouă operație',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Familii de Măsurare',
        'measurement_family'   => 'Familie de Măsurare',
        'measurement_unit'     => 'Unitate de Măsurare',
    ],

    'datagrid' => [
        'labels'        => 'Etichete',
        'code'          => 'Cod',
        'standard_unit' => 'Unitate Standard',
        'unit_count'    => 'Număr de Unități',
        'is_standard'   => 'Marchează ca Unitate Standard',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Familia de măsurători a fost creată cu succes.',
            'updated'      => 'Familia de măsurare a fost actualizată cu succes.',
            'deleted'      => 'Familia de măsurare a fost ștearsă cu succes.',
            'mass_deleted' => 'Familiile de măsurare selectate au fost șterse cu succes.',
        ],

        'unit' => [
            'not_found'              => 'Familia de măsurare nu a fost găsită.',
            'already_exists'         => 'Codul unității există deja.',
            'units_not_found'        => 'Unitatea nu a fost găsită.',
            'deleted'                => 'Unitatea a fost ștearsă cu succes.',
            'no_items_selected'      => 'Nu a fost selectat niciun element.',
            'mass_deleted'           => 'Unitățile de măsurare selectate au fost șterse cu succes.',
        ],
    ],

];
