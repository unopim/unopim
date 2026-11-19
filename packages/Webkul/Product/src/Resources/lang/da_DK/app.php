<?php

return [
    'acl' => [
        'variant-structures' => 'Variantstrukturer',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopi-af-:value',
        'copy-of'                       => 'Kopi af :value',
        'variant-already-exist-message' => 'Variant med samme attributmuligheder eksisterer allerede.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produkter af typen :type kan ikke kopieres',
    ],

    'type'     => [
        'configurable'  => 'Konfigurerbar',
        'simple'        => 'Enkel',
        'variant-group' => 'Variantgruppe',
        'variant_group' => 'Variantgruppe',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Cirkulær skabelonreference fundet: :path.',
            'currency-not-allowed'    => 'Kun prisattributter accepterer en valuta, men ":attribute" er ikke en prisattribut.',
            'currency-not-enabled'    => 'Valutaen ":currency", der bruges i skabelonen, er ikke aktiveret for nogen kanal.',
            'currency-not-in-channel' => 'Valutaen ":currency" er ikke aktiveret for kanalen ":channel".',
            'filter-failed'           => 'Filteret ":filter" kunne ikke anvendes på værdien af ":attribute".',
            'filter-not-supported'    => 'Filteret ":filter" kan ikke anvendes på attributten ":attribute".',
            'format-date-position'    => 'Filteret format_date skal være det første filter i ":expression".',
            'inside-html-tag'         => 'Skabelonudtryk kan ikke placeres i HTML-tags eller -attributter som links og billeder.',
            'invalid-filter-argument' => 'Filteret ":filter" i ":expression" har et manglende eller ugyldigt argument.',
            'invalid-reference'       => 'Skabelonen gemt i ":attribute" er ugyldig og kunne ikke gengives.',
            'invalid-syntax'          => 'Skabelonudtrykket ":expression" er ugyldigt. Brug en attributkode efterfulgt af valgfrie filtre, for eksempel: name | upper.',
            'max-depth'               => 'Skabelonreferencer er indlejret mere end :max niveauer dybt ved ":attribute".',
            'missing-value'           => 'Attributten ":attribute" har ingen værdi for denne kanal og lokalitet.',
            'not-in-family'           => 'Attributten ":attribute", der bruges i skabelonen, tilhører ikke produktfamilien.',
            'output-too-large'        => 'Den gengivne skabelon er større end :max bytes og blev afkortet.',
            'self-reference'          => 'Skabelonen kan ikke referere til sin egen attribut ":attribute".',
            'single-brace'            => 'Fandt ":text" i enkelte krøllede parenteser. Skabelonudtryk kræver dobbelte krøllede parenteser.',
            'source-too-long'         => 'En skabelon kan højst være :max bytes lang.',
            'too-complex'             => 'Skabelonen er for kompleks til at blive kontrolleret. Forkort eller forenkl den.',
            'too-many-expressions'    => 'En skabelon kan højst indeholde :max udtryk.',
            'too-many-filters'        => 'Skabelonudtrykket ":expression" kan højst bruge :max filtre.',
            'unclosed-expression'     => 'Et skabelonudtryk blev åbnet, men aldrig lukket.',
            'unknown-attribute'       => 'Attributten ":attribute", der bruges i skabelonen, findes ikke.',
            'unknown-currency'        => 'Valutaen ":currency", der bruges i skabelonen, findes ikke.',
            'unknown-filter'          => 'Filteret ":filter" understøttes ikke. Tilgængelige filtre: :filters.',
            'unsupported-attribute'   => 'Attributten ":attribute" kan ikke bruges i en skabelon, fordi dens type ikke understøttes.',
        ],
    ],
];
