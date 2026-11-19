<?php

return [
    'acl' => [
        'variant-structures' => 'Varianttirakenteet',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopio-:value',
        'copy-of'                       => 'Kopio :value',
        'variant-already-exist-message' => 'Variantti samoilla ominaisuuksilla on jo olemassa.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Tyyppisiä tuotteita :type ei voi kopioida',
    ],

    'type'     => [
        'configurable'  => 'Konfiguroitava',
        'simple'        => 'Yksinkertainen',
        'variant-group' => 'Varianttiryhmä',
        'variant_group' => 'Varianttiryhmä',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Mallipohjasta löytyi kehäviittaus: :path.',
            'currency-not-allowed'    => 'Vain hinta-attribuutit hyväksyvät valuutan, mutta ":attribute" ei ole hinta-attribuutti.',
            'currency-not-enabled'    => 'Mallipohjassa käytetty valuutta ":currency" ei ole käytössä millään kanavalla.',
            'currency-not-in-channel' => 'Valuutta ":currency" ei ole käytössä kanavalla ":channel".',
            'filter-failed'           => 'Suodatinta ":filter" ei voitu käyttää attribuutin ":attribute" arvoon.',
            'filter-not-supported'    => 'Suodatinta ":filter" ei voi käyttää attribuuttiin ":attribute".',
            'format-date-position'    => 'Suodattimen format_date on oltava ensimmäinen suodatin lausekkeessa ":expression".',
            'inside-html-tag'         => 'Mallipohjan lausekkeita ei voi sijoittaa HTML-tagien tai -attribuuttien, kuten linkkien ja kuvien, sisään.',
            'invalid-filter-argument' => 'Suodattimen ":filter" argumentti lausekkeessa ":expression" puuttuu tai on virheellinen.',
            'invalid-reference'       => 'Attribuuttiin ":attribute" tallennettu mallipohja on virheellinen, eikä sitä voitu muodostaa.',
            'invalid-syntax'          => 'Mallipohjan lauseke ":expression" on virheellinen. Käytä attribuutin koodia ja sen perässä valinnaisia suodattimia, esimerkiksi: name | upper.',
            'max-depth'               => 'Mallipohjien viittaukset ovat sisäkkäin yli :max tason syvyydellä kohdassa ":attribute".',
            'missing-value'           => 'Attribuutilla ":attribute" ei ole arvoa tälle kanavalle ja kielelle.',
            'not-in-family'           => 'Mallipohjassa käytetty attribuutti ":attribute" ei kuulu tuoteperheeseen.',
            'output-too-large'        => 'Hahmonnettu mallipohja on suurempi kuin :max tavua, ja se katkaistiin.',
            'self-reference'          => 'Mallipohja ei voi viitata omaan attribuuttiinsa ":attribute".',
            'single-brace'            => 'Löytyi ":text" yksinkertaisissa aaltosulkeissa. Mallipohjan lausekkeet vaativat kaksinkertaiset aaltosulkeet.',
            'source-too-long'         => 'Mallipohjan pituus voi olla enintään :max tavua.',
            'too-complex'             => 'Mallipohja on liian monimutkainen tarkistettavaksi. Lyhennä tai yksinkertaista sitä.',
            'too-many-expressions'    => 'Mallipohja voi sisältää enintään :max lauseketta.',
            'too-many-filters'        => 'Mallipohjan lauseke ":expression" voi käyttää enintään :max suodatinta.',
            'unclosed-expression'     => 'Mallipohjan lauseke avattiin, mutta sitä ei koskaan suljettu.',
            'unknown-attribute'       => 'Mallipohjassa käytettyä attribuuttia ":attribute" ei ole olemassa.',
            'unknown-currency'        => 'Mallipohjassa käytettyä valuuttaa ":currency" ei ole olemassa.',
            'unknown-filter'          => 'Suodatinta ":filter" ei tueta. Käytettävissä olevat suodattimet: :filters.',
            'unsupported-attribute'   => 'Attribuuttia ":attribute" ei voi käyttää mallipohjassa, koska sen tyyppiä ei tueta.',
        ],
    ],
];
