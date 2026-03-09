<?php

return [

    'attribute' => [
        'measurement' => 'Meting',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Maak meetfamilie',
            'code'     => 'Code',
            'standard' => 'Standaardeenheid code',
            'symbol'   => 'Symbool',
            'save'     => 'Opslaan',
        ],

        'edit' => [
            'measurement_edit' => 'Bewerk meetfamilie',
            'back'             => 'Terug',
            'save'             => 'Opslaan',
            'general'          => 'Algemeen',
            'code'             => 'Code',
            'label'            => 'Label',
            'units'            => 'Eenheden',
            'create_units'     => 'Maak eenheden',
        ],

        'unit' => [
            'edit_unit'   => 'Bewerk eenheid',
            'create_unit' => 'Maak eenheid',
            'symbol'      => 'Symbool',
            'save'        => 'Opslaan',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Meetfamilies',
        'measurement_family'   => 'Meetfamilie',
        'measurement_unit'     => 'Meteenheid',
    ],

    'datagrid' => [
        'labels'        => 'Labels',
        'code'          => 'Code',
        'standard_unit' => 'Standaardeenheid',
        'unit_count'    => 'Aantal eenheden',
        'is_standard'   => 'Markeer als standaardeenheid',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Meetfamilie is succesvol bijgewerkt.',
            'deleted'      => 'Meetfamilie is succesvol verwijderd.',
            'mass_deleted' => 'Geselecteerde meetfamilies zijn succesvol verwijderd.',
        ],

        'unit' => [
            'not_found'         => 'Meetfamilie niet gevonden.',
            'already_exists'    => 'Eenheidscode bestaat al.',
            'not_foundd'        => 'Eenheid niet gevonden.',
            'deleted'           => 'Eenheid is succesvol verwijderd.',
            'no_items_selected' => 'Geen items geselecteerd.',
            'mass_deleted'      => 'Geselecteerde meeteenheden zijn succesvol verwijderd.',
        ],
    ],

];
