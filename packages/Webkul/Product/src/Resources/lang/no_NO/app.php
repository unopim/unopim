<?php

return [
    'acl' => [
        'variant-structures' => 'Variantstrukturer',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopi-av-:value',
        'copy-of'                       => 'Kopi av :value',
        'variant-already-exist-message' => 'Variant med samme attributmuligheter eksisterer allerede.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produkter av typen :type kan ikke kopieres',
    ],

    'type'     => [
        'configurable'  => 'Konfigurerbar',
        'simple'        => 'Enkel',
        'variant-group' => 'Variantgruppe',
        'variant_group' => 'Variantgruppe',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Sirkulær malreferanse oppdaget: :path.',
            'currency-not-allowed'    => 'Bare prisattributter godtar en valuta, men ":attribute" er ikke et prisattributt.',
            'currency-not-enabled'    => 'Valutaen ":currency" som brukes i malen, er ikke aktivert for noen kanal.',
            'currency-not-in-channel' => 'Valutaen ":currency" er ikke aktivert for kanalen ":channel".',
            'filter-failed'           => 'Filteret ":filter" kunne ikke brukes på verdien til ":attribute".',
            'filter-not-supported'    => 'Filteret ":filter" kan ikke brukes på attributtet ":attribute".',
            'format-date-position'    => 'Filteret format_date må være det første filteret i ":expression".',
            'inside-html-tag'         => 'Maluttrykk kan ikke plasseres inne i HTML-tagger eller -attributter som lenker og bilder.',
            'invalid-filter-argument' => 'Filteret ":filter" i ":expression" har et manglende eller ugyldig argument.',
            'invalid-reference'       => 'Malen som er lagret i ":attribute", er ugyldig og kunne ikke gjengis.',
            'invalid-syntax'          => 'Maluttrykket ":expression" er ugyldig. Bruk en attributtkode etterfulgt av valgfrie filtre, for eksempel: name | upper.',
            'max-depth'               => 'Malreferanser er nestet mer enn :max nivåer dypt ved ":attribute".',
            'missing-value'           => 'Attributtet ":attribute" har ingen verdi for denne kanalen og dette språket.',
            'not-in-family'           => 'Attributtet ":attribute" som brukes i malen, tilhører ikke produktfamilien.',
            'output-too-large'        => 'Den gjengitte malen er større enn :max byte og ble avkortet.',
            'self-reference'          => 'Malen kan ikke referere til sitt eget attributt ":attribute".',
            'single-brace'            => 'Fant ":text" i enkle krøllparenteser. Maluttrykk krever doble krøllparenteser.',
            'source-too-long'         => 'En mal kan være maksimalt :max byte lang.',
            'too-complex'             => 'Malen er for kompleks til å kontrolleres. Gjør den kortere eller enklere.',
            'too-many-expressions'    => 'En mal kan inneholde maksimalt :max uttrykk.',
            'too-many-filters'        => 'Maluttrykket ":expression" kan bruke maksimalt :max filtre.',
            'unclosed-expression'     => 'Et maluttrykk ble åpnet, men aldri lukket.',
            'unknown-attribute'       => 'Attributtet ":attribute" som brukes i malen, finnes ikke.',
            'unknown-currency'        => 'Valutaen ":currency" som brukes i malen, finnes ikke.',
            'unknown-filter'          => 'Filteret ":filter" støttes ikke. Tilgjengelige filtre: :filters.',
            'unsupported-attribute'   => 'Attributtet ":attribute" kan ikke brukes i en mal fordi typen ikke støttes.',
        ],
    ],
];
