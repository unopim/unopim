<?php

return [

    'attribute' => [
        'measurement' => 'Medição',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Criar Família de Medição',
            'code'     => 'Código',
            'standard' => 'Código da Unidade Padrão',
            'symbol'   => 'Símbolo',
            'save'     => 'Guardar',
        ],

        'edit' => [
            'measurement_edit' => 'Editar Família de Medição',
            'back'             => 'Voltar',
            'save'             => 'Guardar',
            'general'          => 'Geral',
            'code'             => 'Código',
            'label'            => 'Rótulo',
            'units'            => 'Unidades',
            'create_units'     => 'Criar Unidades',
        ],

        'unit' => [
            'edit_unit'   => 'Editar Unidade',
            'create_unit' => 'Criar Unidade',
            'symbol'      => 'Símbolo',
            'save'        => 'Guardar',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Famílias de Medição',
        'measurement_family'   => 'Família de Medição',
        'measurement_unit'     => 'Unidade de Medição',
    ],

    'datagrid' => [
        'labels'        => 'Rótulos',
        'code'          => 'Código',
        'standard_unit' => 'Unidade Padrão',
        'unit_count'    => 'Número de Unidades',
        'is_standard'   => 'Marcar como Unidade Padrão',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Família de medição atualizada com sucesso.',
            'deleted'      => 'Família de medição eliminada com sucesso.',
            'mass_deleted' => 'Famílias de medição selecionadas eliminadas com sucesso.',
        ],

        'unit' => [
            'not_found'         => 'Família de medição não encontrada.',
            'already_exists'    => 'Código da unidade já existe.',
            'not_foundd'        => 'Unidade não encontrada.',
            'deleted'           => 'Unidade eliminada com sucesso.',
            'no_items_selected' => 'Nenhum item selecionado.',
            'mass_deleted'      => 'Unidades de medição selecionadas eliminadas com sucesso.',
        ],
    ],

];
