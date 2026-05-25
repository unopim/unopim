<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhooki',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Proszę włączyć Webhook w ustawieniach',
        'success'       => 'Dane produktu zostały pomyślnie wysłane do Webhooka',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Ustawienia',
            'update' => 'Zaktualizuj ustawienia',
        ],
        'logs' => [
            'index'       => 'Logi',
            'delete'      => 'Usuń',
            'mass-delete' => 'Usuwanie masowe',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Ustawienia',
                    'title'   => 'Ustawienia Webhooka',
                    'save'    => 'Zapisz',
                    'general' => 'Ogólne',
                    'active'  => [
                        'label' => 'Aktywny Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'URL Webhooka',
                    ],
                    'success'    => 'Ustawienia Webhooka zostały pomyślnie zapisane',
                    'logs-title' => 'Logi',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Data/Czas',
                        'user'       => 'Użytkownik',
                        'status'     => 'Status',
                        'success'    => 'Sukces',
                        'failed'     => 'Niepowodzenie',
                        'delete'     => 'Usuń',
                    ],
                    'title'          => 'Logi Webhooka',
                    'delete-success' => 'Logi Webhooka zostały pomyślnie usunięte',
                    'delete-failed'  => 'Usuwanie logów Webhooka nie powiodło się niespodziewanie',
                ],
            ],
        ],
    ],
];
