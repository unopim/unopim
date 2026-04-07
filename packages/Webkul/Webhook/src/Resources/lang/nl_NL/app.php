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
        'delete-failed' => 'Schakel de Webhook in via de instellingen',
        'success'       => 'De productgegevens zijn succesvol naar de Webhook verzonden',
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
                        'label' => 'Actieve Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'Webhook-URL',
                    ],
                    'success'    => 'Webhook-instellingen succesvol opgeslagen',
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
                    ],
                    'title'          => 'Webhook-logboeken',
                    'delete-success' => 'Webhook-logboeken succesvol verwijderd',
                    'delete-failed'  => 'Het verwijderen van Webhook-logboeken is onverwacht mislukt',
                ],
            ],
        ],
    ],
];
