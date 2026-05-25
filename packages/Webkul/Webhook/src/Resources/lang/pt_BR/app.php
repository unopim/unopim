<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook',
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
                        'label'             => 'URL do Webhook',
                        'required'          => 'Uma URL do Webhook é necessária quando o Webhook está ativo.',
                        'scheme'            => 'A URL do Webhook deve começar com http:// ou https://.',
                        'connection_failed' => 'Não foi possível acessar a URL do Webhook. Verifique a URL.',
                        'unreachable'       => 'A URL do Webhook não é válida (HTTP :code).',
                    ],
                    'success'    => 'Configurações do Webhook salvas com sucesso',
                    'logs-title' => 'Registros',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Usuário',
                        'status'           => 'Status',
                        'success'          => 'Sucesso',
                        'failed'           => 'Falhou',
                        'server_error'     => 'Erro do servidor',
                        'timeout_or_error' => 'Tempo esgotado/Erro',
                        'delete'           => 'Excluir',
                    ],
                    'title'          => 'Registros do Webhook',
                    'delete-success' => 'Registros do Webhook excluídos com sucesso',
                    'delete-failed'  => 'A exclusão dos registros do Webhook falhou inesperadamente',
                ],
            ],
        ],
    ],
];
