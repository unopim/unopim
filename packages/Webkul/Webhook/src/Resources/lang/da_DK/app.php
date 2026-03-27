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
                        'label' => 'Webhook URL',
                    ],
                    'success'    => 'Webhook-indstillinger blev gemt',
                    'logs-title' => 'Logfiler',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Dato/Tid',
                        'user'       => 'Bruger',
                        'status'     => 'Status',
                        'success'    => 'Succes',
                        'failed'     => 'Fejlet',
                        'delete'     => 'Slet',
                    ],
                    'title'          => 'Webhook-logfiler',
                    'delete-success' => 'Webhook-logfiler blev slettet',
                    'delete-failed'  => 'Sletning af Webhook-logfiler mislykkedes uventet',
                ],
            ],
        ],
    ],
];
