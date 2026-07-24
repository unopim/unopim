<?php

return [
    'type' => [
        'label' => 'Passaporto Digitale del Prodotto',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaporto del Prodotto',
            'info'     => 'Impostazioni di pubblicazione del passaporto digitale del prodotto.',
            'settings' => [
                'title'                              => 'Impostazioni del passaporto del prodotto',
                'enabled'                            => 'Abilitato',
                'enabled-hint'                       => 'Attiva la funzionalità Passaporto Digitale di Prodotto per questo catalogo. Quando è disattivata, il pannello e la griglia dei passaporti vengono nascosti.',
                'auto-publish'                       => 'Pubblica automaticamente al salvataggio',
                'auto-publish-hint'                  => 'Pubblica automaticamente una versione del passaporto ogni volta che un prodotto viene salvato e raggiunge la soglia di completezza. Lascia disattivato per pubblicare manualmente.',
                'completeness-threshold'             => 'Soglia di completezza (%)',
                'completeness-threshold-hint'        => 'Completezza minima del prodotto, in percentuale, richiesta prima che un passaporto possa essere pubblicato per una lingua.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Nome dell\'operatore economico',
                'operator-name-hint'                 => 'Denominazione legale del produttore o dell\'operatore economico responsabile, mostrata su ogni passaporto pubblico come richiesto dal regolamento ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Indirizzo dell\'operatore economico',
                'operator-address-hint'              => 'Indirizzo postale registrato dell\'operatore economico, mostrato sul passaporto pubblico per la tracciabilità.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Rappresentante autorizzato nell\'UE',
                'operator-eu-rep-hint'               => 'Nome e contatto del rappresentante autorizzato nell\'UE, richiesto quando il produttore ha sede al di fuori dell\'UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL di supporto',
                'support-url-hint'                   => 'Pagina pubblica dove i clienti possono trovare assistenza o informazioni sulla garanzia. Mostrata come link su ogni passaporto.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Passaporto Digitale del Prodotto',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Composizione dei materiali',
        'dpp_substances_of_concern'     => 'Sostanze preoccupanti',
        'dpp_recycled_content_pct'      => 'Contenuto riciclato (%)',
        'dpp_carbon_footprint'          => 'Impronta di carbonio',
        'dpp_energy_consumption'        => 'Consumo energetico',
        'dpp_durability_statement'      => 'Dichiarazione di durabilità',
        'dpp_repairability_score'       => 'Punteggio di riparabilità',
        'dpp_spare_parts_availability'  => 'Disponibilità dei ricambi',
        'dpp_care_instructions'         => 'Istruzioni per la cura',
        'dpp_disassembly_guide'         => 'Guida allo smontaggio',
        'dpp_manufacturer_name'         => 'Nome del produttore',
        'dpp_manufacturing_site'        => 'Sito di produzione',
        'dpp_country_of_origin'         => 'Paese di origine',
        'dpp_supply_chain_notes'        => 'Note sulla catena di fornitura',
        'dpp_end_of_life_instructions'  => 'Istruzioni di fine vita',
        'dpp_take_back_scheme'          => 'Programma di ritiro',
        'dpp_declaration_of_conformity' => 'Dichiarazione di conformità',
        'dpp_test_reports'              => 'Rapporti di prova',
        'dpp_certificates'              => 'Certificati',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identificativo del modello',
        'dpp_batch_identifier'          => 'Identificativo del lotto',
        'dpp_warranty_terms'            => 'Condizioni di garanzia',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Gli attributi del Passaporto Digitale del Prodotto sono stati installati correttamente.',
        ],
    ],

    'public' => [
        'badge'         => 'Passaporto Digitale di Prodotto EU',
        'search-locale' => 'Lingua di ricerca',
        'sections'      => [
            'passport' => 'Passaporto del Prodotto',
        ],
        'title'      => 'Passaporto Digitale del Prodotto',
        'identifier' => [
            'title'        => 'Identificazione',
            'gtin'         => 'GTIN',
            'model'        => 'Modello',
            'batch'        => 'Lotto',
            'not-provided' => 'Non fornito',
        ],
        'operator' => [
            'title' => 'Operatore economico',
        ],
        'documents' => [
            'title' => 'Documenti',
        ],
    ],

    'publications' => [
        'not-found'      => 'Nessun passaporto trovato per l\'id :id.',
        'index'          => [
            'disabled-notice' => 'La pubblicazione dei passaporti è attualmente disattivata. I passaporti esistenti sono mostrati di seguito per la gestione (visualizzazione e ritiro).',
            'title'           => 'Passaporti Digitali del Prodotto',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Canale',
            'status'          => 'Stato',
            'live-locales'    => 'Lingue attive',
            'last-published'  => 'Ultima pubblicazione',
            'withdraw'        => 'Ritira',
        ],
        'publish-queued' => 'La pubblicazione del passaporto è stata messa in coda.',
        'withdrawn'      => 'Passaporto ritirato con successo.',
        'mass-publish'   => [
            'action' => 'Pubblica il passaporto digitale di prodotto',
            'queued' => 'Pubblicazione del passaporto in coda per :count prodotto/i.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Passaporti',
            'view'     => 'Visualizza',
            'publish'  => 'Pubblica',
            'withdraw' => 'Ritira',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passaporti',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Pubblicazione…',
                    'queued'               => 'In coda',
                    'copy-operator-link'   => 'Copia link operatore',
                    'copy-authority-link'  => 'Copia link autorità',
                    'link-copied'          => 'Link copiato',
                    'title'                => 'Passaporto Digitale del Prodotto',
                    'publishing-disabled'  => 'La pubblicazione dei passaporti è disabilitata per questo canale.',
                    'locale'               => 'Lingua',
                    'version'              => 'Versione',
                    'published-at'         => 'Pubblicato il',
                    'missing-fields'       => 'Campi mancanti',
                    'not-published'        => 'Non pubblicato',
                    'unscored'             => 'Non valutato',
                    'publish'              => 'Pubblica',
                    'republish'            => 'Ripubblica',
                    'publish-all'          => 'Pubblica tutte le lingue',
                    'auto-publish-on'      => 'La pubblicazione automatica è attiva — i passaporti vengono pubblicati automaticamente quando il prodotto viene salvato e raggiunge la soglia di completezza. Usa i pulsanti per pubblicare ora.',
                    'auto-publish-off'     => 'Pubblicazione manuale — usa i pulsanti per pubblicare il passaporto di questo prodotto per ogni lingua.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Il campo :attribute deve essere un GTIN valido (8, 12, 13 o 14 cifre con una cifra di controllo corretta).',
    ],
    'mapping' => [
        'title'         => 'Mappatura dei campi del passaporto',
        'info'          => 'Alimenta ogni campo del passaporto da un attributo che già gestisci. Lascia un campo non mappato per ricorrere al suo attributo di passaporto dedicato.',
        'menu'          => 'Mappatura dei campi',
        'field'         => 'Campo del passaporto',
        'source'        => 'Attributo di origine',
        'select-source' => 'Usa l’attributo del passaporto',
        'save-btn'      => 'Salva mappatura',
        'type-mismatch' => 'L\'origine selezionata non è compatibile con il tipo di questo campo del passaporto.',
        'saved'         => 'Mappatura dei campi salvata con successo.',
    ],

];
