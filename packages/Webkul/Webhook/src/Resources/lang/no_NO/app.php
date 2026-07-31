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
        'delete-failed' => 'Vennligst aktiver Webhook fra innstillingene',
        'success'       => 'Produktdataene ble sendt til Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Opprett',
            'edit'   => 'Rediger',
            'delete' => 'Slett',
        ],
        'logs' => [
            'index'       => 'Logger',
            'view'        => 'Vis',
            'delete'      => 'Slett',
            'mass-delete' => 'Massesletting',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produkt opprettet',
            'updated' => 'Produkt oppdatert',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Opprett Webhook',
            'logs-btn'     => 'Logger',
            'back-btn'     => 'Tilbake til Webhooks',
            'default-name' => 'Standard',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Navn',
                'url'        => 'URL',
                'events'     => 'Hendelser',
                'status'     => 'Status',
                'active'     => 'Aktiv',
                'inactive'   => 'Inaktiv',
                'created_at' => 'Opprettet',
                'edit'       => 'Rediger',
                'delete'     => 'Slett',
            ],
        ],
        'create' => [
            'title'    => 'Opprett Webhook',
            'save-btn' => 'Lagre',
        ],
        'edit' => [
            'title'    => 'Rediger Webhook',
            'save-btn' => 'Lagre',
        ],
        'form' => [
            'general'       => 'Generelt',
            'name'          => 'Navn',
            'url'           => 'URL',
            'events'        => 'Hendelser',
            'select-events' => 'Velg hendelser',
            'secret'        => 'Signeringshemmelighet',
            'secret-set'    => 'En hemmelighet er allerede angitt',
            'secret-hint'   => 'Brukes til å signere hver nyttelast med en HMAC SHA-256-signatur. La feltet stå tomt for å beholde den nåværende hemmeligheten.',
            'settings'      => 'Innstillinger',
            'active'        => 'Aktiv',
            'test'          => 'Test tilkobling',
            'test-hint'     => 'Send en testforespørsel til URL-en ovenfor.',
            'test-btn'      => 'Send test',
            'test-no-url'   => 'Vennligst skriv inn en URL først.',
            'test-failed'   => 'Testforespørselen mislyktes.',
            'headers'       => 'Egendefinerte headere',
            'add-header'    => 'Legg til header',
            'no-headers'    => 'Ingen egendefinerte headere lagt til.',
            'header-key'    => 'Header',
            'header-value'  => 'Verdi',
        ],
        'create-success' => 'Webhook opprettet',
        'update-success' => 'Webhook oppdatert',
        'delete-success' => 'Webhook slettet',
        'delete-failed'  => 'Sletting av Webhook mislyktes',
        'validation'     => [
            'unsafe-url' => 'URL-en peker til en privat, loopback- eller intern adresse og er ikke tillatt.',
            'scheme'     => 'URL-en må begynne med http:// eller https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook-testforespørsel',
            'connection-failed' => 'URL-en kunne ikke nås. Vennligst sjekk URL-en.',
            'unreachable'       => 'URL-en er ikke tilgjengelig (HTTP :code).',
            'reachable'         => 'URL-en er tilgjengelig.',
        ],
        'prune' => [
            'disabled' => 'Oppbevaring av webhook-logger er deaktivert; ingenting ble slettet.',
            'done'     => 'Slettet :count webhook-logg(er) eldre enn :days dag(er).',
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
                        'event'            => 'Hendelse',
                        'created_at'       => 'Dato/Tid',
                        'user'             => 'Bruker',
                        'status'           => 'Status',
                        'success'          => 'Vellykket',
                        'failed'           => 'Mislyktes',
                        'server_error'     => 'Serverfeil',
                        'timeout_or_error' => 'Tidsavbrudd/Feil',
                        'delete'           => 'Slett',
                        'view'             => 'Vis',
                    ],
                    'title'          => 'Webhook-logger',
                    'show-title'     => 'Webhook Log Detaljer',
                    'sent-payload'   => 'Sendt nyttelast',
                    'response'       => 'Svar',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Ingen nyttelast registrert for denne loggen.',
                    'load-failed'    => 'Kunne ikke laste inn loggdetaljer.',
                    'delete-success' => 'Webhook-logger slettet',
                    'delete-failed'  => 'Sletting av Webhook-logger mislyktes uventet',
                    'unauthorized'   => 'Denne handlingen er ikke autorisert',
                ],
            ],
        ],
    ],
];
