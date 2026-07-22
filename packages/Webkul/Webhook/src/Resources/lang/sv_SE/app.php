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
        'delete-failed' => 'Vänligen aktivera Webhook från inställningarna',
        'success'       => 'Produktdata skickades till Webhook framgångsrikt',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Skapa',
            'edit'   => 'Redigera',
            'delete' => 'Radera',
        ],
        'settings' => [
            'index'  => 'Inställningar',
            'update' => 'Uppdatera inställningar',
        ],
        'logs' => [
            'index'       => 'Loggar',
            'view'        => 'Visa',
            'delete'      => 'Radera',
            'mass-delete' => 'Massradering',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produkt skapad',
            'updated' => 'Produkt uppdaterad',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Skapa webhook',
            'logs-btn'     => 'Loggar',
            'back-btn'     => 'Tillbaka till webhooks',
            'default-name' => 'Standard',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Namn',
                'url'        => 'URL',
                'events'     => 'Händelser',
                'status'     => 'Status',
                'active'     => 'Aktiv',
                'inactive'   => 'Inaktiv',
                'created_at' => 'Skapad',
                'edit'       => 'Redigera',
                'delete'     => 'Radera',
            ],
        ],
        'create' => [
            'title'    => 'Skapa webhook',
            'cancel'   => 'Avbryt',
            'save-btn' => 'Spara',
        ],
        'edit' => [
            'title'    => 'Redigera webhook',
            'cancel'   => 'Avbryt',
            'save-btn' => 'Spara',
        ],
        'form' => [
            'general'       => 'Allmänt',
            'name'          => 'Namn',
            'url'           => 'URL',
            'events'        => 'Händelser',
            'select-events' => 'Välj händelser',
            'secret'        => 'Signeringshemlighet',
            'secret-set'    => 'En hemlighet är redan angiven',
            'secret-hint'   => 'Används för att signera varje nyttolast med en HMAC SHA-256-signatur. Lämna tomt för att behålla den nuvarande hemligheten.',
            'settings'      => 'Inställningar',
            'active'        => 'Aktiv',
            'test'          => 'Testa anslutning',
            'test-hint'     => 'Skicka en testförfrågan till URL:en ovan.',
            'test-btn'      => 'Skicka test',
            'test-no-url'   => 'Ange en URL först.',
            'test-failed'   => 'Testförfrågan misslyckades.',
            'headers'       => 'Anpassade rubriker',
            'add-header'    => 'Lägg till rubrik',
            'no-headers'    => 'Inga anpassade rubriker tillagda.',
            'header-key'    => 'Rubrik',
            'header-value'  => 'Värde',
        ],
        'create-success' => 'Webhook skapades framgångsrikt',
        'update-success' => 'Webhook uppdaterades framgångsrikt',
        'delete-success' => 'Webhook raderades framgångsrikt',
        'delete-failed'  => 'Radering av webhook misslyckades',
        'validation'     => [
            'unsafe-url' => 'URL:en pekar på en privat, loopback- eller intern adress och är inte tillåten.',
            'scheme'     => 'URL:en måste börja med http:// eller https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook-testförfrågan',
            'connection-failed' => 'URL:en kunde inte nås. Kontrollera URL:en.',
            'unreachable'       => 'URL:en är inte nåbar (HTTP :code).',
            'reachable'         => 'URL:en är nåbar.',
        ],
        'prune' => [
            'disabled' => 'Lagring av webhook-loggar är inaktiverad; inget rensades.',
            'done'     => 'Rensade :count webhook-logg(ar) äldre än :days dag(ar).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Inställningar',
                    'save'    => 'Spara',
                    'general' => 'Allmänt',
                    'active'  => [
                        'label' => 'Aktiv Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook-URL',
                        'required'          => 'En Webhook-URL krävs när webhooken är aktiv.',
                        'scheme'            => 'Webhook-URL:en måste börja med http:// eller https://.',
                        'connection_failed' => 'Webhook-URL:en kunde inte nås. Kontrollera URL:en.',
                        'unreachable'       => 'Webhook-URL:en är inte giltig (HTTP :code).',
                        'unsafe'            => 'Webhook-URL:en pekar på en privat, loopback- eller intern adress och är inte tillåten.',
                    ],
                    'success'    => 'Webhook-inställningar sparades framgångsrikt',
                    'title'      => 'Webhook-inställningar',
                    'logs-title' => 'Loggar',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Händelse',
                        'created_at'       => 'Datum/Tid',
                        'user'             => 'Användare',
                        'status'           => 'Status',
                        'success'          => 'Lyckades',
                        'failed'           => 'Misslyckades',
                        'server_error'     => 'Serverfel',
                        'timeout_or_error' => 'Tidsgräns/Fel',
                        'delete'           => 'Radera',
                        'view'             => 'Visa',
                    ],
                    'title'          => 'Webhook-loggar',
                    'show-title'     => 'Webhook-loggdetaljer',
                    'sent-payload'   => 'Skickad nyttolast',
                    'response'       => 'Svar',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Ingen nyttolast registrerad för den här loggen.',
                    'load-failed'    => 'Det gick inte att läsa in loggdetaljer.',
                    'delete-success' => 'Webhook-loggar raderades framgångsrikt',
                    'delete-failed'  => 'Radering av Webhook-loggar misslyckades oväntat',
                    'unauthorized'   => 'Denna åtgärd är inte tillåten',
                ],
            ],
        ],
    ],
];
