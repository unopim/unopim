<?php

return [

    'attribute' => [
        'measurement' => 'Måling',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Opret målefamilie',
            'code'     => 'Kode',
            'standard' => 'Standard enhedskode',
            'symbol'   => 'Symbol',
            'save'     => 'Gem',
        ],

        'edit' => [
            'measurement_edit' => 'Rediger målefamilie',
            'back'             => 'Tilbage',
            'save'             => 'Gem',
            'general'          => 'Generelt',
            'code'             => 'Kode',
            'label'            => 'Etiket',
            'units'            => 'Enheder',
            'create_units'     => 'Opret enheder',
        ],

        'unit' => [
            'edit_unit'   => 'Rediger enhed',
            'create_unit' => 'Opret enhed',
            'symbol'      => 'Symbol',
            'save'        => 'Gem',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Målefamilier',
        'measurement_family'   => 'Målefamilie',
        'measurement_unit'     => 'Måleenhed',
    ],

    'datagrid' => [
        'labels'        => 'Etiketter',
        'code'          => 'Kode',
        'standard_unit' => 'Standardenhed',
        'unit_count'    => 'Antal enheder',
        'is_standard'   => 'Marker som standardenhed',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Målefamilien blev oprettet med succes.',
            'updated'      => 'Målefamilien er blevet opdateret.',
            'deleted'      => 'Målefamilien er blevet slettet.',
            'mass_deleted' => 'De valgte målefamilier er blevet slettet.',
        ],

        'unit' => [
            'not_found'         => 'Målefamilien blev ikke fundet.',
            'already_exists'    => 'Enhedskoden findes allerede.',
            'not_foundd'        => 'Enheden blev ikke fundet.',
            'deleted'           => 'Enheden er blevet slettet.',
            'no_items_selected' => 'Ingen elementer valgt.',
            'mass_deleted'      => 'De valgte måleenheder er blevet slettet.',
        ],
    ],

];
