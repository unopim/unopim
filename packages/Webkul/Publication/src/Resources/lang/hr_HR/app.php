<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Objava',
            'info'     => 'Javna razina posluživanja za objavljeni sadržaj, po jeziku.',
            'settings' => [
                'title'                            => 'Postavke objave',
                'enabled'                          => 'Omogućeno',
                'enabled-hint'                     => 'Glavni prekidač za javni sloj posluživanja. Kada je isključen, svaki javni URL putovnice vraća 404, a izbornik putovnica je skriven.',
                'base-url'                         => 'Osnovni URL',
                'base-url-hint'                    => 'Javna adresa na kojoj se poslužuju putovnice, koristi se za izradu QR kodova i poveznica za dijeljenje. Ostavite prazno za korištenje vlastite domene ove stranice.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'TTL predmemorije (sekunde)',
                'cache-ttl-hint'                   => 'Koliko dugo se renderirana javna putovnica sprema u predmemoriju prije ponovne izrade. Veće vrijednosti smanjuju opterećenje; niže vrijednosti brže prikazuju izmjene.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Ograničenje brzine (zahtjeva/minuti)',
                'rate-limit-hint'                  => 'Najveći broj zahtjeva za javnim putovnicama dopušten u minuti od jednog posjetitelja prije nego što se ograniči.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Dopusti indeksiranje tražilica',
                'indexable-hint'                   => 'Dopustite tražilicama da indeksiraju javne stranice putovnica. Isključite kako bi putovnice bile dostupne putem poveznice, ali skrivene iz rezultata pretraživanja.',
                'gs1-passport-channel'             => 'Kanal putovnice GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'Kanal na koji skenirani GS1 barkod (/01/{gtin}) upućuje kada je jedan proizvod objavljen na više kanala. Ostavite prazno za korištenje prvog omogućenog kanala.',
                'gs1-passport-channel-placeholder' => 'Prvi omogućeni kanal (automatski)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Nacrt',
            'published' => 'Objavljeno',
            'withdrawn' => 'Povučeno',
            'redacted'  => 'Prikriveno',
        ],
        'product-delete-blocked' => 'Ovaj proizvod ne može se izbrisati dok ima objavljene putovnice. Prvo ih povucite.',
        'channel-delete-blocked' => 'Ovaj kanal ne može se izbrisati dok ima objavljene putovnice. Prvo ih povucite.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Putovnica nije pronađena.',
            'notice'  => 'Ova putovnica proizvoda nije dostupna. Možda još nije objavljena ili je poveznica netočna.',
        ],
        '429' => [
            'heading' => 'Previše zahtjeva. Pokušajte ponovno uskoro.',
            'notice'  => 'Poslali ste previše zahtjeva. Pričekajte trenutak i pokušajte ponovno.',
        ],
        'withdrawn' => [
            'heading' => 'Ova putovnica više nije dostupna.',
            'notice'  => 'Ovaj zapis zadržan je radi transparentnosti, ali se više aktivno ne održava.',
        ],
    ],
];
