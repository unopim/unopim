<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhookovi',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Molimo omogućite Webhook u postavkama',
        'success'       => 'Podaci o proizvodu uspješno poslani na Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Stvori',
            'edit'   => 'Uredi',
            'delete' => 'Obriši',
        ],
        'settings' => [
            'index'  => 'Postavke',
            'update' => 'Ažuriraj postavke',
        ],
        'logs' => [
            'index'       => 'Zapisi',
            'view'        => 'Pogledaj',
            'delete'      => 'Obriši',
            'mass-delete' => 'Masovno brisanje',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Proizvod stvoren',
            'updated' => 'Proizvod ažuriran',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhookovi',
            'create-btn'   => 'Stvori Webhook',
            'logs-btn'     => 'Zapisi',
            'back-btn'     => 'Natrag na Webhookove',
            'default-name' => 'Zadano',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Naziv',
                'url'        => 'URL',
                'events'     => 'Događaji',
                'status'     => 'Status',
                'active'     => 'Aktivan',
                'inactive'   => 'Neaktivan',
                'created_at' => 'Stvoreno',
                'edit'       => 'Uredi',
                'delete'     => 'Obriši',
            ],
        ],
        'create' => [
            'title'    => 'Stvori Webhook',
            'cancel'   => 'Odustani',
            'save-btn' => 'Spremi',
        ],
        'edit' => [
            'title'    => 'Uredi Webhook',
            'cancel'   => 'Odustani',
            'save-btn' => 'Spremi',
        ],
        'form' => [
            'general'       => 'Općenito',
            'name'          => 'Naziv',
            'url'           => 'URL',
            'events'        => 'Događaji',
            'select-events' => 'Odaberite događaje',
            'secret'        => 'Tajni ključ za potpisivanje',
            'secret-set'    => 'Tajni ključ je već postavljen',
            'secret-hint'   => 'Koristi se za potpisivanje svakog sadržaja HMAC SHA-256 potpisom. Ostavite prazno kako biste zadržali trenutni tajni ključ.',
            'settings'      => 'Postavke',
            'active'        => 'Aktivan',
            'test'          => 'Testiraj vezu',
            'test-hint'     => 'Pošaljite testni zahtjev na gornji URL.',
            'test-btn'      => 'Pošalji test',
            'test-no-url'   => 'Najprije unesite URL.',
            'test-failed'   => 'Testni zahtjev nije uspio.',
            'headers'       => 'Prilagođena zaglavlja',
            'add-header'    => 'Dodaj zaglavlje',
            'no-headers'    => 'Nema dodanih prilagođenih zaglavlja.',
            'header-key'    => 'Zaglavlje',
            'header-value'  => 'Vrijednost',
        ],
        'create-success' => 'Webhook uspješno stvoren',
        'update-success' => 'Webhook uspješno ažuriran',
        'delete-success' => 'Webhook uspješno obrisan',
        'delete-failed'  => 'Brisanje Webhooka nije uspjelo',
        'validation'     => [
            'unsafe-url' => 'URL upućuje na privatnu, loopback ili internu adresu i nije dopušten.',
            'scheme'     => 'URL mora počinjati s http:// ili https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook testni zahtjev',
            'connection-failed' => 'Nije moguće pristupiti URL-u. Provjerite URL.',
            'unreachable'       => 'URL nije dostupan (HTTP :code).',
            'reachable'         => 'URL je dostupan.',
        ],
        'prune' => [
            'disabled' => 'Zadržavanje webhook zapisa je onemogućeno; ništa nije obrisano.',
            'done'     => 'Obrisano :count webhook zapis(a) starijih od :days dan(a).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Postavke',
                    'save'    => 'Spremi',
                    'general' => 'Općenito',
                    'active'  => [
                        'label' => 'Aktivan Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook URL',
                        'required'          => 'Webhook URL je obavezan kada je Webhook aktivan.',
                        'scheme'            => 'Webhook URL mora počinjati s http:// ili https://.',
                        'connection_failed' => 'Nije moguće pristupiti Webhook URL-u. Provjerite URL.',
                        'unreachable'       => 'Webhook URL nije ispravan (HTTP :code).',
                        'unsafe'            => 'Webhook URL upućuje na privatnu, loopback ili internu adresu i nije dopušten.',
                    ],
                    'success'    => 'Postavke Webhooka uspješno spremljene',
                    'title'      => 'Postavke Webhooka',
                    'logs-title' => 'Zapisi',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Događaj',
                        'created_at'       => 'Datum/Vrijeme',
                        'user'             => 'Korisnik',
                        'status'           => 'Status',
                        'success'          => 'Uspjeh',
                        'failed'           => 'Neuspjeh',
                        'server_error'     => 'Pogreška poslužitelja',
                        'timeout_or_error' => 'Istek vremena/Pogreška',
                        'delete'           => 'Obriši',
                        'view'             => 'Pogledaj',
                    ],
                    'title'          => 'Zapisi Webhooka',
                    'show-title'     => 'Detalji Webhook zapisa',
                    'sent-payload'   => 'Poslani sadržaj',
                    'response'       => 'Odgovor',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Za ovaj zapis nije zabilježen sadržaj.',
                    'load-failed'    => 'Učitavanje detalja zapisa nije uspjelo.',
                    'delete-success' => 'Zapisi Webhooka uspješno obrisani',
                    'delete-failed'  => 'Brisanje zapisa Webhooka neočekivano nije uspjelo',
                    'unauthorized'   => 'Ova radnja nije ovlaštena',
                ],
            ],
        ],
    ],
];
