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
            'view'        => 'Visa',
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
                        'label'             => 'Webhook-URL',
                        'required'          => 'En Webhook-URL krävs när webhooken är aktiv.',
                        'scheme'            => 'Webhook-URL:en måste börja med http:// eller https://.',
                        'connection_failed' => 'Webhook-URL:en kunde inte nås. Kontrollera URL:en.',
                        'unreachable'       => 'Webhook-URL:en är inte giltig (HTTP :code).',
                        'unsafe'            => 'Webhook-URL:en pekar på en privat, loopback- eller intern adress och är inte tillåten.',
                    ],
                    'success'    => 'Webhook-inställningar sparades framgångsrikt',
                    'logs-title' => 'Loggar',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
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
                    'delete-success' => 'Webhook-loggar raderades framgångsrikt',
                    'delete-failed'  => 'Radering av Webhook-loggar misslyckades oväntat',
                    'unauthorized'   => 'Denna åtgärd är inte tillåten',
                ],
            ],
        ],
    ],
];
