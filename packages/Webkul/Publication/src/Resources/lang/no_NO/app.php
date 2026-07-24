<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publisering',
            'info'     => 'Offentlig serveringslag for publisert, språkspesifikt innhold.',
            'settings' => [
                'title'                  => 'Publiseringsinnstillinger',
                'enabled'                => 'Aktivert',
                'base-url'               => 'Grunn-URL',
                'cache-ttl'              => 'Cache-TTL (sekunder)',
                'rate-limit'             => 'Hastighetsgrense (forespørsler/minutt)',
                'indexable'              => 'Tillat indeksering fra søkemotorer',
                'enabled-hint'           => 'Hovedbryter for det offentlige visningsnivået. Når den er av, returnerer alle offentlige pass-URL-er 404, og passmenyen skjules.',
                'base-url-hint'          => 'Offentlig adresse der pass leveres, brukt til å bygge QR-koder og delbare lenker. La stå tom for å bruke dette nettstedets eget domene.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl-hint'         => 'Hvor lenge et gjengitt offentlig pass mellomlagres før det bygges på nytt. Høyere verdier reduserer belastningen; lavere verdier viser endringer raskere.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit-hint'        => 'Maksimalt antall offentlige passforespørsler tillatt per minutt fra én besøkende før vedkommende begrenses.',
                'rate-limit-placeholder' => '60',
                'indexable-hint'         => 'La søkemotorer indeksere offentlige passsider. Slå av for å holde pass tilgjengelige via lenke, men skjult fra søkeresultater.',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Utkast',
            'published' => 'Publisert',
            'withdrawn' => 'Trukket tilbake',
            'redacted'  => 'Sladdet',
        ],
        'product-delete-blocked' => 'Dette produktet kan ikke slettes så lenge det har publiserte pass. Trekk dem tilbake først.',
        'channel-delete-blocked' => 'Denne kanalen kan ikke slettes så lenge den har publiserte pass. Trekk dem tilbake først.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pass ikke funnet.',
            'notice'  => 'Dette produktpasset er ikke tilgjengelig. Det er kanskje ikke publisert ennå, eller lenken kan være feil.',
        ],
        '429' => [
            'heading' => 'For mange forespørsler. Prøv igjen om litt.',
            'notice'  => 'Du har sendt for mange forespørsler. Vent et øyeblikk og prøv igjen.',
        ],
        'withdrawn' => [
            'heading' => 'Dette passet er ikke lenger tilgjengelig.',
            'notice'  => 'Denne posten oppbevares av hensyn til åpenhet, men vedlikeholdes ikke lenger aktivt.',
        ],
    ],
];
