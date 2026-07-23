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
            'index'  => 'Webhook',
            'create' => 'Opret',
            'edit'   => 'Rediger',
            'delete' => 'Slet',
        ],
        'logs' => [
            'index'       => 'Logfiler',
            'view'        => 'Vis',
            'delete'      => 'Slet',
            'mass-delete' => 'Massesletning',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produkt oprettet',
            'updated' => 'Produkt opdateret',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Opret Webhook',
            'logs-btn'     => 'Logfiler',
            'back-btn'     => 'Tilbage til Webhooks',
            'default-name' => 'Standard',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Navn',
                'url'        => 'URL',
                'events'     => 'Hændelser',
                'status'     => 'Status',
                'active'     => 'Aktiv',
                'inactive'   => 'Inaktiv',
                'created_at' => 'Oprettet den',
                'edit'       => 'Rediger',
                'delete'     => 'Slet',
            ],
        ],
        'create' => [
            'title'    => 'Opret Webhook',
            'save-btn' => 'Gem',
        ],
        'edit' => [
            'title'    => 'Rediger Webhook',
            'save-btn' => 'Gem',
        ],
        'form' => [
            'general'       => 'Generelt',
            'name'          => 'Navn',
            'url'           => 'URL',
            'events'        => 'Hændelser',
            'select-events' => 'Vælg hændelser',
            'secret'        => 'Signeringshemmelighed',
            'secret-set'    => 'En hemmelighed er allerede angivet',
            'secret-hint'   => 'Bruges til at signere hver nyttelast med en HMAC SHA-256-signatur. Lad feltet stå tomt for at beholde den nuværende hemmelighed.',
            'settings'      => 'Indstillinger',
            'active'        => 'Aktiv',
            'test'          => 'Test forbindelse',
            'test-hint'     => 'Send en testanmodning til URL\'en ovenfor.',
            'test-btn'      => 'Send test',
            'test-no-url'   => 'Indtast venligst en URL først.',
            'test-failed'   => 'Testanmodningen mislykkedes.',
            'headers'       => 'Brugerdefinerede headere',
            'add-header'    => 'Tilføj header',
            'no-headers'    => 'Ingen brugerdefinerede headere tilføjet.',
            'header-key'    => 'Header',
            'header-value'  => 'Værdi',
        ],
        'create-success' => 'Webhook oprettet',
        'update-success' => 'Webhook opdateret',
        'delete-success' => 'Webhook slettet',
        'delete-failed'  => 'Sletning af Webhook mislykkedes',
        'validation'     => [
            'unsafe-url' => 'URL\'en peger på en privat, loopback eller intern adresse og er ikke tilladt.',
            'scheme'     => 'URL\'en skal starte med http:// eller https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook-testanmodning',
            'connection-failed' => 'URL\'en kunne ikke nås. Tjek venligst URL\'en.',
            'unreachable'       => 'URL\'en kan ikke nås (HTTP :code).',
            'reachable'         => 'URL\'en kan nås.',
        ],
        'prune' => [
            'disabled' => 'Opbevaring af webhook-logfiler er deaktiveret; intet blev slettet.',
            'done'     => 'Slettede :count webhook-log(ge) ældre end :days dag(e).',
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
                        'event'            => 'Hændelse',
                        'created_at'       => 'Dato/Tid',
                        'user'             => 'Bruger',
                        'status'           => 'Status',
                        'success'          => 'Succes',
                        'failed'           => 'Fejlet',
                        'server_error'     => 'Serverfejl',
                        'timeout_or_error' => 'Timeout/Fejl',
                        'delete'           => 'Slet',
                        'view'             => 'Vis',
                    ],
                    'title'          => 'Webhook-logfiler',
                    'show-title'     => 'Webhook Log Detaljer',
                    'sent-payload'   => 'Sendt nyttelast',
                    'response'       => 'Svar',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Ingen nyttelast registreret for denne log.',
                    'load-failed'    => 'Kunne ikke indlæse logdetaljer.',
                    'delete-success' => 'Webhook-logfiler blev slettet',
                    'delete-failed'  => 'Sletning af Webhook-logfiler mislykkedes uventet',
                    'unauthorized'   => 'Denne handling er ikke tilladt',
                ],
            ],
        ],
    ],
];
