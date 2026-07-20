<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook-uri',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Vă rugăm să activați Webhook din setări',
        'success'       => 'Datele produsului au fost trimise cu succes către Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Creare',
            'edit'   => 'Editare',
            'delete' => 'Ștergere',
        ],
        'settings' => [
            'index'  => 'Setări',
            'update' => 'Actualizare setări',
        ],
        'logs' => [
            'index'       => 'Jurnale',
            'view'        => 'Vizualizare',
            'delete'      => 'Ștergere',
            'mass-delete' => 'Ștergere în masă',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produs creat',
            'updated' => 'Produs actualizat',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhook-uri',
            'create-btn'   => 'Creare Webhook',
            'logs-btn'     => 'Jurnale',
            'back-btn'     => 'Înapoi la Webhook-uri',
            'default-name' => 'Implicit',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Nume',
                'url'        => 'URL',
                'events'     => 'Evenimente',
                'status'     => 'Stare',
                'active'     => 'Activ',
                'inactive'   => 'Inactiv',
                'created_at' => 'Creat la',
                'edit'       => 'Editare',
                'delete'     => 'Ștergere',
            ],
        ],
        'create' => [
            'title'    => 'Creare Webhook',
            'cancel'   => 'Anulare',
            'save-btn' => 'Salvează',
        ],
        'edit' => [
            'title'    => 'Editare Webhook',
            'cancel'   => 'Anulare',
            'save-btn' => 'Salvează',
        ],
        'form' => [
            'general'       => 'General',
            'name'          => 'Nume',
            'url'           => 'URL',
            'events'        => 'Evenimente',
            'select-events' => 'Selectați evenimentele',
            'secret'        => 'Secret de semnare',
            'secret-set'    => 'Un secret este deja setat',
            'secret-hint'   => 'Utilizat pentru a semna fiecare payload cu o semnătură HMAC SHA-256. Lăsați gol pentru a păstra secretul actual.',
            'settings'      => 'Setări',
            'active'        => 'Activ',
            'test'          => 'Testare conexiune',
            'test-hint'     => 'Trimiteți o cerere de test către URL-ul de mai sus.',
            'test-btn'      => 'Trimite test',
            'test-no-url'   => 'Vă rugăm să introduceți mai întâi un URL.',
            'test-failed'   => 'Cererea de test a eșuat.',
            'headers'       => 'Anteturi personalizate',
            'add-header'    => 'Adaugă antet',
            'no-headers'    => 'Nu au fost adăugate anteturi personalizate.',
            'header-key'    => 'Antet',
            'header-value'  => 'Valoare',
        ],
        'create-success' => 'Webhook creat cu succes',
        'update-success' => 'Webhook actualizat cu succes',
        'delete-success' => 'Webhook șters cu succes',
        'delete-failed'  => 'Ștergerea Webhook-ului a eșuat',
        'validation'     => [
            'unsafe-url' => 'URL-ul indică o adresă privată, loopback sau internă și nu este permis.',
            'scheme'     => 'URL-ul trebuie să înceapă cu http:// sau https://.',
        ],
        'test' => [
            'payload-message'   => 'Cerere de test webhook Unopim',
            'connection-failed' => 'URL-ul nu a putut fi accesat. Verificați URL-ul.',
            'unreachable'       => 'URL-ul nu este accesibil (HTTP :code).',
            'reachable'         => 'URL-ul este accesibil.',
        ],
        'prune' => [
            'disabled' => 'Păstrarea jurnalelor webhook este dezactivată; nu a fost șters nimic.',
            'done'     => 'Au fost șterse :count jurnal(e) webhook mai vechi de :days zi(le).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Setări',
                    'save'    => 'Salvează',
                    'general' => 'General',
                    'active'  => [
                        'label' => 'Webhook activ',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL Webhook',
                        'required'          => 'Un URL Webhook este obligatoriu când Webhook-ul este activ.',
                        'scheme'            => 'URL-ul Webhook trebuie să înceapă cu http:// sau https://.',
                        'connection_failed' => 'URL-ul Webhook nu a putut fi accesat. Verificați URL-ul.',
                        'unreachable'       => 'URL-ul Webhook nu este valid (HTTP :code).',
                        'unsafe'            => 'URL-ul webhook indică o adresă privată, loopback sau internă și nu este permis.',
                    ],
                    'success'    => 'Setările Webhook au fost salvate cu succes',
                    'title'      => 'Setări Webhook',
                    'logs-title' => 'Jurnale',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Eveniment',
                        'created_at'       => 'Data/Ora',
                        'user'             => 'Utilizator',
                        'status'           => 'Stare',
                        'success'          => 'Succes',
                        'failed'           => 'Eșuat',
                        'server_error'     => 'Eroare server',
                        'timeout_or_error' => 'Expirare/Eroare',
                        'delete'           => 'Ștergere',
                        'view'             => 'Vizualizare',
                    ],
                    'title'          => 'Jurnale Webhook',
                    'show-title'     => 'Detalii jurnal Webhook',
                    'sent-payload'   => 'Payload trimis',
                    'response'       => 'Răspuns',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Niciun payload înregistrat pentru acest jurnal.',
                    'load-failed'    => 'Încărcarea detaliilor jurnalului a eșuat.',
                    'delete-success' => 'Jurnalele Webhook au fost șterse cu succes',
                    'delete-failed'  => 'Ștergerea jurnalelor Webhook a eșuat în mod neașteptat',
                    'unauthorized'   => 'Această acțiune nu este autorizată',
                ],
            ],
        ],
    ],
];
