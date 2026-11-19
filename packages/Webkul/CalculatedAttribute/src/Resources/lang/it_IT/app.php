<?php

return [

    'attribute' => [
        'calculated' => 'Calcolato',
    ],

    'formula' => [
        'title'            => 'Formula',
        'label'            => 'Formula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => "Il valore viene ricavato da questa formula ogni volta che il prodotto viene mostrato, esportato o restituito dall'API. Non viene mai memorizzato e non può essere importato.",
        'reference-hint'   => 'Fai riferimento agli altri attributi tramite il loro codice.',
        'available'        => 'Funzioni disponibili',
        'examples'         => 'Esempi',
        'example-concat'   => 'Unire due attributi, saltando quelli vuoti',
        'example-category' => 'Il percorso delle etichette di categoria, unito con #',
        'example-fallback' => 'Ripiegare su un secondo attributo quando il primo è vuoto',
        'read-only'        => 'Calcolato',
        'read-only-hint'   => 'Questo valore è calcolato a partire da altri attributi e non può essere modificato.',

        'variables' => [
            'title'      => 'Variabili disponibili',
            'sku'        => 'lo SKU del prodotto',
            'type'       => 'il tipo di prodotto',
            'status'     => 'lo stato del prodotto',
            'family'     => 'il codice della famiglia di attributi',
            'parent'     => 'lo SKU del prodotto padre, per le varianti',
            'categories' => 'i codici delle categorie assegnate',
            'channel'    => 'il canale in fase di rendering',
            'locale'     => 'la lingua in fase di rendering',
        ],

        'errors' => [
            'syntax'              => 'Non è stato possibile analizzare la formula: :detail',
            'unknown-variable'    => 'Attributo o variabile ":name" sconosciuto nella formula.',
            'disallowed-function' => 'La funzione ":name" non è consentita in una formula.',
            'disallowed-operator' => 'L\'operatore ":name" non è consentito in una formula.',
            'reserved-variable'   => 'Il nome ":name" è riservato e non può essere usato in una formula.',
            'too-long'            => 'La formula non può superare i :max caratteri.',
            'empty'               => 'Un attributo calcolato richiede una formula.',
            'circular'            => 'La formula crea una dipendenza circolare: :cycle',
            'referenced-by'       => 'Questo attributo non può essere eliminato perché è usato dalla formula di: :attributes',
        ],
    ],

];
