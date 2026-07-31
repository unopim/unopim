<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhookit',
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
            'index'  => 'Webhook',
            'create' => 'Luo',
            'edit'   => 'Muokkaa',
            'delete' => 'Poista',
        ],
        'logs' => [
            'index'       => 'Lokit',
            'view'        => 'Näytä',
            'delete'      => 'Poista',
            'mass-delete' => 'Joukkopoisto',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Tuote luotu',
            'updated' => 'Tuote päivitetty',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhookit',
            'create-btn'   => 'Luo Webhook',
            'logs-btn'     => 'Lokit',
            'back-btn'     => 'Takaisin Webhookeihin',
            'default-name' => 'Oletus',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nimi',
                'url'        => 'URL',
                'events'     => 'Tapahtumat',
                'status'     => 'Tila',
                'active'     => 'Aktiivinen',
                'inactive'   => 'Ei-aktiivinen',
                'created_at' => 'Luotu',
                'edit'       => 'Muokkaa',
                'delete'     => 'Poista',
            ],
        ],
        'create' => [
            'title'    => 'Luo Webhook',
            'save-btn' => 'Tallenna',
        ],
        'edit' => [
            'title'    => 'Muokkaa Webhookia',
            'save-btn' => 'Tallenna',
        ],
        'form' => [
            'general'       => 'Yleiset',
            'name'          => 'Nimi',
            'url'           => 'URL',
            'events'        => 'Tapahtumat',
            'select-events' => 'Valitse tapahtumat',
            'secret'        => 'Allekirjoitussalaisuus',
            'secret-set'    => 'Salaisuus on jo asetettu',
            'secret-hint'   => 'Käytetään jokaisen hyötykuorman allekirjoittamiseen HMAC SHA-256 -allekirjoituksella. Jätä tyhjäksi säilyttääksesi nykyisen salaisuuden.',
            'settings'      => 'Asetukset',
            'active'        => 'Aktiivinen',
            'test'          => 'Testaa yhteys',
            'test-hint'     => 'Lähetä testipyyntö yllä olevaan URL-osoitteeseen.',
            'test-btn'      => 'Lähetä testi',
            'test-no-url'   => 'Anna ensin URL-osoite.',
            'test-failed'   => 'Testipyyntö epäonnistui.',
            'headers'       => 'Mukautetut otsikot',
            'add-header'    => 'Lisää otsikko',
            'no-headers'    => 'Mukautettuja otsikoita ei ole lisätty.',
            'header-key'    => 'Otsikko',
            'header-value'  => 'Arvo',
        ],
        'create-success' => 'Webhook luotu onnistuneesti',
        'update-success' => 'Webhook päivitetty onnistuneesti',
        'delete-success' => 'Webhook poistettu onnistuneesti',
        'delete-failed'  => 'Webhookin poisto epäonnistui',
        'validation'     => [
            'unsafe-url' => 'URL-osoite osoittaa yksityiseen, loopback- tai sisäiseen osoitteeseen, eikä sitä sallita.',
            'scheme'     => 'URL-osoitteen on alettava http:// tai https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook -testipyyntö',
            'connection-failed' => 'URL-osoitteeseen ei saatu yhteyttä. Tarkista URL-osoite.',
            'unreachable'       => 'URL-osoitetta ei tavoiteta (HTTP :code).',
            'reachable'         => 'URL-osoite on tavoitettavissa.',
        ],
        'prune' => [
            'disabled' => 'Webhook-lokien säilytys on poistettu käytöstä; mitään ei poistettu.',
            'done'     => 'Poistettu :count webhook-loki(a), jotka ovat yli :days päivä(ä) vanhoja.',
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
                        'event'            => 'Tapahtuma',
                        'created_at'       => 'Päivämäärä/Aika',
                        'user'             => 'Käyttäjä',
                        'status'           => 'Tila',
                        'success'          => 'Onnistunut',
                        'failed'           => 'Epäonnistunut',
                        'server_error'     => 'Palvelinvirhe',
                        'timeout_or_error' => 'Aikakatkaisu/Virhe',
                        'delete'           => 'Poista',
                        'view'             => 'Näytä',
                    ],
                    'title'          => 'Webhook-lokit',
                    'show-title'     => 'Webhook-lokitiedot',
                    'sent-payload'   => 'Lähetetty hyötykuorma',
                    'response'       => 'Vastaus',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Tälle lokimerkinnälle ei ole tallennettu hyötykuormaa.',
                    'load-failed'    => 'Lokitietojen lataaminen epäonnistui.',
                    'delete-success' => 'Webhook-lokit poistettu onnistuneesti',
                    'delete-failed'  => 'Webhook-lokien poisto epäonnistui odottamattomasti',
                    'unauthorized'   => 'Tätä toimintoa ei ole valtuutettu',
                ],
            ],
        ],
    ],
];
