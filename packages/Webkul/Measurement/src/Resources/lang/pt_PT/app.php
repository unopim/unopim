<?php

return [

    'attribute' => [
        'measurement' => 'Medição',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Criar Família de Medição',
            'code'                  => 'Código',
            'standard'              => 'Código da Unidade Padrão',
            'symbol'                => 'Símbolo',
            'save'                  => 'Guardar',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Editar Família de Medição',
            'back'                  => 'Voltar',
            'save'                  => 'Guardar',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Geral',
            'code'                  => 'Código',
            'label'                 => 'Rótulo',
            'units'                 => 'Unidades',
            'create_units'          => 'Criar Unidades',
        ],

        'unit' => [
            'edit_unit'             => 'Editar Unidade',
            'create_unit'           => 'Criar Unidade',
            'symbol'                => 'Símbolo',
            'save'                  => 'Guardar',
            'conversion_operation'  => 'Operação de conversão',
            'add_new_operation'     => 'Adicionar nova operação',
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
            'created'      => 'A família de medições foi criada com sucesso.',
            'updated'      => 'Família de medição atualizada com sucesso.',
            'deleted'      => 'Família de medição eliminada com sucesso.',
            'mass_deleted' => 'Famílias de medição selecionadas eliminadas com sucesso.',
        ],

        'unit' => [
            'not_found'              => 'Família de medição não encontrada.',
            'already_exists'         => 'Código da unidade já existe.',
            'units_not_found'        => 'Unidade não encontrada.',
            'deleted'                => 'Unidade eliminada com sucesso.',
            'no_items_selected'      => 'Nenhum item selecionado.',
            'mass_deleted'           => 'Unidades de medição selecionadas eliminadas com sucesso.',
        ],
    ],

];
