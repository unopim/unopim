<?php

return [

    'attribute' => [
        'measurement' => 'Mesura',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Crear família de mesures',
            'code'     => 'Codi',
            'standard' => 'Codi de la unitat estàndard',
            'symbol'   => 'Símbol',
            'save'     => 'Desar',
        ],

        'edit' => [
            'measurement_edit' => 'Editar família de mesures',
            'back'             => 'Enrere',
            'save'             => 'Desar',
            'general'          => 'General',
            'code'             => 'Codi',
            'label'            => 'Etiqueta',
            'units'            => 'Unitats',
            'create_units'     => 'Crear unitats',
        ],

        'unit' => [
            'edit_unit'   => 'Editar unitat',
            'create_unit' => 'Crear unitat',
            'symbol'      => 'Símbol',
            'save'        => 'Desar',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Famílies de mesures',
        'measurement_family'   => 'Família de mesures',
        'measurement_unit'     => 'Unitat de mesura',
    ],

    'datagrid' => [
        'labels'        => 'Etiquetes',
        'code'          => 'Codi',
        'standard_unit' => 'Unitat estàndard',
        'unit_count'    => 'Nombre d’unitats',
        'is_standard'   => 'Marcar com a unitat estàndard',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'La família de mesures s’ha actualitzat correctament.',
            'deleted'      => 'La família de mesures s’ha eliminat correctament.',
            'mass_deleted' => 'Les famílies de mesures seleccionades s’han eliminat correctament.',
        ],

        'unit' => [
            'not_found'         => 'No s’ha trobat la família de mesures.',
            'already_exists'    => 'El codi de la unitat ja existeix.',
            'not_foundd'        => 'No s’ha trobat la unitat.',
            'deleted'           => 'La unitat s’ha eliminat correctament.',
            'no_items_selected' => 'No s’han seleccionat elements.',
            'mass_deleted'      => 'Les unitats de mesura seleccionades s’han eliminat correctament.',
        ],
    ],

];
