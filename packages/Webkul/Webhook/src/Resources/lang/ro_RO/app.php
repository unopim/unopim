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
        'delete-failed' => 'Vă rugăm să activați Webhook din setări',
        'success'       => 'Datele produsului au fost trimise cu succes către Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Setări',
            'update' => 'Actualizare setări',
        ],
        'logs' => [
            'index'       => 'Jurnale',
            'delete'      => 'Ștergere',
            'mass-delete' => 'Ștergere în masă',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Setări',
                    'title'   => 'Setări Webhook',
                    'save'    => 'Salvează',
                    'general' => 'General',
                    'active'  => [
                        'label' => 'Webhook activ',
                    ],
                    'webhook_url' => [
                        'label' => 'URL Webhook',
                    ],
                    'success'    => 'Setările Webhook au fost salvate cu succes',
                    'logs-title' => 'Jurnale',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Data/Ora',
                        'user'       => 'Utilizator',
                        'status'     => 'Stare',
                        'success'    => 'Succes',
                        'failed'     => 'Eșuat',
                        'delete'     => 'Ștergere',
                    ],
                    'title'          => 'Jurnale Webhook',
                    'delete-success' => 'Jurnalele Webhook au fost șterse cu succes',
                    'delete-failed'  => 'Ștergerea jurnalelor Webhook a eșuat în mod neașteptat',
                ],
            ],
        ],
    ],
];
