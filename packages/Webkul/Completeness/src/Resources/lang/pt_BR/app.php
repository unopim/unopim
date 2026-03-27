<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completude',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completude atualizada com sucesso',
                    'title'               => 'Completude',
                    'configure'           => 'Configurar completude',
                    'channel-required'    => 'Obrigatório nos canais',
                    'save-btn'            => 'Salvar',
                    'back-btn'            => 'Voltar',
                    'mass-update-success' => 'Completude atualizada com sucesso',
                    'datagrid'            => [
                        'code'             => 'Código',
                        'name'             => 'Nome',
                        'channel-required' => 'Obrigatório nos canais',
                        'actions'          => [
                            'change-requirement' => 'Alterar requisito de completude',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Completo',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Completude',
                    'subtitle' => 'Completude média',
                ],
                'required-attributes' => 'atributos obrigatórios ausentes',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Cálculo de completude concluído',
        'completeness-calculated'        => 'Completude calculada para :count produtos.',
        'completeness-calculated-family' => 'Completude calculada para :count produtos na família ":family".',
        'email-subject'                  => 'Cálculo de completude concluído',
        'email-greeting'                 => 'Olá,',
        'email-body'                     => 'O cálculo de completude foi concluído para :count produtos.',
        'email-body-family'              => 'O cálculo de completude foi concluído para :count produtos na família de atributos ":family".',
        'email-footer'                   => 'Você pode visualizar os detalhes de completude no seu painel.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produtos calculados',
                'suggestion'          => [
                    'low'     => 'Completude baixa, adicione detalhes para melhorar.',
                    'medium'  => 'Continue, siga adicionando informações.',
                    'high'    => 'Quase completo, faltam apenas alguns detalhes.',
                    'perfect' => 'As informações do produto estão totalmente completas.',
                ],
            ],
        ],
    ],
];
