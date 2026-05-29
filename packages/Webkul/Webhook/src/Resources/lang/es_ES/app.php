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
        'delete-failed' => 'Por favor, active el Webhook desde la configuración',
        'success'       => 'Los datos del producto se enviaron al Webhook correctamente',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Configuración',
            'update' => 'Actualizar configuración',
        ],
        'logs' => [
            'index'       => 'Registros',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminación masiva',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Configuración',
                    'title'   => 'Configuración del Webhook',
                    'save'    => 'Guardar',
                    'general' => 'General',
                    'active'  => [
                        'label' => 'Webhook activo',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL del Webhook',
                        'required'          => 'Se requiere una URL del Webhook cuando el Webhook está activo.',
                        'scheme'            => 'La URL del Webhook debe comenzar con http:// o https://.',
                        'connection_failed' => 'No se pudo acceder a la URL del Webhook. Verifique la URL.',
                        'unreachable'       => 'La URL del Webhook no es válida (HTTP :code).',
                    ],
                    'success'    => 'Configuración del Webhook guardada correctamente',
                    'logs-title' => 'Registros',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Fecha/Hora',
                        'user'             => 'Usuario',
                        'status'           => 'Estado',
                        'success'          => 'Éxito',
                        'failed'           => 'Fallido',
                        'server_error'     => 'Error del servidor',
                        'timeout_or_error' => 'Tiempo de espera/Error',
                        'delete'           => 'Eliminar',
                    ],
                    'title'          => 'Registros del Webhook',
                    'delete-success' => 'Registros del Webhook eliminados correctamente',
                    'delete-failed'  => 'La eliminación de los registros del Webhook falló inesperadamente',
                ],
            ],
        ],
    ],
];
