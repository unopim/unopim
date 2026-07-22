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
        'delete-failed' => 'Schakel de webhook eerst uit om hem te kunnen verwijderen.',
        'success'       => 'De productgegevens zijn succesvol naar de webhook verzonden.',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Aanmaken',
            'edit'   => 'Bewerken',
            'delete' => 'Verwijderen',
        ],
        'settings' => [
            'index'  => 'Instellingen',
            'update' => 'Instellingen bijwerken',
        ],
        'logs' => [
            'index'       => 'Logboeken',
            'view'        => 'Bekijken',
            'delete'      => 'Verwijderen',
            'mass-delete' => 'Massaal verwijderen',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Product aangemaakt',
            'updated' => 'Product bijgewerkt',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Webhook aanmaken',
            'logs-btn'     => 'Logboeken',
            'back-btn'     => 'Terug naar webhooks',
            'default-name' => 'Standaard',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Naam',
                'url'        => 'URL',
                'events'     => 'Gebeurtenissen',
                'status'     => 'Status',
                'active'     => 'Actief',
                'inactive'   => 'Inactief',
                'created_at' => 'Aangemaakt op',
                'edit'       => 'Bewerken',
                'delete'     => 'Verwijderen',
            ],
        ],
        'create' => [
            'title'    => 'Webhook aanmaken',
            'cancel'   => 'Annuleren',
            'save-btn' => 'Opslaan',
        ],
        'edit' => [
            'title'    => 'Webhook bewerken',
            'cancel'   => 'Annuleren',
            'save-btn' => 'Opslaan',
        ],
        'form' => [
            'general'       => 'Algemeen',
            'name'          => 'Naam',
            'url'           => 'URL',
            'events'        => 'Gebeurtenissen',
            'select-events' => 'Selecteer gebeurtenissen',
            'secret'        => 'Ondertekeningsgeheim',
            'secret-set'    => 'Er is al een geheim ingesteld',
            'secret-hint'   => 'Wordt gebruikt om elke payload te ondertekenen met een HMAC SHA-256-handtekening. Laat leeg om het huidige geheim te behouden.',
            'settings'      => 'Instellingen',
            'active'        => 'Actief',
            'test'          => 'Verbinding testen',
            'test-hint'     => 'Stuur een testverzoek naar de bovenstaande URL.',
            'test-btn'      => 'Test verzenden',
            'test-no-url'   => 'Voer eerst een URL in.',
            'test-failed'   => 'Het testverzoek is mislukt.',
            'headers'       => 'Aangepaste headers',
            'add-header'    => 'Header toevoegen',
            'no-headers'    => 'Geen aangepaste headers toegevoegd.',
            'header-key'    => 'Header',
            'header-value'  => 'Waarde',
        ],
        'create-success' => 'Webhook succesvol aangemaakt',
        'update-success' => 'Webhook succesvol bijgewerkt',
        'delete-success' => 'Webhook succesvol verwijderd',
        'delete-failed'  => 'Verwijderen van webhook mislukt',
        'validation'     => [
            'unsafe-url' => 'De URL verwijst naar een privé-, loopback- of intern adres en is niet toegestaan.',
            'scheme'     => 'De URL moet beginnen met http:// of https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook-testverzoek',
            'connection-failed' => 'De URL kon niet worden bereikt. Controleer de URL.',
            'unreachable'       => 'De URL is niet bereikbaar (HTTP :code).',
            'reachable'         => 'De URL is bereikbaar.',
        ],
        'prune' => [
            'disabled' => 'Het bewaren van webhook-logboeken is uitgeschakeld; er is niets opgeschoond.',
            'done'     => ':count webhook-logboek(en) ouder dan :days dag(en) opgeschoond.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Instellingen',
                    'save'    => 'Opslaan',
                    'general' => 'Algemeen',
                    'active'  => [
                        'label' => 'Actieve webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook-URL',
                        'required'          => 'Een Webhook-URL is vereist wanneer de webhook actief is.',
                        'scheme'            => 'De Webhook-URL moet beginnen met http:// of https://.',
                        'connection_failed' => 'De Webhook-URL kon niet worden bereikt. Controleer de URL.',
                        'unreachable'       => 'De Webhook-URL is niet geldig (HTTP :code).',
                        'unsafe'            => 'De Webhook-URL verwijst naar een privé-, loopback- of intern adres en is niet toegestaan.',
                    ],
                    'success'    => 'Webhook-instellingen succesvol opgeslagen.',
                    'title'      => 'Webhook-instellingen',
                    'logs-title' => 'Logboeken',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Gebeurtenis',
                        'created_at'       => 'Datum/Tijd',
                        'user'             => 'Gebruiker',
                        'status'           => 'Status',
                        'success'          => 'Geslaagd',
                        'failed'           => 'Mislukt',
                        'server_error'     => 'Serverfout',
                        'timeout_or_error' => 'Time-out/Fout',
                        'delete'           => 'Verwijderen',
                        'view'             => 'Bekijken',
                    ],
                    'title'          => 'Webhook-logboeken',
                    'show-title'     => 'Webhook Log Details',
                    'sent-payload'   => 'Verzonden payload',
                    'response'       => 'Reactie',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Geen payload opgenomen voor dit logboek.',
                    'load-failed'    => 'Kon de logdetails niet laden.',
                    'delete-success' => 'Webhook-logboeken succesvol verwijderd.',
                    'delete-failed'  => 'Het verwijderen van webhook-logboeken is onverwacht mislukt.',
                    'unauthorized'   => 'Deze actie is niet toegestaan',
                ],
            ],
        ],
    ],
];
