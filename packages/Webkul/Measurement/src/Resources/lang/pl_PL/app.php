<?php

return [

    'acl' => [
        'unauthorized' => 'Nie masz uprawnień do wykonania tej akcji.',
    ],
    'attribute' => [
        'measurement' => 'Pomiar',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Utwórz rodzinę jednostek',
            'code'                  => 'Kod',
            'standard'              => 'Kod jednostki standardowej',
            'symbol'                => 'Symbol',
            'save'                  => 'Zapisz',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Edytuj rodzinę jednostek',
            'back'                  => 'Powrót',
            'save'                  => 'Zapisz',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Ogólne',
            'code'                  => 'Kod',
            'label'                 => 'Etykieta',
            'units'                 => 'Jednostki',
            'create_units'          => 'Utwórz jednostki',
        ],

        'unit' => [
            'edit_unit'             => 'Edytuj jednostkę',
            'create_unit'           => 'Utwórz jednostkę',
            'symbol'                => 'Symbol',
            'save'                  => 'Zapisz',
            'conversion_operation'  => 'Operacja konwersji',
            'add_new_operation'     => 'Dodaj nową operację',
            'conversion_value'      => 'Wartość',
            'conversion_operator'   => 'Operator',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Rodziny jednostek',
        'measurement_family'   => 'Rodzina jednostek',
        'measurement_unit'     => 'Jednostka miary',
    ],

    'datagrid' => [
        'labels'        => 'Nazwa',
        'code'          => 'Kod',
        'standard_unit' => 'Jednostka standardowa',
        'unit_count'    => 'Liczba jednostek',
        'is_standard'   => 'Oznacz jako jednostkę standardową',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Rodzina pomiarów została pomyślnie utworzona.',
            'updated'      => 'Rodzina jednostek została pomyślnie zaktualizowana.',
            'deleted'      => 'Rodzina jednostek została pomyślnie usunięta.',
            'mass_deleted' => 'Wybrane rodziny jednostek zostały pomyślnie usunięte.',
        ],

        'unit' => [
            'not_found'              => 'Nie znaleziono rodziny jednostek.',
            'already_exists'         => 'Kod jednostki już istnieje.',
            'units_not_found'        => 'Nie znaleziono jednostki.',
            'deleted'                => 'Jednostka została pomyślnie usunięta.',
            'no_items_selected'      => 'Nie wybrano żadnych elementów.',
            'mass_deleted'           => 'Wybrane jednostki zostały pomyślnie usunięte.',
        ],
    ],

];
