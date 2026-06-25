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
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Inställningar',
            'update' => 'Uppdatera inställningar',
        ],
        'logs' => [
            'index'       => 'Loggar',
            'view'        => 'View',
            'delete'      => 'Radera',
            'mass-delete' => 'Massradering',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Inställningar',
                    'title'   => 'Webhook-inställningar',
                    'save'    => 'Spara',
                    'general' => 'Allmänt',
                    'active'  => [
                        'label' => 'Aktiv Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'Webhook-URL',
                    ],
                    'success'    => 'Webhook-inställningar sparades framgångsrikt',
                    'logs-title' => 'Loggar',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Datum/Tid',
                        'user'       => 'Användare',
                        'status'     => 'Status',
                        'success'    => 'Lyckades',
                        'failed'     => 'Misslyckades',
                        'delete'     => 'Radera',
                        'view'       => 'View',
                    ],
                    'title'          => 'Webhook-loggar',
                    'show-title'     => 'Webhook-loggdetaljer',
                    'sent-payload'   => 'Skickad nyttolast',
                    'response'       => 'Svar',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Ingen nyttolast registrerad för den här loggen.',
                    'delete-success' => 'Webhook-loggar raderades framgångsrikt',
                    'delete-failed'  => 'Radering av Webhook-loggar misslyckades oväntat',
                ],
            ],
        ],
    ],
];
