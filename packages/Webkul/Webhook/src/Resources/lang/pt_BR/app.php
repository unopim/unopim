<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhooks',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Por favor, ative o Webhook nas configurações',
        'success'       => 'Os dados do produto foram enviados ao Webhook com sucesso',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Configurações',
            'update' => 'Atualizar configurações',
        ],
        'logs' => [
            'index'       => 'Registros',
            'delete'      => 'Excluir',
            'mass-delete' => 'Exclusão em massa',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Configurações',
                    'title'   => 'Configurações do Webhook',
                    'save'    => 'Salvar',
                    'general' => 'Geral',
                    'active'  => [
                        'label' => 'Webhook ativo',
                    ],
                    'webhook_url' => [
                        'label' => 'URL do Webhook',
                    ],
                    'success'    => 'Configurações do Webhook salvas com sucesso',
                    'logs-title' => 'Registros',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Data/Hora',
                        'user'       => 'Usuário',
                        'status'     => 'Status',
                        'success'    => 'Sucesso',
                        'failed'     => 'Falhou',
                        'delete'     => 'Excluir',
                    ],
                    'title'          => 'Registros do Webhook',
                    'delete-success' => 'Registros do Webhook excluídos com sucesso',
                    'delete-failed'  => 'A exclusão dos registros do Webhook falhou inesperadamente',
                ],
            ],
        ],
    ],
];
