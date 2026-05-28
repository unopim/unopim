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
        'delete-failed' => 'Vennligst aktiver Webhook fra innstillingene',
        'success'       => 'Produktdataene ble sendt til Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Innstillinger',
            'update' => 'Oppdater innstillinger',
        ],
        'logs' => [
            'index'       => 'Logger',
            'delete'      => 'Slett',
            'mass-delete' => 'Massesletting',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Innstillinger',
                    'title'   => 'Webhook-innstillinger',
                    'save'    => 'Lagre',
                    'general' => 'Generelt',
                    'active'  => [
                        'label' => 'Aktiv Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook-URL',
                        'required'          => 'En Webhook-URL er påkrevd når webhooken er aktiv.',
                        'scheme'            => 'Webhook-URL-en må begynne med http:// eller https://.',
                        'connection_failed' => 'Webhook-URL-en kunne ikke nås. Sjekk URL-en.',
                        'unreachable'       => 'Webhook-URL-en er ikke gyldig (HTTP :code).',
                    ],
                    'success'    => 'Webhook-innstillinger lagret',
                    'logs-title' => 'Logger',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Dato/Tid',
                        'user'             => 'Bruker',
                        'status'           => 'Status',
                        'success'          => 'Vellykket',
                        'failed'           => 'Mislyktes',
                        'server_error'     => 'Serverfeil',
                        'timeout_or_error' => 'Tidsavbrudd/Feil',
                        'delete'           => 'Slett',
                    ],
                    'title'          => 'Webhook-logger',
                    'delete-success' => 'Webhook-logger slettet',
                    'delete-failed'  => 'Sletting av Webhook-logger mislyktes uventet',
                ],
            ],
        ],
    ],
];
