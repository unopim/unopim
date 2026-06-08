<?php

return [

    'acl' => [
        'unauthorized' => 'Sie haben keine Berechtigung, diese Aktion durchzuführen.',
    ],
    'attribute' => [
        'measurement' => 'Messung',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Messfamilie erstellen',
            'code'                  => 'Code',
            'standard'              => 'Standard-Einheitencode',
            'symbol'                => 'Symbol',
            'save'                  => 'Speichern',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Messfamilie bearbeiten',
            'back'                  => 'Zurück',
            'save'                  => 'Speichern',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Allgemein',
            'code'                  => 'Code',
            'label'                 => 'Bezeichnung',
            'units'                 => 'Einheiten',
            'create_units'          => 'Einheiten erstellen',
        ],

        'unit' => [
            'edit_unit'             => 'Einheit bearbeiten',
            'create_unit'           => 'Einheit erstellen',
            'symbol'                => 'Symbol',
            'save'                  => 'Speichern',
            'conversion_operation'  => 'Konvertierungsoperation',
            'add_new_operation'     => 'Neue Operation hinzufügen',
            'conversion_value'      => 'Wert',
            'conversion_operator'   => 'Operator',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Messfamilien',
        'measurement_family'   => 'Messfamilie',
        'measurement_unit'     => 'Maßeinheit',
    ],

    'datagrid' => [
        'labels'        => 'Name',
        'code'          => 'Code',
        'standard_unit' => 'Standardeinheit',
        'unit_count'    => 'Anzahl der Einheiten',
        'is_standard'   => 'Als Standardeinheit markieren',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => 'Die Einheit ":unit" ist keine gültige Einheit für das Maßattribut ":attribute".',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => 'Messfamilie wurde erfolgreich erstellt.',
            'updated'      => 'Die Messfamilie wurde erfolgreich aktualisiert.',
            'deleted'      => 'Die Messfamilie wurde erfolgreich gelöscht.',
            'mass_deleted' => 'Die ausgewählten Messfamilien wurden erfolgreich gelöscht.',
        ],

        'unit' => [
            'not_found'              => 'Messfamilie nicht gefunden.',
            'already_exists'         => 'Der Einheitencode existiert bereits.',
            'units_not_found'        => 'Einheit nicht gefunden.',
            'deleted'                => 'Die Einheit wurde erfolgreich gelöscht.',
            'no_items_selected'      => 'Keine Elemente ausgewählt.',
            'mass_deleted'           => 'Die ausgewählten Maßeinheiten wurden erfolgreich gelöscht.',
        ],
    ],

];
