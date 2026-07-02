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
        'delete-failed' => 'Por favor, ative o Webhook nas definições',
        'success'       => 'Os dados do produto foram enviados para o Webhook com sucesso',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Definições',
            'update' => 'Atualizar definições',
        ],
        'logs' => [
            'index'       => 'Registos',
            'view'        => 'Ver',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminação em massa',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Definições',
                    'title'   => 'Definições do Webhook',
                    'save'    => 'Guardar',
                    'general' => 'Geral',
                    'active'  => [
                        'label' => 'Webhook ativo',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL do Webhook',
                        'required'          => 'É necessário um URL do Webhook quando o Webhook está ativo.',
                        'scheme'            => 'O URL do Webhook deve começar com http:// ou https://.',
                        'connection_failed' => 'Não foi possível aceder ao URL do Webhook. Verifique o URL.',
                        'unreachable'       => 'O URL do Webhook não é válido (HTTP :code).',
                        'unsafe'            => 'O URL do webhook aponta para um endereço privado, de loopback ou interno e não é permitido.',
                    ],
                    'success'    => 'Definições do Webhook guardadas com sucesso',
                    'logs-title' => 'Registos',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Utilizador',
                        'status'           => 'Estado',
                        'success'          => 'Sucesso',
                        'failed'           => 'Falhado',
                        'server_error'     => 'Erro do servidor',
                        'timeout_or_error' => 'Tempo esgotado/Erro',
                        'delete'           => 'Eliminar',
                        'view'             => 'Ver',
                    ],
                    'title'          => 'Registos do Webhook',
                    'show-title'     => 'Detalhes do Registo do Webhook',
                    'sent-payload'   => 'Payload Enviado',
                    'response'       => 'Resposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Nenhum payload registado para este registo.',
                    'delete-success' => 'Registos do Webhook eliminados com sucesso',
                    'delete-failed'  => 'A eliminação dos registos do Webhook falhou inesperadamente',
                    'unauthorized'   => 'Esta ação não está autorizada',
                ],
            ],
        ],
    ],
];
