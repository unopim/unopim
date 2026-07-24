<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Udgivelse',
            'info'     => 'Offentligt serveringslag for publiceret, sprogspecifikt indhold.',
            'settings' => [
                'title'                  => 'Udgivelsesindstillinger',
                'enabled'                => 'Aktiveret',
                'enabled-hint'           => 'Hovedafbryder for det offentlige visningslag. Når det er slået fra, returnerer hver offentlig pas-URL 404, og pas-menuen skjules.',
                'base-url'               => 'Basis-URL',
                'base-url-hint'          => 'Offentlig adresse, hvor pas serveres; bruges til at opbygge QR-koder og delbare links. Lad stå tomt for at bruge dette websteds eget domæne.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl'              => 'Cache-TTL (sekunder)',
                'cache-ttl-hint'         => 'Hvor længe et gengivet offentligt pas gemmes i cachen, før det genopbygges. Højere værdier reducerer belastningen; lavere værdier afspejler ændringer hurtigere.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit'             => 'Hastighedsgrænse (forespørgsler/minut)',
                'rate-limit-hint'        => 'Maksimalt antal offentlige pas-anmodninger tilladt pr. minut fra en enkelt besøgende, før vedkommende begrænses.',
                'rate-limit-placeholder' => '60',
                'indexable'              => 'Tillad indeksering fra søgemaskiner',
                'indexable-hint'         => 'Tillad søgemaskiner at indeksere offentlige pas-sider. Slå fra for at holde pas tilgængelige via link, men skjult fra søgeresultater.',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Kladde',
            'published' => 'Udgivet',
            'withdrawn' => 'Trukket tilbage',
            'redacted'  => 'Skjult',
        ],
        'product-delete-blocked' => 'Dette produkt kan ikke slettes, så længe det har offentliggjorte pas. Træk dem tilbage først.',
        'channel-delete-blocked' => 'Denne kanal kan ikke slettes, så længe den har offentliggjorte pas. Træk dem tilbage først.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pas ikke fundet.',
            'notice'  => 'Dette produktpas er ikke tilgængeligt. Det er måske ikke offentliggjort endnu, eller linket kan være forkert.',
        ],
        '429' => [
            'heading' => 'For mange anmodninger. Prøv venligst igen om lidt.',
            'notice'  => 'Du har foretaget for mange anmodninger. Vent et øjeblik, og prøv igen.',
        ],
        'withdrawn' => [
            'heading' => 'Dette pas er ikke længere tilgængeligt.',
            'notice'  => 'Denne post opbevares af hensyn til gennemsigtighed, men vedligeholdes ikke længere aktivt.',
        ],
    ],
];
