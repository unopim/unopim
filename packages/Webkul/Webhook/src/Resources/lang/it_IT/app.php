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
        'delete-failed' => 'Si prega di abilitare il Webhook dalle impostazioni',
        'success'       => 'I dati del prodotto sono stati inviati al Webhook con successo',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Crea',
            'edit'   => 'Modifica',
            'delete' => 'Elimina',
        ],
        'settings' => [
            'index'  => 'Impostazioni',
            'update' => 'Aggiorna impostazioni',
        ],
        'logs' => [
            'index'       => 'Registri',
            'view'        => 'Visualizza',
            'delete'      => 'Elimina',
            'mass-delete' => 'Eliminazione di massa',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Prodotto creato',
            'updated' => 'Prodotto aggiornato',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhook',
            'create-btn'   => 'Crea Webhook',
            'logs-btn'     => 'Registri',
            'back-btn'     => 'Torna ai Webhook',
            'default-name' => 'Predefinito',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nome',
                'url'        => 'URL',
                'events'     => 'Eventi',
                'status'     => 'Stato',
                'active'     => 'Attivo',
                'inactive'   => 'Inattivo',
                'created_at' => 'Creato il',
                'edit'       => 'Modifica',
                'delete'     => 'Elimina',
            ],
        ],
        'create' => [
            'title'    => 'Crea Webhook',
            'cancel'   => 'Annulla',
            'save-btn' => 'Salva',
        ],
        'edit' => [
            'title'    => 'Modifica Webhook',
            'cancel'   => 'Annulla',
            'save-btn' => 'Salva',
        ],
        'form' => [
            'general'       => 'Generale',
            'name'          => 'Nome',
            'url'           => 'URL',
            'events'        => 'Eventi',
            'select-events' => 'Seleziona eventi',
            'secret'        => 'Segreto di firma',
            'secret-set'    => 'Un segreto è già impostato',
            'secret-hint'   => 'Utilizzato per firmare ogni payload con una firma HMAC SHA-256. Lascia vuoto per mantenere il segreto attuale.',
            'settings'      => 'Impostazioni',
            'active'        => 'Attivo',
            'test'          => 'Testa connessione',
            'test-hint'     => 'Invia una richiesta di test all\'URL sopra indicato.',
            'test-btn'      => 'Invia test',
            'test-no-url'   => 'Inserisci prima un URL.',
            'test-failed'   => 'La richiesta di test è fallita.',
            'headers'       => 'Intestazioni personalizzate',
            'add-header'    => 'Aggiungi intestazione',
            'no-headers'    => 'Nessuna intestazione personalizzata aggiunta.',
            'header-key'    => 'Intestazione',
            'header-value'  => 'Valore',
        ],
        'create-success' => 'Webhook creato con successo',
        'update-success' => 'Webhook aggiornato con successo',
        'delete-success' => 'Webhook eliminato con successo',
        'delete-failed'  => 'Eliminazione del Webhook fallita',
        'validation'     => [
            'unsafe-url' => 'L\'URL punta a un indirizzo privato, di loopback o interno e non è consentito.',
            'scheme'     => 'L\'URL deve iniziare con http:// o https://.',
        ],
        'test' => [
            'payload-message'   => 'Richiesta di test del webhook Unopim',
            'connection-failed' => 'Impossibile raggiungere l\'URL. Verifica l\'URL.',
            'unreachable'       => 'L\'URL non è raggiungibile (HTTP :code).',
            'reachable'         => 'L\'URL è raggiungibile.',
        ],
        'prune' => [
            'disabled' => 'La conservazione dei registri webhook è disabilitata; nulla è stato eliminato.',
            'done'     => 'Eliminati :count registro/i webhook più vecchi di :days giorno/i.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Impostazioni',
                    'save'    => 'Salva',
                    'general' => 'Generale',
                    'active'  => [
                        'label' => 'Webhook attivo',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL del Webhook',
                        'required'          => 'L\'URL del Webhook è obbligatorio quando il Webhook è attivo.',
                        'scheme'            => 'L\'URL del Webhook deve iniziare con http:// o https://.',
                        'connection_failed' => 'Impossibile raggiungere l\'URL del Webhook. Verifica l\'URL.',
                        'unreachable'       => 'L\'URL del Webhook non è valido (HTTP :code).',
                        'unsafe'            => 'L\'URL del webhook punta a un indirizzo privato, di loopback o interno e non è consentito.',
                    ],
                    'success'    => 'Impostazioni Webhook salvate con successo',
                    'title'      => 'Impostazioni Webhook',
                    'logs-title' => 'Registri',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Evento',
                        'created_at'       => 'Data/Ora',
                        'user'             => 'Utente',
                        'status'           => 'Stato',
                        'success'          => 'Successo',
                        'failed'           => 'Fallito',
                        'server_error'     => 'Errore del server',
                        'timeout_or_error' => 'Timeout/Errore',
                        'delete'           => 'Elimina',
                        'view'             => 'Visualizza',
                    ],
                    'title'          => 'Registri Webhook',
                    'show-title'     => 'Dettagli log Webhook',
                    'sent-payload'   => 'Payload inviato',
                    'response'       => 'Risposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Nessun payload registrato per questo log.',
                    'load-failed'    => 'Impossibile caricare i dettagli del log.',
                    'delete-success' => 'Registri Webhook eliminati con successo',
                    'delete-failed'  => 'L\'eliminazione dei registri Webhook è fallita inaspettatamente',
                    'unauthorized'   => 'Questa azione non è autorizzata',
                ],
            ],
        ],
    ],
];
