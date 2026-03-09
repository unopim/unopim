<?php

return [

    'attribute' => [
        'measurement' => 'Mätning',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Skapa mätningsfamilj',
            'code'     => 'Kod',
            'standard' => 'Standardenhetskod',
            'symbol'   => 'Symbol',
            'save'     => 'Spara',
        ],

        'edit' => [
            'measurement_edit' => 'Redigera mätningsfamilj',
            'back'             => 'Tillbaka',
            'save'             => 'Spara',
            'general'          => 'Allmänt',
            'code'             => 'Kod',
            'label'            => 'Etikett',
            'units'            => 'Enheter',
            'create_units'     => 'Skapa enheter',
        ],

        'unit' => [
            'edit_unit'   => 'Redigera enhet',
            'create_unit' => 'Skapa enhet',
            'symbol'      => 'Symbol',
            'save'        => 'Spara',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Mätningsfamiljer',
        'measurement_family'   => 'Mätningsfamilj',
        'measurement_unit'     => 'Mätenhet',
    ],

    'datagrid' => [
        'labels'        => 'Etiketter',
        'code'          => 'Kod',
        'standard_unit' => 'Standardenhet',
        'unit_count'    => 'Antal enheter',
        'is_standard'   => 'Markera som standardenhet',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Mätningsfamiljen uppdaterades framgångsrikt.',
            'deleted'      => 'Mätningsfamiljen raderades framgångsrikt.',
            'mass_deleted' => 'Valda mätningsfamiljer raderades framgångsrikt.',
        ],

        'unit' => [
            'not_found'         => 'Mätningsfamiljen hittades inte.',
            'already_exists'    => 'Enhetskoden finns redan.',
            'not_foundd'        => 'Enheten hittades inte.',
            'deleted'           => 'Enheten raderades framgångsrikt.',
            'no_items_selected' => 'Inga objekt valda.',
            'mass_deleted'      => 'Valda mätenheter raderades framgångsrikt.',
        ],
    ],

];
