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
                        'label'             => 'URL Webhook',
                        'required'          => 'Un URL Webhook este obligatoriu când Webhook-ul este activ.',
                        'scheme'            => 'URL-ul Webhook trebuie să înceapă cu http:// sau https://.',
                        'connection_failed' => 'URL-ul Webhook nu a putut fi accesat. Verificați URL-ul.',
                        'unreachable'       => 'URL-ul Webhook nu este valid (HTTP :code).',
                    ],
                    'success'    => 'Setările Webhook au fost salvate cu succes',
                    'logs-title' => 'Jurnale',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Ora',
                        'user'             => 'Utilizator',
                        'status'           => 'Stare',
                        'success'          => 'Succes',
                        'failed'           => 'Eșuat',
                        'server_error'     => 'Eroare server',
                        'timeout_or_error' => 'Expirare/Eroare',
                        'delete'           => 'Ștergere',
                    ],
                    'title'          => 'Jurnale Webhook',
                    'delete-success' => 'Jurnalele Webhook au fost șterse cu succes',
                    'delete-failed'  => 'Ștergerea jurnalelor Webhook a eșuat în mod neașteptat',
                ],
            ],
        ],
    ],
];
