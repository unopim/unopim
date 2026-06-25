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
            'view'        => 'View',
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
                        'unsafe'            => 'A URL do webhook aponta para um endereço privado, de loopback ou interno e não é permitida.',
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
                        'view'       => 'View',
                    ],
                    'title'          => 'Registros do Webhook',
                    'show-title'     => 'Detalhes do Log do Webhook',
                    'sent-payload'   => 'Payload Enviado',
                    'response'       => 'Resposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Nenhum payload registrado para este log.',
                    'delete-success' => 'Registros do Webhook excluídos com sucesso',
                    'delete-failed'  => 'A exclusão dos registros do Webhook falhou inesperadamente',
                ],
            ],
        ],
    ],
];
