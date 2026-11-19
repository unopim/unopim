<?php

return [
    'acl' => [
        'variant-structures' => 'Strukture varijanti',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopija-:value',
        'copy-of'                       => 'Kopija :value',
        'variant-already-exist-message' => 'Varijanta s istim opcijama atributa već postoji.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Proizvodi tipa :type se ne mogu kopirati',
    ],

    'type'     => [
        'configurable'  => 'Konfigurabilan',
        'simple'        => 'Jednostavan',
        'variant-group' => 'Grupa varijanti',
        'variant_group' => 'Grupa varijanti',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Otkrivena je kružna referenca predloška: :path.',
            'currency-not-allowed'    => 'Samo atributi cijene prihvaćaju valutu, ali ":attribute" nije atribut cijene.',
            'currency-not-enabled'    => 'Valuta ":currency" korištena u predlošku nije omogućena ni za jedan kanal.',
            'currency-not-in-channel' => 'Valuta ":currency" nije omogućena za kanal ":channel".',
            'filter-failed'           => 'Filtar ":filter" nije bilo moguće primijeniti na vrijednost atributa ":attribute".',
            'filter-not-supported'    => 'Filtar ":filter" ne može se primijeniti na atribut ":attribute".',
            'format-date-position'    => 'Filtar format_date mora biti prvi filtar u ":expression".',
            'inside-html-tag'         => 'Izrazi predloška ne mogu se postaviti unutar HTML oznaka ili atributa poput poveznica i slika.',
            'invalid-filter-argument' => 'Filtar ":filter" u ":expression" ima nedostajući ili nevažeći argument.',
            'invalid-reference'       => 'Predložak spremljen u ":attribute" nije valjan i nije ga bilo moguće prikazati.',
            'invalid-syntax'          => 'Izraz predloška ":expression" nije valjan. Upotrijebite kôd atributa iza kojeg slijede neobavezni filtri, na primjer: name | upper.',
            'max-depth'               => 'Reference predloška ugniježđene su dublje od :max razina kod ":attribute".',
            'missing-value'           => 'Atribut ":attribute" nema vrijednost za ovaj kanal i jezik.',
            'not-in-family'           => 'Atribut ":attribute" korišten u predlošku ne pripada obitelji proizvoda.',
            'output-too-large'        => 'Iscrtani predložak veći je od :max bajtova i skraćen je.',
            'self-reference'          => 'Predložak ne može referencirati vlastiti atribut ":attribute".',
            'single-brace'            => 'Pronađeno ":text" u jednostrukim vitičastim zagradama. Izrazi predloška zahtijevaju dvostruke vitičaste zagrade.',
            'source-too-long'         => 'Predložak može imati najviše :max bajtova.',
            'too-complex'             => 'Predložak je previše složen za provjeru. Skratite ga ili pojednostavnite.',
            'too-many-expressions'    => 'Predložak može sadržavati najviše :max izraza.',
            'too-many-filters'        => 'Izraz predloška ":expression" može koristiti najviše :max filtara.',
            'unclosed-expression'     => 'Izraz predloška otvoren je, ali nikada nije zatvoren.',
            'unknown-attribute'       => 'Atribut ":attribute" korišten u predlošku ne postoji.',
            'unknown-currency'        => 'Valuta ":currency" korištena u predlošku ne postoji.',
            'unknown-filter'          => 'Filtar ":filter" nije podržan. Dostupni filtri: :filters.',
            'unsupported-attribute'   => 'Atribut ":attribute" ne može se koristiti u predlošku jer njegova vrsta nije podržana.',
        ],
    ],
];
