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
                        'label' => 'URL do Webhook',
                    ],
                    'success'    => 'Definições do Webhook guardadas com sucesso',
                    'logs-title' => 'Registos',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Data/Hora',
                        'user'       => 'Utilizador',
                        'status'     => 'Estado',
                        'success'    => 'Sucesso',
                        'failed'     => 'Falhado',
                        'delete'     => 'Eliminar',
                    ],
                    'title'          => 'Registos do Webhook',
                    'delete-success' => 'Registos do Webhook eliminados com sucesso',
                    'delete-failed'  => 'A eliminação dos registos do Webhook falhou inesperadamente',
                ],
            ],
        ],
    ],
];
