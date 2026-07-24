<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicare',
            'info'     => 'Nivel de difuzare publică pentru conținut publicat, pe fiecare limbă.',
            'settings' => [
                'title'                            => 'Setări de publicare',
                'enabled'                          => 'Activat',
                'base-url'                         => 'URL de bază',
                'cache-ttl'                        => 'TTL cache (secunde)',
                'rate-limit'                       => 'Limită de rată (cereri/minut)',
                'indexable'                        => 'Permite indexarea de către motoarele de căutare',
                'enabled-hint'                     => 'Comutatorul principal pentru nivelul public de servire. Când este dezactivat, fiecare URL public de pașaport returnează 404, iar meniul de pașapoarte este ascuns.',
                'base-url-hint'                    => 'Adresa publică unde sunt servite pașapoartele, folosită pentru a crea coduri QR și linkuri care pot fi partajate. Lăsați necompletat pentru a folosi domeniul propriu al acestui site.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Cât timp este păstrat în cache un pașaport public randat înainte de a fi reconstruit. Valorile mai mari reduc încărcarea; valorile mai mici reflectă modificările mai repede.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Numărul maxim de cereri publice de pașaport permise pe minut de la un singur vizitator înainte de a fi limitat.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Permiteți motoarelor de căutare să indexeze paginile publice de pașaport. Dezactivați pentru a păstra pașapoartele accesibile prin link, dar ascunse din rezultatele căutării.',
                'gs1-passport-channel'             => 'Canal de pașaport GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'Canalul către care se rezolvă un cod de bare GS1 scanat (/01/{gtin}) atunci când un produs este publicat pe mai multe canale. Lăsați necompletat pentru a folosi primul canal activat.',
                'gs1-passport-channel-placeholder' => 'Primul canal activat (automat)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Ciornă',
            'published' => 'Publicat',
            'withdrawn' => 'Retras',
            'redacted'  => 'Cenzurat',
        ],
        'product-delete-blocked' => 'Acest produs nu poate fi șters atât timp cât are pașapoarte publicate. Retrageți-le mai întâi.',
        'channel-delete-blocked' => 'Acest canal nu poate fi șters atât timp cât are pașapoarte publicate. Retrageți-le mai întâi.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pașaportul nu a fost găsit.',
            'notice'  => 'Acest pașaport de produs nu este disponibil. Este posibil să nu fi fost publicat încă sau linkul să fie incorect.',
        ],
        '429' => [
            'heading' => 'Prea multe cereri. Încercați din nou în scurt timp.',
            'notice'  => 'Ați efectuat prea multe solicitări. Așteptați un moment și încercați din nou.',
        ],
        'withdrawn' => [
            'heading' => 'Acest pașaport nu mai este disponibil.',
            'notice'  => 'Această înregistrare este păstrată pentru transparență, dar nu mai este menținută activ.',
        ],
    ],
];
