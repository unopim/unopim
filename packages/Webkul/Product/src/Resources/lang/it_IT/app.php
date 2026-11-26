<?php

return [
    'acl' => [
        'variant-structures' => 'Strutture delle varianti',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'copertura-di-:value',
        'copy-of'                       => 'Copia di :value',
        'variant-already-exist-message' => 'Variante con le stesse opzioni di attributo già esiste.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'I prodotti del tipo :type non possono essere copiati',
    ],

    'type'     => [
        'configurable'  => 'Configurabile',
        'simple'        => 'Semplice',
        'variant-group' => 'Gruppo di varianti',
        'variant_group' => 'Gruppo di varianti',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Rilevato un riferimento circolare nel modello: :path.',
            'currency-not-allowed'    => 'Solo gli attributi di prezzo accettano una valuta, ma ":attribute" non è un attributo di prezzo.',
            'currency-not-enabled'    => 'La valuta ":currency" usata nel modello non è abilitata per nessun canale.',
            'currency-not-in-channel' => 'La valuta ":currency" non è abilitata per il canale ":channel".',
            'filter-failed'           => 'Impossibile applicare il filtro ":filter" al valore di ":attribute".',
            'filter-not-supported'    => 'Il filtro ":filter" non può essere applicato all\'attributo ":attribute".',
            'format-date-position'    => 'Il filtro format_date deve essere il primo filtro in ":expression".',
            'inside-html-tag'         => 'Le espressioni del modello non possono essere inserite all\'interno di tag o attributi HTML, come link e immagini.',
            'invalid-filter-argument' => 'Il filtro ":filter" in ":expression" ha un argomento mancante o non valido.',
            'invalid-reference'       => 'Il modello memorizzato in ":attribute" non è valido e non è stato possibile elaborarlo.',
            'invalid-syntax'          => 'L\'espressione del modello ":expression" non è valida. Usa un codice attributo seguito da filtri facoltativi, ad esempio: name | upper.',
            'max-depth'               => 'I riferimenti del modello sono annidati per più di :max livelli in ":attribute".',
            'missing-value'           => 'L\'attributo ":attribute" non ha alcun valore per questo canale e questa lingua.',
            'not-in-family'           => 'L\'attributo ":attribute" usato nel modello non appartiene alla famiglia del prodotto.',
            'output-too-large'        => 'Il modello renderizzato supera i :max byte ed è stato troncato.',
            'self-reference'          => 'Il modello non può fare riferimento al proprio attributo ":attribute".',
            'single-brace'            => 'Trovato ":text" tra parentesi graffe singole. Le espressioni del modello richiedono doppie parentesi graffe.',
            'source-too-long'         => 'Un modello può essere lungo al massimo :max byte.',
            'too-complex'             => 'Il modello è troppo complesso per essere verificato. Accorcialo o semplificalo.',
            'too-many-expressions'    => 'Un modello può contenere al massimo :max espressioni.',
            'too-many-filters'        => 'L\'espressione del modello ":expression" può usare al massimo :max filtri.',
            'unclosed-expression'     => 'Un\'espressione del modello è stata aperta ma mai chiusa.',
            'unknown-attribute'       => 'L\'attributo ":attribute" usato nel modello non esiste.',
            'unknown-currency'        => 'La valuta ":currency" usata nel modello non esiste.',
            'unknown-filter'          => 'Il filtro ":filter" non è supportato. Filtri disponibili: :filters.',
            'unsupported-attribute'   => 'L\'attributo ":attribute" non può essere usato in un modello perché il suo tipo non è supportato.',
        ],
    ],
];
