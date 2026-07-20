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
        'delete-failed' => 'Por favor, active el Webhook desde la configuración',
        'success'       => 'Los datos del producto se enviaron al Webhook correctamente',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Crear',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],
        'settings' => [
            'index'  => 'Configuración',
            'update' => 'Actualizar configuración',
        ],
        'logs' => [
            'index'       => 'Registros',
            'view'        => 'Ver',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminación masiva',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Producto creado',
            'updated' => 'Producto actualizado',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Crear Webhook',
            'logs-btn'     => 'Registros',
            'back-btn'     => 'Volver a los Webhooks',
            'default-name' => 'Predeterminado',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nombre',
                'url'        => 'URL',
                'events'     => 'Eventos',
                'status'     => 'Estado',
                'active'     => 'Activo',
                'inactive'   => 'Inactivo',
                'created_at' => 'Creado el',
                'edit'       => 'Editar',
                'delete'     => 'Eliminar',
            ],
        ],
        'create' => [
            'title'    => 'Crear Webhook',
            'cancel'   => 'Cancelar',
            'save-btn' => 'Guardar',
        ],
        'edit' => [
            'title'    => 'Editar Webhook',
            'cancel'   => 'Cancelar',
            'save-btn' => 'Guardar',
        ],
        'form' => [
            'general'       => 'General',
            'name'          => 'Nombre',
            'url'           => 'URL',
            'events'        => 'Eventos',
            'select-events' => 'Seleccionar eventos',
            'secret'        => 'Secreto de firma',
            'secret-set'    => 'Ya hay un secreto configurado',
            'secret-hint'   => 'Se utiliza para firmar cada carga útil con una firma HMAC SHA-256. Déjelo en blanco para mantener el secreto actual.',
            'settings'      => 'Configuración',
            'active'        => 'Activo',
            'test'          => 'Probar conexión',
            'test-hint'     => 'Envíe una solicitud de prueba a la URL anterior.',
            'test-btn'      => 'Enviar prueba',
            'test-no-url'   => 'Introduzca primero una URL.',
            'test-failed'   => 'La solicitud de prueba falló.',
            'headers'       => 'Cabeceras personalizadas',
            'add-header'    => 'Añadir cabecera',
            'no-headers'    => 'No se han añadido cabeceras personalizadas.',
            'header-key'    => 'Cabecera',
            'header-value'  => 'Valor',
        ],
        'create-success' => 'Webhook creado correctamente',
        'update-success' => 'Webhook actualizado correctamente',
        'delete-success' => 'Webhook eliminado correctamente',
        'delete-failed'  => 'La eliminación del Webhook falló',
        'validation'     => [
            'unsafe-url' => 'La URL apunta a una dirección privada, de loopback o interna y no está permitida.',
            'scheme'     => 'La URL debe comenzar con http:// o https://.',
        ],
        'test' => [
            'payload-message'   => 'Solicitud de prueba del webhook de Unopim',
            'connection-failed' => 'No se pudo acceder a la URL. Verifique la URL.',
            'unreachable'       => 'No se puede acceder a la URL (HTTP :code).',
            'reachable'         => 'Se puede acceder a la URL.',
        ],
        'prune' => [
            'disabled' => 'La retención de registros del webhook está desactivada; no se eliminó nada.',
            'done'     => 'Se eliminaron :count registro(s) de webhook con más de :days día(s) de antigüedad.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Configuración',
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
                        'unsafe'            => 'La URL del webhook apunta a una dirección privada, de loopback o interna y no está permitida.',
                    ],
                    'success'    => 'Configuración del Webhook guardada correctamente',
                    'title'      => 'Configuración del Webhook',
                    'logs-title' => 'Registros',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Evento',
                        'created_at'       => 'Fecha/Hora',
                        'user'             => 'Usuario',
                        'status'           => 'Estado',
                        'success'          => 'Éxito',
                        'failed'           => 'Fallido',
                        'server_error'     => 'Error del servidor',
                        'timeout_or_error' => 'Tiempo de espera/Error',
                        'delete'           => 'Eliminar',
                        'view'             => 'Ver',
                    ],
                    'title'          => 'Registros del Webhook',
                    'show-title'     => 'Detalles del registro del Webhook',
                    'sent-payload'   => 'Carga útil enviada',
                    'response'       => 'Respuesta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'No se registró ninguna carga útil para este registro.',
                    'load-failed'    => 'No se pudieron cargar los detalles del registro.',
                    'delete-success' => 'Registros del Webhook eliminados correctamente',
                    'delete-failed'  => 'La eliminación de los registros del Webhook falló inesperadamente',
                    'unauthorized'   => 'Esta acción no está autorizada',
                ],
            ],
        ],
    ],
];
