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
        'delete-failed' => 'Molimo omogućite Webhook u postavkama',
        'success'       => 'Podaci o proizvodu uspješno poslani na Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Postavke',
            'update' => 'Ažuriraj postavke',
        ],
        'logs' => [
            'index'       => 'Zapisi',
            'delete'      => 'Obriši',
            'mass-delete' => 'Masovno brisanje',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Postavke',
                    'title'   => 'Postavke Webhooka',
                    'save'    => 'Spremi',
                    'general' => 'Općenito',
                    'active'  => [
                        'label' => 'Aktivan Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook URL',
                        'required'          => 'Webhook URL je obavezan kada je Webhook aktivan.',
                        'scheme'            => 'Webhook URL mora počinjati s http:// ili https://.',
                        'connection_failed' => 'Nije moguće pristupiti Webhook URL-u. Provjerite URL.',
                        'unreachable'       => 'Webhook URL nije ispravan (HTTP :code).',
                    ],
                    'success'    => 'Postavke Webhooka uspješno spremljene',
                    'logs-title' => 'Zapisi',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Datum/Vrijeme',
                        'user'             => 'Korisnik',
                        'status'           => 'Status',
                        'success'          => 'Uspjeh',
                        'failed'           => 'Neuspjeh',
                        'server_error'     => 'Pogreška poslužitelja',
                        'timeout_or_error' => 'Istek vremena/Pogreška',
                        'delete'           => 'Obriši',
                    ],
                    'title'          => 'Zapisi Webhooka',
                    'delete-success' => 'Zapisi Webhooka uspješno obrisani',
                    'delete-failed'  => 'Brisanje zapisa Webhooka neočekivano nije uspjelo',
                ],
            ],
        ],
    ],
];
