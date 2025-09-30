<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Integridade',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Integridade atualizada com sucesso',
                    'title'               => 'Integridade',
                    'configure'           => 'Configurar integridade',
                    'channel-required'    => 'Necessário nos canais',
                    'save-btn'            => 'Salvar',
                    'back-btn'            => 'Voltar',
                    'mass-update-success' => 'Integridade atualizada com sucesso',

                    'datagrid' => [
                        'code'             => 'Código',
                        'name'             => 'Nome',
                        'channel-required' => 'Necessário nos canais',

                        'actions' => [
                            'change-requirement' => 'Alterar requisito de integridade',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Nenhuma configuração',
                    'completeness'                 => 'Completo',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Integridade',
                    'subtitle' => 'Integridade média',
                ],

                'required-attributes' => 'atributos obrigatórios ausentes',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produtos calculados',

                'suggestion' => [
                    'low'     => 'Baixa integridade — adicione detalhes para melhorar.',
                    'medium'  => 'Continue, continue adicionando informações.',
                    'high'    => 'Quase completo, restam apenas alguns detalhes.',
                    'perfect' => 'As informações do produto estão totalmente completas.',
                ],
            ],
        ],
    ],
];
