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
        'delete-failed' => 'Si us plau, activeu el Webhook des de la configuració',
        'success'       => 'Les dades del producte s\'han enviat al Webhook correctament',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Crear',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],
        'settings' => [
            'index'  => 'Configuració',
            'update' => 'Actualitzar configuració',
        ],
        'logs' => [
            'index'       => 'Registres',
            'view'        => 'Veure',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminació massiva',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Producte creat',
            'updated' => 'Producte actualitzat',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Crear Webhook',
            'logs-btn'     => 'Registres',
            'back-btn'     => 'Tornar als Webhooks',
            'default-name' => 'Predeterminat',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nom',
                'url'        => 'URL',
                'events'     => 'Esdeveniments',
                'status'     => 'Estat',
                'active'     => 'Actiu',
                'inactive'   => 'Inactiu',
                'created_at' => 'Creat el',
                'edit'       => 'Editar',
                'delete'     => 'Eliminar',
            ],
        ],
        'create' => [
            'title'    => 'Crear Webhook',
            'cancel'   => 'Cancel·lar',
            'save-btn' => 'Desar',
        ],
        'edit' => [
            'title'    => 'Editar Webhook',
            'cancel'   => 'Cancel·lar',
            'save-btn' => 'Desar',
        ],
        'form' => [
            'general'       => 'General',
            'name'          => 'Nom',
            'url'           => 'URL',
            'events'        => 'Esdeveniments',
            'select-events' => 'Seleccionar esdeveniments',
            'secret'        => 'Secret de signatura',
            'secret-set'    => 'Ja hi ha un secret configurat',
            'secret-hint'   => 'S\'utilitza per signar cada càrrega amb una signatura HMAC SHA-256. Deixeu-ho en blanc per mantenir el secret actual.',
            'settings'      => 'Configuració',
            'active'        => 'Actiu',
            'test'          => 'Provar connexió',
            'test-hint'     => 'Envieu una sol·licitud de prova a l\'URL anterior.',
            'test-btn'      => 'Enviar prova',
            'test-no-url'   => 'Introduïu primer una URL.',
            'test-failed'   => 'La sol·licitud de prova ha fallat.',
            'headers'       => 'Capçaleres personalitzades',
            'add-header'    => 'Afegir capçalera',
            'no-headers'    => 'No s\'han afegit capçaleres personalitzades.',
            'header-key'    => 'Capçalera',
            'header-value'  => 'Valor',
        ],
        'create-success' => 'Webhook creat correctament',
        'update-success' => 'Webhook actualitzat correctament',
        'delete-success' => 'Webhook eliminat correctament',
        'delete-failed'  => 'L\'eliminació del Webhook ha fallat',
        'validation'     => [
            'unsafe-url' => 'L\'URL apunta a una adreça privada, de loopback o interna i no està permès.',
            'scheme'     => 'L\'URL ha de començar amb http:// o https://.',
        ],
        'test' => [
            'payload-message'   => 'Sol·licitud de prova del webhook d\'Unopim',
            'connection-failed' => 'No s\'ha pogut accedir a l\'URL. Verifiqueu l\'URL.',
            'unreachable'       => 'L\'URL no és accessible (HTTP :code).',
            'reachable'         => 'L\'URL és accessible.',
        ],
        'prune' => [
            'disabled' => 'La retenció de registres del webhook està desactivada; no s\'ha eliminat res.',
            'done'     => 'S\'han eliminat :count registre(s) de webhook més antics de :days dia(es).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Configuració',
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
                    'title'      => 'Configuració del Webhook',
                    'logs-title' => 'Registres',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Esdeveniment',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Usuari',
                        'status'           => 'Estat',
                        'success'          => 'Èxit',
                        'failed'           => 'Fallat',
                        'server_error'     => 'Error del servidor',
                        'timeout_or_error' => 'Temps d\'espera/Error',
                        'delete'           => 'Eliminar',
                        'view'             => 'Veure',
                    ],
                    'title'          => 'Registres del Webhook',
                    'show-title'     => 'Detalls del registre del Webhook',
                    'sent-payload'   => 'Càrrega enviada',
                    'response'       => 'Resposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'No s\'ha registrat cap càrrega per a aquest registre.',
                    'load-failed'    => 'No s\'han pogut carregar els detalls del registre.',
                    'delete-success' => 'Els registres del Webhook s\'han eliminat correctament',
                    'delete-failed'  => 'L\'eliminació dels registres del Webhook ha fallat inesperadament',
                    'unauthorized'   => 'Aquesta acció no està autoritzada',
                ],
            ],
        ],
    ],
];
