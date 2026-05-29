<?php

declare(strict_types=1);

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
        'delete-failed' => 'Si us plau, activeu el Webhook des de la configuració',
        'success'       => 'Les dades del producte s\'han enviat al Webhook correctament',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Configuració',
            'update' => 'Actualitzar configuració',
        ],
        'logs' => [
            'index'       => 'Registres',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminació massiva',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Configuració',
                    'title'   => 'Configuració del Webhook',
                    'save'    => 'Desar',
                    'general' => 'General',
                    'active'  => [
                        'label' => 'Webhook actiu',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL del Webhook',
                        'required'          => 'Es requereix una URL del Webhook quan el Webhook està actiu.',
                        'scheme'            => 'L\'URL del Webhook ha de començar amb http:// o https://.',
                        'connection_failed' => 'No s\'ha pogut accedir a l\'URL del Webhook. Verifiqueu l\'URL.',
                        'unreachable'       => 'L\'URL del Webhook no és vàlid (HTTP :code).',
                        'unsafe'            => 'L\'URL del webhook apunta a una adreça privada, de loopback o interna i no està permès.',
                    ],
                    'success'    => 'La configuració del Webhook s\'ha desat correctament',
                    'logs-title' => 'Registres',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Usuari',
                        'status'           => 'Estat',
                        'success'          => 'Èxit',
                        'failed'           => 'Fallat',
                        'server_error'     => 'Error del servidor',
                        'timeout_or_error' => 'Temps d\'espera/Error',
                        'delete'           => 'Eliminar',
                    ],
                    'title'          => 'Registres del Webhook',
                    'delete-success' => 'Els registres del Webhook s\'han eliminat correctament',
                    'delete-failed'  => 'L\'eliminació dels registres del Webhook ha fallat inesperadament',
                ],
            ],
        ],
    ],
];
