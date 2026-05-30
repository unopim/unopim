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
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
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
        'products' => [
            'title'      => 'Prodotti',
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
        'channels' => [
            'title' => 'Canali',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Esecuzione del lavoro iniziata',
        'completed' => 'Esecuzione del lavoro completata',
    ],
];
