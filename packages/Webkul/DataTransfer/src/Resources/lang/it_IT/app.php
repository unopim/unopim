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
                    'super-attribute-not-found'                => 'Attributo configurabile con codice: \'%s\' non trovato o non appartiene alla famiglia di attributi: \'%s\'',
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
        ],
    ],
    'job' => [
        'started'   => 'Esecuzione del lavoro iniziata',
        'completed' => 'Esecuzione del lavoro completata',
    ],
];
