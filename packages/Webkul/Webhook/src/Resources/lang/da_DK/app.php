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
        'delete-failed' => 'Venligst aktiver Webhook fra indstillingerne',
        'success'       => 'Produktdata blev sendt til Webhook med succes',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Indstillinger',
            'update' => 'Opdater indstillinger',
        ],
        'logs' => [
            'index'       => 'Logfiler',
            'delete'      => 'Slet',
            'mass-delete' => 'Massesletning',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Indstillinger',
                    'title'   => 'Webhook-indstillinger',
                    'save'    => 'Gem',
                    'general' => 'Generelt',
                    'active'  => [
                        'label' => 'Aktiv Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook URL',
                        'required'          => 'En Webhook-URL er påkrævet, når webhooken er aktiv.',
                        'scheme'            => 'Webhook-URL\'en skal starte med http:// eller https://.',
                        'connection_failed' => 'Webhook-URL\'en kunne ikke nås. Tjek venligst URL\'en.',
                        'unreachable'       => 'Webhook-URL\'en er ikke gyldig (HTTP :code).',
                        'unsafe'            => 'Webhook-URL\'en peger på en privat, loopback eller intern adresse og er ikke tilladt.',
                    ],
                    'success'    => 'Webhook-indstillinger blev gemt',
                    'logs-title' => 'Logfiler',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Dato/Tid',
                        'user'             => 'Bruger',
                        'status'           => 'Status',
                        'success'          => 'Succes',
                        'failed'           => 'Fejlet',
                        'server_error'     => 'Serverfejl',
                        'timeout_or_error' => 'Timeout/Fejl',
                        'delete'           => 'Slet',
                    ],
                    'title'          => 'Webhook-logfiler',
                    'delete-success' => 'Webhook-logfiler blev slettet',
                    'delete-failed'  => 'Sletning af Webhook-logfiler mislykkedes uventet',
                ],
            ],
        ],
    ],
];
