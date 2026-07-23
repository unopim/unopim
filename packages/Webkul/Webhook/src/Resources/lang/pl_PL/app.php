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
            'index'  => 'Webhook',
            'create' => 'Utwórz',
            'edit'   => 'Edytuj',
            'delete' => 'Usuń',
        ],
        'logs' => [
            'index'       => 'Logi',
            'view'        => 'Zobacz',
            'delete'      => 'Usuń',
            'mass-delete' => 'Usuwanie masowe',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produkt utworzony',
            'updated' => 'Produkt zaktualizowany',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooki',
            'create-btn'   => 'Utwórz Webhook',
            'logs-btn'     => 'Logi',
            'back-btn'     => 'Powrót do Webhooków',
            'default-name' => 'Domyślny',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Nazwa',
                'url'        => 'URL',
                'events'     => 'Zdarzenia',
                'status'     => 'Status',
                'active'     => 'Aktywny',
                'inactive'   => 'Nieaktywny',
                'created_at' => 'Utworzono',
                'edit'       => 'Edytuj',
                'delete'     => 'Usuń',
            ],
        ],
        'create' => [
            'title'    => 'Utwórz Webhook',
            'save-btn' => 'Zapisz',
        ],
        'edit' => [
            'title'    => 'Edytuj Webhook',
            'save-btn' => 'Zapisz',
        ],
        'form' => [
            'general'       => 'Ogólne',
            'name'          => 'Nazwa',
            'url'           => 'URL',
            'events'        => 'Zdarzenia',
            'select-events' => 'Wybierz zdarzenia',
            'secret'        => 'Sekret podpisujący',
            'secret-set'    => 'Sekret został już ustawiony',
            'secret-hint'   => 'Używany do podpisywania każdego ładunku podpisem HMAC SHA-256. Pozostaw puste, aby zachować bieżący sekret.',
            'settings'      => 'Ustawienia',
            'active'        => 'Aktywny',
            'test'          => 'Testuj połączenie',
            'test-hint'     => 'Wyślij żądanie testowe na powyższy adres URL.',
            'test-btn'      => 'Wyślij test',
            'test-no-url'   => 'Najpierw wprowadź adres URL.',
            'test-failed'   => 'Żądanie testowe nie powiodło się.',
            'headers'       => 'Nagłówki niestandardowe',
            'add-header'    => 'Dodaj nagłówek',
            'no-headers'    => 'Nie dodano nagłówków niestandardowych.',
            'header-key'    => 'Nagłówek',
            'header-value'  => 'Wartość',
        ],
        'create-success' => 'Webhook został pomyślnie utworzony',
        'update-success' => 'Webhook został pomyślnie zaktualizowany',
        'delete-success' => 'Webhook został pomyślnie usunięty',
        'delete-failed'  => 'Usuwanie Webhooka nie powiodło się',
        'validation'     => [
            'unsafe-url' => 'Adres URL wskazuje na adres prywatny, loopback lub wewnętrzny i nie jest dozwolony.',
            'scheme'     => 'Adres URL musi zaczynać się od http:// lub https://.',
        ],
        'test' => [
            'payload-message'   => 'Żądanie testowe webhooka Unopim',
            'connection-failed' => 'Nie można połączyć się z adresem URL. Sprawdź adres URL.',
            'unreachable'       => 'Adres URL jest nieosiągalny (HTTP :code).',
            'reachable'         => 'Adres URL jest osiągalny.',
        ],
        'prune' => [
            'disabled' => 'Przechowywanie logów webhooków jest wyłączone; nic nie zostało usunięte.',
            'done'     => 'Usunięto :count log(ów) webhooka starszych niż :days dni.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Zdarzenie',
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
                    'load-failed'    => 'Nie udało się załadować szczegółów logu.',
                    'delete-success' => 'Logi Webhooka zostały pomyślnie usunięte',
                    'delete-failed'  => 'Usuwanie logów Webhooka nie powiodło się niespodziewanie',
                    'unauthorized'   => 'Ta akcja jest nieautoryzowana',
                ],
            ],
        ],
    ],
];
