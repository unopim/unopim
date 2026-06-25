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
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Instellingen',
            'update' => 'Instellingen bijwerken',
        ],
        'logs' => [
            'index'       => 'Logboeken',
            'view'        => 'View',
            'delete'      => 'Verwijderen',
            'mass-delete' => 'Massaal verwijderen',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Instellingen',
                    'title'   => 'Webhook-instellingen',
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
                    'logs-title' => 'Logboeken',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Datum/Tijd',
                        'user'       => 'Gebruiker',
                        'status'     => 'Status',
                        'success'    => 'Geslaagd',
                        'failed'     => 'Mislukt',
                        'delete'     => 'Verwijderen',
                        'view'       => 'View',
                    ],
                    'title'          => 'Webhook-logboeken',
                    'show-title'     => 'Webhook Log Details',
                    'sent-payload'   => 'Verzonden payload',
                    'response'       => 'Reactie',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Geen payload opgenomen voor dit logboek.',
                    'delete-success' => 'Webhook-logboeken succesvol verwijderd.',
                    'delete-failed'  => 'Het verwijderen van webhook-logboeken is onverwacht mislukt.',
                ],
            ],
        ],
    ],
];
