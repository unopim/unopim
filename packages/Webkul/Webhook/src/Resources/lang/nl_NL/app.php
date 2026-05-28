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
                    ],
                    'success'    => 'Webhook-instellingen succesvol opgeslagen.',
                    'logs-title' => 'Logboeken',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Datum/Tijd',
                        'user'             => 'Gebruiker',
                        'status'           => 'Status',
                        'success'          => 'Geslaagd',
                        'failed'           => 'Mislukt',
                        'server_error'     => 'Serverfout',
                        'timeout_or_error' => 'Time-out/Fout',
                        'delete'           => 'Verwijderen',
                    ],
                    'title'          => 'Webhook-logboeken',
                    'delete-success' => 'Webhook-logboeken succesvol verwijderd.',
                    'delete-failed'  => 'Het verwijderen van webhook-logboeken is onverwacht mislukt.',
                ],
            ],
        ],
    ],
];
