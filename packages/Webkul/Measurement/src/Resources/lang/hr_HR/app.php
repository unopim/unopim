<?php

return [

    'attribute' => [
        'measurement' => 'Mjerenje',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Kreiraj obitelj mjerenja',
            'code'                  => 'Kod',
            'standard'              => 'Kod standardne jedinice',
            'symbol'                => 'Simbol',
            'save'                  => 'Spremi',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Uredi obitelj mjerenja',
            'back'                  => 'Natrag',
            'save'                  => 'Spremi',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Općenito',
            'code'                  => 'Kod',
            'label'                 => 'Oznaka',
            'units'                 => 'Jedinice',
            'create_units'          => 'Kreiraj jedinice',
        ],

        'unit' => [
            'edit_unit'             => 'Uredi jedinicu',
            'create_unit'           => 'Kreiraj jedinicu',
            'symbol'                => 'Simbol',
            'save'                  => 'Spremi',
            'conversion_operation'  => 'Operacija konverzije',
            'add_new_operation'     => 'Dodaj novu operaciju',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Obitelji mjerenja',
        'measurement_family'   => 'Obitelj mjerenja',
        'measurement_unit'     => 'Mjerna jedinica',
    ],

    'datagrid' => [
        'labels'        => 'Oznake',
        'code'          => 'Kod',
        'standard_unit' => 'Standardna jedinica',
        'unit_count'    => 'Broj jedinica',
        'is_standard'   => 'Označi kao standardnu jedinicu',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Obitelj mjernih jedinica uspješno je kreirana.',
            'updated'      => 'Obitelj mjerenja uspješno je ažurirana.',
            'deleted'      => 'Obitelj mjerenja uspješno je obrisana.',
            'mass_deleted' => 'Odabrane obitelji mjerenja uspješno su obrisane.',
        ],

        'unit' => [
            'not_found'              => 'Obitelj mjerenja nije pronađena.',
            'already_exists'         => 'Kod jedinice već postoji.',
            'units_not_found'        => 'Jedinica nije pronađena.',
            'deleted'                => 'Jedinica je uspješno obrisana.',
            'no_items_selected'      => 'Nijedna stavka nije odabrana.',
            'mass_deleted'           => 'Odabrane mjerne jedinice uspješno su obrisane.',
        ],
    ],

];
