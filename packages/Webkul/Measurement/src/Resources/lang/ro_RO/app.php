<?php

return [

    'attribute' => [
        'measurement' => 'Măsurare',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Creează Familie de Măsurare',
            'code'     => 'Cod',
            'standard' => 'Cod Unitate Standard',
            'symbol'   => 'Simbol',
            'save'     => 'Salvează',
        ],

        'edit' => [
            'measurement_edit' => 'Editează Familia de Măsurare',
            'back'             => 'Înapoi',
            'save'             => 'Salvează',
            'general'          => 'General',
            'code'             => 'Cod',
            'label'            => 'Etichetă',
            'units'            => 'Unități',
            'create_units'     => 'Creează Unități',
        ],

        'unit' => [
            'edit_unit'   => 'Editează Unitate',
            'create_unit' => 'Creează Unitate',
            'symbol'      => 'Simbol',
            'save'        => 'Salvează',
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
            'updated'      => 'Familia de măsurare a fost actualizată cu succes.',
            'deleted'      => 'Familia de măsurare a fost ștearsă cu succes.',
            'mass_deleted' => 'Familiile de măsurare selectate au fost șterse cu succes.',
        ],

        'unit' => [
            'not_found'         => 'Familia de măsurare nu a fost găsită.',
            'already_exists'    => 'Codul unității există deja.',
            'not_foundd'        => 'Unitatea nu a fost găsită.',
            'deleted'           => 'Unitatea a fost ștearsă cu succes.',
            'no_items_selected' => 'Nu a fost selectat niciun element.',
            'mass_deleted'      => 'Unitățile de măsurare selectate au fost șterse cu succes.',
        ],
    ],

];
