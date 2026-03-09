<?php

return [

    'attribute' => [
        'measurement' => 'Mesura',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Creyar familia de medidas',
            'code'     => 'Codigo',
            'standard' => 'Codigo d’a unidat estandar',
            'symbol'   => 'Símbolo',
            'save'     => 'Alzar',
        ],

        'edit' => [
            'measurement_edit' => 'Editar familia de medidas',
            'back'             => 'Tornar',
            'save'             => 'Alzar',
            'general'          => 'Cheneral',
            'code'             => 'Codigo',
            'label'            => 'Etiqueta',
            'units'            => 'Unidatz',
            'create_units'     => 'Creyar unidatz',
        ],

        'unit' => [
            'edit_unit'   => 'Editar unidat',
            'create_unit' => 'Creyar unidat',
            'symbol'      => 'Símbolo',
            'save'        => 'Alzar',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Familias de medidas',
        'measurement_family'   => 'Familia de medidas',
        'measurement_unit'     => 'Unidat de medida',
    ],

    'datagrid' => [
        'labels'        => 'Etiquetas',
        'code'          => 'Codigo',
        'standard_unit' => 'Unidat estandar',
        'unit_count'    => 'Numero d’unidatz',
        'is_standard'   => 'Marcar como unidat estandar',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'A familia de medidas s’ha actualizau correctament.',
            'deleted'      => 'A familia de medidas s’ha borrau correctament.',
            'mass_deleted' => 'As familias de medidas seleccionadas s’han borrau correctament.',
        ],

        'unit' => [
            'not_found'         => 'No s’ha trobau a familia de medidas.',
            'already_exists'    => 'O codigo d’a unidat ya existe.',
            'not_foundd'        => 'No s’ha trobau a unidat.',
            'deleted'           => 'A unidat s’ha borrau correctament.',
            'no_items_selected' => 'No i hai garra elemento seleccionau.',
            'mass_deleted'      => 'As unidatz de medida seleccionadas s’han borrau correctament.',
        ],
    ],

];
