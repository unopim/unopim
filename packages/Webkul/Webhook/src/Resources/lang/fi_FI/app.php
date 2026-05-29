<?php

declare(strict_types=1);

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
                        'label'             => 'Webhook-URL',
                        'required'          => 'Webhook-URL vaaditaan, kun webhook on käytössä.',
                        'scheme'            => 'Webhook-URL:n on alettava http:// tai https://.',
                        'connection_failed' => 'Webhook-URL-osoitteeseen ei saatu yhteyttä. Tarkista URL-osoite.',
                        'unreachable'       => 'Webhook-URL ei kelpaa (HTTP :code).',
                        'unsafe'            => 'Webhook-URL osoittaa yksityiseen, loopback- tai sisäiseen osoitteeseen, eikä sitä sallita.',
                    ],
                    'success'    => 'Webhook-asetukset tallennettu onnistuneesti',
                    'logs-title' => 'Lokit',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Päivämäärä/Aika',
                        'user'             => 'Käyttäjä',
                        'status'           => 'Tila',
                        'success'          => 'Onnistunut',
                        'failed'           => 'Epäonnistunut',
                        'server_error'     => 'Palvelinvirhe',
                        'timeout_or_error' => 'Aikakatkaisu/Virhe',
                        'delete'           => 'Poista',
                    ],
                    'title'          => 'Webhook-lokit',
                    'delete-success' => 'Webhook-lokit poistettu onnistuneesti',
                    'delete-failed'  => 'Webhook-lokien poisto epäonnistui odottamattomasti',
                ],
            ],
        ],
    ],
];
