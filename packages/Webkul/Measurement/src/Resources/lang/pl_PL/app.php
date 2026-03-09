<?php

return [

    'attribute' => [
        'measurement' => 'Pomiar',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Utwórz rodzinę jednostek',
            'code'     => 'Kod',
            'standard' => 'Kod jednostki standardowej',
            'symbol'   => 'Symbol',
            'save'     => 'Zapisz',
        ],

        'edit' => [
            'measurement_edit' => 'Edytuj rodzinę jednostek',
            'back'             => 'Powrót',
            'save'             => 'Zapisz',
            'general'          => 'Ogólne',
            'code'             => 'Kod',
            'label'            => 'Etykieta',
            'units'            => 'Jednostki',
            'create_units'     => 'Utwórz jednostki',
        ],

        'unit' => [
            'edit_unit'   => 'Edytuj jednostkę',
            'create_unit' => 'Utwórz jednostkę',
            'symbol'      => 'Symbol',
            'save'        => 'Zapisz',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Rodziny jednostek',
        'measurement_family'   => 'Rodzina jednostek',
        'measurement_unit'     => 'Jednostka miary',
    ],

    'datagrid' => [
        'labels'        => 'Etykiety',
        'code'          => 'Kod',
        'standard_unit' => 'Jednostka standardowa',
        'unit_count'    => 'Liczba jednostek',
        'is_standard'   => 'Oznacz jako jednostkę standardową',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Rodzina jednostek została pomyślnie zaktualizowana.',
            'deleted'      => 'Rodzina jednostek została pomyślnie usunięta.',
            'mass_deleted' => 'Wybrane rodziny jednostek zostały pomyślnie usunięte.',
        ],

        'unit' => [
            'not_found'         => 'Nie znaleziono rodziny jednostek.',
            'already_exists'    => 'Kod jednostki już istnieje.',
            'not_foundd'        => 'Nie znaleziono jednostki.',
            'deleted'           => 'Jednostka została pomyślnie usunięta.',
            'no_items_selected' => 'Nie wybrano żadnych elementów.',
            'mass_deleted'      => 'Wybrane jednostki zostały pomyślnie usunięte.',
        ],
    ],

];
