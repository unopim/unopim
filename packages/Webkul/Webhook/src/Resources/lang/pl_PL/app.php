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
            'view'        => 'Zobacz',
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
                        'label'             => 'URL Webhooka',
                        'required'          => 'Adres URL Webhooka jest wymagany, gdy Webhook jest aktywny.',
                        'scheme'            => 'Adres URL Webhooka musi zaczynać się od http:// lub https://.',
                        'connection_failed' => 'Nie można połączyć się z adresem URL Webhooka. Sprawdź adres URL.',
                        'unreachable'       => 'Adres URL Webhooka jest nieprawidłowy (HTTP :code).',
                        'unsafe'            => 'Adres URL webhooka wskazuje na adres prywatny, loopback lub wewnętrzny i nie jest dozwolony.',
                    ],
                    'success'    => 'Ustawienia Webhooka zostały pomyślnie zapisane',
                    'logs-title' => 'Logi',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Czas',
                        'user'             => 'Użytkownik',
                        'status'           => 'Status',
                        'success'          => 'Sukces',
                        'failed'           => 'Niepowodzenie',
                        'server_error'     => 'Błąd serwera',
                        'timeout_or_error' => 'Przekroczenie czasu/Błąd',
                        'delete'           => 'Usuń',
                        'view'             => 'Zobacz',
                    ],
                    'title'          => 'Logi Webhooka',
                    'show-title'     => 'Szczegóły logu Webhook',
                    'sent-payload'   => 'Wysłany ładunek',
                    'response'       => 'Odpowiedź',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Brak zarejestrowanego ładunku dla tego logu.',
                    'delete-success' => 'Logi Webhooka zostały pomyślnie usunięte',
                    'delete-failed'  => 'Usuwanie logów Webhooka nie powiodło się niespodziewanie',
                    'unauthorized'   => 'Ta akcja jest nieautoryzowana',
                ],
            ],
        ],
    ],
];
