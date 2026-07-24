<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicatie',
            'info'     => 'Openbare serveerlaag voor gepubliceerde, taalspecifieke inhoud.',
            'settings' => [
                'title'                            => 'Publicatie-instellingen',
                'enabled'                          => 'Ingeschakeld',
                'base-url'                         => 'Basis-URL',
                'cache-ttl'                        => 'Cache-TTL (seconden)',
                'rate-limit'                       => 'Snelheidslimiet (verzoeken/minuut)',
                'indexable'                        => 'Indexering door zoekmachines toestaan',
                'enabled-hint'                     => 'Hoofdschakelaar voor de openbare weergavelaag. Wanneer uit, geeft elke openbare paspoort-URL een 404 terug en wordt het paspoortmenu verborgen.',
                'base-url-hint'                    => 'Openbaar adres waar paspoorten worden aangeboden, gebruikt om QR-codes en deelbare links te bouwen. Laat leeg om het eigen domein van deze site te gebruiken.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Hoe lang een gerenderd openbaar paspoort in de cache blijft voordat het opnieuw wordt opgebouwd. Hogere waarden verlagen de belasting; lagere waarden tonen wijzigingen sneller.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Maximaal aantal openbare paspoortverzoeken dat per minuut van één bezoeker is toegestaan voordat deze wordt beperkt.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Laat zoekmachines openbare paspoortpagina\'s indexeren. Schakel uit om paspoorten via een link bereikbaar te houden maar verborgen voor zoekresultaten.',
                'gs1-passport-channel'             => 'GS1 Digital Link-paspoortkanaal',
                'gs1-passport-channel-hint'        => 'Het kanaal waarnaar een gescande GS1-barcode (/01/{gtin}) verwijst wanneer één product op meerdere kanalen is gepubliceerd. Laat leeg om het eerste ingeschakelde kanaal te gebruiken.',
                'gs1-passport-channel-placeholder' => 'Eerste ingeschakelde kanaal (automatisch)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Concept',
            'published' => 'Gepubliceerd',
            'withdrawn' => 'Ingetrokken',
            'redacted'  => 'Bewerkt (geredigeerd)',
        ],
        'product-delete-blocked' => 'Dit product kan niet worden verwijderd zolang er gepubliceerde paspoorten zijn. Trek deze eerst in.',
        'channel-delete-blocked' => 'Dit kanaal kan niet worden verwijderd zolang er gepubliceerde paspoorten zijn. Trek deze eerst in.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Paspoort niet gevonden.',
            'notice'  => 'Dit productpaspoort is niet beschikbaar. Het is mogelijk nog niet gepubliceerd of de link is onjuist.',
        ],
        '429' => [
            'heading' => 'Te veel verzoeken. Probeer het straks opnieuw.',
            'notice'  => 'U hebt te veel verzoeken gedaan. Wacht even en probeer het opnieuw.',
        ],
        'withdrawn' => [
            'heading' => 'Dit paspoort is niet langer beschikbaar.',
            'notice'  => 'Dit record wordt bewaard omwille van transparantie, maar wordt niet langer actief bijgehouden.',
        ],
    ],
];
