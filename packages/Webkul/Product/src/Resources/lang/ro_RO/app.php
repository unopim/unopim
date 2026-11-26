<?php

return [
    'acl' => [
        'variant-structures' => 'Structuri de variante',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'copie-de-:value',
        'copy-of'                       => 'Copie de :value',
        'variant-already-exist-message' => 'Variante cu aceleași opțiuni de atribut există deja.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produsele de tip :type nu pot fi copiate',
    ],

    'type'     => [
        'configurable'  => 'Configurabil',
        'simple'        => 'Simplu',
        'variant-group' => 'Grup de variante',
        'variant_group' => 'Grup de variante',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'A fost detectată o referință circulară în șablon: :path.',
            'currency-not-allowed'    => 'Doar atributele de preț acceptă o monedă, dar ":attribute" nu este un atribut de preț.',
            'currency-not-enabled'    => 'Moneda ":currency" folosită în șablon nu este activată pentru niciun canal.',
            'currency-not-in-channel' => 'Moneda ":currency" nu este activată pentru canalul ":channel".',
            'filter-failed'           => 'Filtrul ":filter" nu a putut fi aplicat valorii atributului ":attribute".',
            'filter-not-supported'    => 'Filtrul ":filter" nu poate fi aplicat atributului ":attribute".',
            'format-date-position'    => 'Filtrul format_date trebuie să fie primul filtru din ":expression".',
            'inside-html-tag'         => 'Expresiile de șablon nu pot fi plasate în interiorul etichetelor sau atributelor HTML, cum ar fi linkurile și imaginile.',
            'invalid-filter-argument' => 'Filtrul ":filter" din ":expression" are un argument lipsă sau nevalid.',
            'invalid-reference'       => 'Șablonul stocat în ":attribute" nu este valid și nu a putut fi randat.',
            'invalid-syntax'          => 'Expresia de șablon ":expression" nu este validă. Folosiți un cod de atribut urmat de filtre opționale, de exemplu: name | upper.',
            'max-depth'               => 'Referințele de șablon sunt imbricate pe mai mult de :max niveluri la ":attribute".',
            'missing-value'           => 'Atributul ":attribute" nu are nicio valoare pentru acest canal și această localitate.',
            'not-in-family'           => 'Atributul ":attribute" folosit în șablon nu aparține familiei produsului.',
            'output-too-large'        => 'Șablonul randat depășește :max octeți și a fost trunchiat.',
            'self-reference'          => 'Șablonul nu poate face referire la propriul atribut ":attribute".',
            'single-brace'            => 'S-a găsit ":text" între acolade simple. Expresiile de șablon necesită acolade duble.',
            'source-too-long'         => 'Un șablon poate avea cel mult :max octeți.',
            'too-complex'             => 'Șablonul este prea complex pentru a fi verificat. Scurtează-l sau simplifică-l.',
            'too-many-expressions'    => 'Un șablon poate conține cel mult :max expresii.',
            'too-many-filters'        => 'Expresia de șablon ":expression" poate folosi cel mult :max filtre.',
            'unclosed-expression'     => 'O expresie de șablon a fost deschisă, dar nu a fost închisă niciodată.',
            'unknown-attribute'       => 'Atributul ":attribute" folosit în șablon nu există.',
            'unknown-currency'        => 'Moneda ":currency" folosită în șablon nu există.',
            'unknown-filter'          => 'Filtrul ":filter" nu este acceptat. Filtre disponibile: :filters.',
            'unsupported-attribute'   => 'Atributul ":attribute" nu poate fi folosit într-un șablon deoarece tipul său nu este acceptat.',
        ],
    ],
];
