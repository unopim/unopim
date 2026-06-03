<?php

return [

    'attribute' => [
        'measurement' => 'Mätning',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Skapa mätningsfamilj',
            'code'                  => 'Kod',
            'standard'              => 'Standardenhetskod',
            'symbol'                => 'Symbol',
            'save'                  => 'Spara',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Redigera mätningsfamilj',
            'back'                  => 'Tillbaka',
            'save'                  => 'Spara',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Allmänt',
            'code'                  => 'Kod',
            'label'                 => 'Etikett',
            'units'                 => 'Enheter',
            'create_units'          => 'Skapa enheter',
        ],

        'unit' => [
            'edit_unit'             => 'Redigera enhet',
            'create_unit'           => 'Skapa enhet',
            'symbol'                => 'Symbol',
            'save'                  => 'Spara',
            'conversion_operation'  => 'Konverteringsoperation',
            'add_new_operation'     => 'Lägg till ny operation',
            'conversion_value'      => 'Värde',
            'conversion_operator'   => 'Operatör',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Mätningsfamiljer',
        'measurement_family'   => 'Mätningsfamilj',
        'measurement_unit'     => 'Mätenhet',
    ],

    'datagrid' => [
        'labels'        => 'Namn',
        'code'          => 'Kod',
        'standard_unit' => 'Standardenhet',
        'unit_count'    => 'Antal enheter',
        'is_standard'   => 'Markera som standardenhet',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Mätfamiljen har skapats framgångsrikt.',
            'updated'      => 'Mätningsfamiljen uppdaterades framgångsrikt.',
            'deleted'      => 'Mätningsfamiljen raderades framgångsrikt.',
            'mass_deleted' => 'Valda mätningsfamiljer raderades framgångsrikt.',
        ],

        'unit' => [
            'not_found'              => 'Mätningsfamiljen hittades inte.',
            'already_exists'         => 'Enhetskoden finns redan.',
            'units_not_found'        => 'Enheten hittades inte.',
            'deleted'                => 'Enheten raderades framgångsrikt.',
            'no_items_selected'      => 'Inga objekt valda.',
            'mass_deleted'           => 'Valda mätenheter raderades framgångsrikt.',
        ],
    ],

];
