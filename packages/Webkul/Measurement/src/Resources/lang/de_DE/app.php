<?php

return [

    'attribute' => [
        'measurement' => 'Messung',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Messfamilie erstellen',
            'code'     => 'Code',
            'standard' => 'Standard-Einheitencode',
            'symbol'   => 'Symbol',
            'save'     => 'Speichern',
        ],

        'edit' => [
            'measurement_edit' => 'Messfamilie bearbeiten',
            'back'             => 'Zurück',
            'save'             => 'Speichern',
            'general'          => 'Allgemein',
            'code'             => 'Code',
            'label'            => 'Bezeichnung',
            'units'            => 'Einheiten',
            'create_units'     => 'Einheiten erstellen',
        ],

        'unit' => [
            'edit_unit'   => 'Einheit bearbeiten',
            'create_unit' => 'Einheit erstellen',
            'symbol'      => 'Symbol',
            'save'        => 'Speichern',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Messfamilien',
        'measurement_family'   => 'Messfamilie',
        'measurement_unit'     => 'Maßeinheit',
    ],

    'datagrid' => [
        'labels'        => 'Bezeichnungen',
        'code'          => 'Code',
        'standard_unit' => 'Standardeinheit',
        'unit_count'    => 'Anzahl der Einheiten',
        'is_standard'   => 'Als Standardeinheit markieren',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Die Messfamilie wurde erfolgreich aktualisiert.',
            'deleted'      => 'Die Messfamilie wurde erfolgreich gelöscht.',
            'mass_deleted' => 'Die ausgewählten Messfamilien wurden erfolgreich gelöscht.',
        ],

        'unit' => [
            'not_found'         => 'Messfamilie nicht gefunden.',
            'already_exists'    => 'Der Einheitencode existiert bereits.',
            'not_foundd'        => 'Einheit nicht gefunden.',
            'deleted'           => 'Die Einheit wurde erfolgreich gelöscht.',
            'no_items_selected' => 'Keine Elemente ausgewählt.',
            'mass_deleted'      => 'Die ausgewählten Maßeinheiten wurden erfolgreich gelöscht.',
        ],
    ],

];
