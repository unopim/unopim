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
        'delete-failed' => 'Ota Webhook käyttöön asetuksista',
        'success'       => 'Tuotetiedot lähetettiin Webhookiin onnistuneesti',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Asetukset',
            'update' => 'Päivitä asetukset',
        ],
        'logs' => [
            'index'       => 'Lokit',
            'view'        => 'View',
            'delete'      => 'Poista',
            'mass-delete' => 'Joukkopoisto',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Asetukset',
                    'title'   => 'Webhook-asetukset',
                    'save'    => 'Tallenna',
                    'general' => 'Yleiset',
                    'active'  => [
                        'label' => 'Aktiivinen Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'Webhook-URL',
                    ],
                    'success'    => 'Webhook-asetukset tallennettu onnistuneesti',
                    'logs-title' => 'Lokit',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Päivämäärä/Aika',
                        'user'       => 'Käyttäjä',
                        'status'     => 'Tila',
                        'success'    => 'Onnistunut',
                        'failed'     => 'Epäonnistunut',
                        'delete'     => 'Poista',
                        'view'       => 'View',
                    ],
                    'title'          => 'Webhook-lokit',
                    'show-title'     => 'Webhook-lokitiedot',
                    'sent-payload'   => 'Lähetetty hyötykuorma',
                    'response'       => 'Vastaus',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Tälle lokimerkinnälle ei ole tallennettu hyötykuormaa.',
                    'delete-success' => 'Webhook-lokit poistettu onnistuneesti',
                    'delete-failed'  => 'Webhook-lokien poisto epäonnistui odottamattomasti',
                ],
            ],
        ],
    ],
];
