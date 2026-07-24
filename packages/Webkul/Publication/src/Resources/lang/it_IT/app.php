<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Pubblicazione',
            'info'     => 'Livello di distribuzione pubblico per contenuti pubblicati, per lingua.',
            'settings' => [
                'title'                            => 'Impostazioni di pubblicazione',
                'enabled'                          => 'Abilitato',
                'enabled-hint'                     => 'Interruttore principale del livello di servizio pubblico. Quando è disattivato, ogni URL pubblico del passaporto restituisce 404 e il menu dei passaporti viene nascosto.',
                'base-url'                         => 'URL di base',
                'base-url-hint'                    => 'Indirizzo pubblico in cui vengono serviti i passaporti, usato per generare codici QR e link condivisibili. Lascia vuoto per usare il dominio proprio di questo sito.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'TTL della cache (secondi)',
                'cache-ttl-hint'                   => 'Per quanto tempo un passaporto pubblico renderizzato viene memorizzato nella cache prima di essere ricostruito. Valori più alti riducono il carico; valori più bassi riflettono prima le modifiche.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Limite di frequenza (richieste/minuto)',
                'rate-limit-hint'                  => 'Numero massimo di richieste di passaporti pubblici consentite al minuto da un singolo visitatore prima che venga limitato.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Consenti l\'indicizzazione dei motori di ricerca',
                'indexable-hint'                   => 'Consenti ai motori di ricerca di indicizzare le pagine pubbliche dei passaporti. Disattiva per mantenere i passaporti raggiungibili tramite link ma nascosti dai risultati di ricerca.',
                'gs1-passport-channel'             => 'Canale del passaporto GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'Il canale a cui viene indirizzato un codice a barre GS1 scansionato (/01/{gtin}) quando un prodotto è pubblicato su più canali. Lascia vuoto per usare il primo canale abilitato.',
                'gs1-passport-channel-placeholder' => 'Primo canale abilitato (automatico)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Bozza',
            'published' => 'Pubblicato',
            'withdrawn' => 'Ritirato',
            'redacted'  => 'Redatto',
        ],
        'product-delete-blocked' => 'Questo prodotto non può essere eliminato finché ha passaporti pubblicati. Ritirali prima.',
        'channel-delete-blocked' => 'Questo canale non può essere eliminato finché ha passaporti pubblicati. Ritirali prima.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaporto non trovato.',
            'notice'  => 'Questo passaporto del prodotto non è disponibile. Potrebbe non essere ancora pubblicato oppure il link potrebbe essere errato.',
        ],
        '429' => [
            'heading' => 'Troppe richieste. Riprova tra poco.',
            'notice'  => 'Hai effettuato troppe richieste. Attendi un momento e riprova.',
        ],
        'withdrawn' => [
            'heading' => 'Questo passaporto non è più disponibile.',
            'notice'  => 'Questo record viene conservato per motivi di trasparenza, ma non è più attivamente mantenuto.',
        ],
    ],
];
