<?php

return [

    'attribute' => [
        'measurement' => 'Medición',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Crear familia de medición',
            'code'     => 'Código',
            'standard' => 'Código de la unidad estándar',
            'symbol'   => 'Símbolo',
            'save'     => 'Guardar',
        ],

        'edit' => [
            'measurement_edit' => 'Editar familia de medición',
            'back'             => 'Volver',
            'save'             => 'Guardar',
            'general'          => 'General',
            'code'             => 'Código',
            'label'            => 'Etiqueta',
            'units'            => 'Unidades',
            'create_units'     => 'Crear unidades',
        ],

        'unit' => [
            'edit_unit'   => 'Editar unidad',
            'create_unit' => 'Crear unidad',
            'symbol'      => 'Símbolo',
            'save'        => 'Guardar',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Familias de medición',
        'measurement_family'   => 'Familia de medición',
        'measurement_unit'     => 'Unidad de medición',
    ],

    'datagrid' => [
        'labels'        => 'Etiquetas',
        'code'          => 'Código',
        'standard_unit' => 'Unidad estándar',
        'unit_count'    => 'Número de unidades',
        'is_standard'   => 'Marcar como unidad estándar',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'La familia de medición se actualizó correctamente.',
            'deleted'      => 'La familia de medición se eliminó correctamente.',
            'mass_deleted' => 'Las familias de medición seleccionadas se eliminaron correctamente.',
        ],

        'unit' => [
            'not_found'         => 'No se encontró la familia de medición.',
            'already_exists'    => 'El código de la unidad ya existe.',
            'not_foundd'        => 'No se encontró la unidad.',
            'deleted'           => 'La unidad se eliminó correctamente.',
            'no_items_selected' => 'No se seleccionaron elementos.',
            'mass_deleted'      => 'Las unidades de medición seleccionadas se eliminaron correctamente.',
        ],
    ],

];
