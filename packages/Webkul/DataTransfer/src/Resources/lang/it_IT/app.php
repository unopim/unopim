<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Prodotti',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Chiave URL: \'%s\' è già stata generata per un articolo con l\'SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valore non valido per la colonna della famiglia di attributi (la famiglia di attributi non esiste?)',
                    'invalid-type'                             => 'Tipo di prodotto non valido o non supportato',
                    'sku-not-found'                            => 'Prodotto con SKU specificato non trovato',
                    'super-attribute-not-found'                => 'Attributo configurabile con codice: \'%s\' non trovato o non appartiene alla famiglia di attributi: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Gli attributi configurabili sono necessari per creare il modello di prodotto',
                    'configurable-attributes-wrong-type'       => 'Solo gli attributi di tipo che non sono basati su locale o canale possono essere attributi configurabili per un prodotto configurabile',
                    'variant-configurable-attribute-not-found' => 'Attributo configurabile variante: :code è richiesto per la creazione',
                    'not-unique-variant-product'               => 'Un prodotto con gli stessi attributi configurabili esiste già.',
                    'channel-not-exist'                        => 'Questo canale non esiste.',
                    'locale-not-in-channel'                    => 'Questo locale non è selezionato nel canale.',
                    'locale-not-exist'                         => 'Questo locale non esiste',
                    'not-unique-value'                         => 'Il valore :code deve essere unico.',
                    'incorrect-family-for-variant'             => 'La famiglia deve essere la stessa della famiglia principale',
                    'parent-not-exist'                         => 'Il genitore non esiste.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorie',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Non puoi eliminare la categoria radice associata a un canale',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Campi categoria',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Il codice del campo categoria :code è già in uso.',
                    'code_not_found_to_delete' => 'Il codice del campo categoria non è stato trovato per l\'eliminazione.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attributi',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Il codice attributo :code è già in uso.',
                    'code_not_found_to_delete'             => 'Codice attributo non trovato per l\'eliminazione.',
                    'code_is_system_and_cannot_be_deleted' => 'L\'attributo di sistema non può essere eliminato.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Associazioni prodotto',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Il campo \'%s\' è obbligatorio.',
                    'self-link-not-allowed'       => 'Il prodotto \'%s\' non può essere associato a se stesso.',
                    'sku-not-found'               => 'Prodotto con SKU \'%s\' non trovato.',
                    'related-sku-not-found'       => 'Prodotto correlato con SKU \'%s\' non trovato.',
                    'association-type-not-found'  => 'Il tipo di associazione \'%s\' non esiste o non è attivo.',
                    'invalid-field-value'         => 'Valore non valido fornito per un campo di associazione.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Gruppi di attributi',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Il codice del gruppo di attributi :code è già in uso.',
                    'code_not_found_to_delete'             => 'Codice del gruppo di attributi non trovato per l\'eliminazione.',
                    'code_is_system_and_cannot_be_deleted' => 'Il gruppo di attributi di sistema non può essere eliminato.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Famiglie di attributi',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Il codice della famiglia di attributi :code è già in uso.',
                    'code_not_found_to_delete' => 'Codice della famiglia di attributi non trovato per l\'eliminazione.',
                    'invalid-attribute-group'  => 'Il gruppo di attributi ":code" non esiste.',
                    'invalid-attribute'        => 'L\'attributo ":code" non esiste.',
                    'invalid-channel'          => 'Il canale ":code" non esiste.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opzioni attributo',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Il codice dell\'opzione attributo :code è già in uso.',
                    'code_not_found_to_delete' => 'Codice dell\'opzione attributo non trovato per l\'eliminazione.',
                    'locale-not-exist'         => 'La lingua ":code" non esiste.',
                    'invalid-attribute'        => 'L\'attributo ":code" non esiste.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Lingue',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Il codice lingua \'%s\' è già stato importato in questo batch.',
                    'code-not-found-to-delete'    => 'Lingua con codice \'%s\' non trovata nel sistema.',
                    'invalid-status'              => 'Lo stato deve essere 0 o 1 (o vuoto per abilitato di default).',
                    'channel-related-locale-root' => 'Non puoi eliminare la lingua con codice :code perché è associata a un canale.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canali',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Canale con codice :code non trovato per l\'eliminazione.',
                    'locale-not-found'         => 'Una o più lingue non esistono.',
                    'root-category-not-found'  => 'La categoria principale non esiste.',
                    'currency-not-found'       => 'Una o più valute non esistono.',
                    'invalid-locale'           => 'La lingua non esiste.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Valute',
            'filters' => [
                'status' => 'Stato',
                'enable' => 'Abilitato',
                'all'    => 'Tutti',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Lo stato deve essere 0 o 1 (o vuoto per abilitato di default).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Ruoli',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Utenti',
            'filters' => [
                'status' => 'Stato',
                'active' => 'Attivo',
                'all'    => 'Tutti',
            ],
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'Questa esportazione è troppo grande per essere eseguita: circa :rows righe × :columns colonne (~:estimated) superano lo spazio disponibile (~:available). Restringi l\'esportazione selezionando meno canali/lingue (e attributi) e riprova.',
        'fields'           => [
            'file-format'         => 'Formato file',
            'with-media'          => 'Con media',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Percorso File',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Stato',
            'enable'         => 'Abilitato',
            'all'            => 'Tutti',
        ],
        'products' => [
            'title'              => 'Prodotti',
            'invalid-locales'    => 'Non tutte le lingue selezionate sono disponibili per i canali selezionati.',
            'invalid-currencies' => 'Non tutte le valute selezionate sono disponibili per i canali selezionati.',
            'filters'            => [
                'channels'             => 'Canali',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valute',
                'currencies-info'      => 'Gli attributi di prezzo vengono esportati per ogni valuta selezionata. Lascia vuoto per esportare tutte le valute del canale.',
                'locales'              => 'Lingue',
                'locales-info'         => 'Gli attributi localizzabili vengono esportati una volta per ogni lingua selezionata. Lascia vuoto per esportare tutte le lingue del canale.',
                'attributes'           => 'Attributi',
                'attributes-info'      => 'Vengono esportati solo gli attributi selezionati. Lascia vuoto per esportare tutti gli attributi della famiglia.',
                'attribute-families'   => 'Famiglie di attributi',
                'categories'           => 'Categorie',
                'completeness'         => 'Completezza',
                'completeness-options' => [
                    'none'         => 'Nessuna condizione di completezza',
                    'at-least-one' => 'Completo in almeno una lingua selezionata',
                    'all'          => 'Completo in tutte le lingue selezionate',
                ],
                'time-condition' => 'Condizione temporale',
                'time-options'   => [
                    'none'              => 'Nessuna condizione di data',
                    'last-n-days'       => 'Prodotti aggiornati negli ultimi N giorni',
                    'between-dates'     => 'Prodotti aggiornati tra due date',
                    'since-last-export' => 'Prodotti aggiornati dall\'ultima esportazione',
                ],
                'time-value'     => 'Numero di giorni',
                'time-date'      => 'Data di inizio',
                'time-date-end'  => 'Data di fine',
                'status'         => 'Stato',
                'status-options' => [
                    'enable'  => 'Abilitato',
                    'disable' => 'Disabilitato',
                    'all'     => 'Tutti',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificatori',
                'identifiers-info' => 'Incolla un SKU / identificatore per riga per esportare solo quei prodotti. Lascia vuoto per esportare tutti i prodotti.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Chiave URL: \'%s\' è già stata generata per un articolo con l\'SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valore non valido per la colonna della famiglia di attributi (la famiglia di attributi non esiste?)',
                    'invalid-type'              => 'Tipo di prodotto non valido o non supportato',
                    'sku-not-found'             => 'Prodotto con SKU specificato non trovato',
                    'super-attribute-not-found' => 'Attributo principale con codice: \'%s\' non trovato o non appartiene alla famiglia di attributi: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorie',
        ],
        'category-fields' => [
            'title' => 'Campi categoria',
        ],
        'attributes' => [
            'title' => 'Attributi',
        ],
        'attribute-groups' => [
            'title' => 'Gruppi di attributi',
        ],
        'attribute-families' => [
            'title' => 'Famiglie di attributi',
        ],
        'attribute-options' => [
            'title' => 'Opzioni attributo',
        ],
        'locales' => [
            'title' => 'Lingue',
        ],
        'channels' => [
            'title' => 'Canali',
        ],
        'currencies' => [
            'title' => 'Valute',
        ],
        'roles' => [
            'title' => 'Ruoli',
        ],
        'users' => [
            'title'   => 'Utenti',
            'filters' => [
                'status' => 'Stato',
                'active' => 'Attivo',
                'all'    => 'Tutti',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Le colonne numero "%s" hanno intestazioni vuote.',
            'column-name-invalid'  => 'Intestazioni di colonna non valide: "%s".',
            'column-not-found'     => 'Le colonne richieste non sono trovate: %s.',
            'column-numbers'       => 'Il numero di colonne non corrisponde al numero di righe nell\'intestazione.',
            'invalid-attribute'    => 'L\'intestazione contiene attributi non validi: "%s".',
            'system'               => 'Si è verificato un errore di sistema imprevisto.',
            'wrong-quotes'         => 'Virgolette a parentesi usate al posto delle virgolette diritte.',
            'file-empty'           => 'Il file è vuoto o non contiene una riga di intestazione. Si prega di caricare un file valido con dati.',
        ],
    ],
    'job' => [
        'started'   => 'Esecuzione del lavoro iniziata',
        'completed' => 'Esecuzione del lavoro completata',
    ],
];
