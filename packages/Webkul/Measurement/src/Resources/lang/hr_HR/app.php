<?php

return [

    'attribute' => [
        'measurement' => 'Mjerenje',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Kreiraj obitelj mjerenja',
            'code'     => 'Kod',
            'standard' => 'Kod standardne jedinice',
            'symbol'   => 'Simbol',
            'save'     => 'Spremi',
        ],

        'edit' => [
            'measurement_edit' => 'Uredi obitelj mjerenja',
            'back'             => 'Natrag',
            'save'             => 'Spremi',
            'general'          => 'Općenito',
            'code'             => 'Kod',
            'label'            => 'Oznaka',
            'units'            => 'Jedinice',
            'create_units'     => 'Kreiraj jedinice',
        ],

        'unit' => [
            'edit_unit'   => 'Uredi jedinicu',
            'create_unit' => 'Kreiraj jedinicu',
            'symbol'      => 'Simbol',
            'save'        => 'Spremi',
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
            'updated'      => 'Obitelj mjerenja uspješno je ažurirana.',
            'deleted'      => 'Obitelj mjerenja uspješno je obrisana.',
            'mass_deleted' => 'Odabrane obitelji mjerenja uspješno su obrisane.',
        ],

        'unit' => [
            'not_found'         => 'Obitelj mjerenja nije pronađena.',
            'already_exists'    => 'Kod jedinice već postoji.',
            'not_foundd'        => 'Jedinica nije pronađena.',
            'deleted'           => 'Jedinica je uspješno obrisana.',
            'no_items_selected' => 'Nijedna stavka nije odabrana.',
            'mass_deleted'      => 'Odabrane mjerne jedinice uspješno su obrisane.',
        ],
    ],

];
