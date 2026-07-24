<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicació',
            'info'     => 'Nivell de servei públic per a contingut publicat, per idioma.',
            'settings' => [
                'title'                            => 'Configuració de publicació',
                'enabled'                          => 'Activat',
                'enabled-hint'                     => 'Interruptor principal del nivell de servei públic. Quan està desactivat, cada URL pública de passaport retorna un 404 i el menú de passaports queda ocult.',
                'base-url'                         => 'URL base',
                'base-url-hint'                    => 'Adreça pública on es serveixen els passaports; s\'utilitza per generar codis QR i enllaços per compartir. Deixeu-ho en blanc per utilitzar el domini propi d\'aquest lloc.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'TTL de la memòria cau (segons)',
                'cache-ttl-hint'                   => 'Durant quant de temps es desa a la memòria cau un passaport públic renderitzat abans de reconstruir-lo. Els valors més alts redueixen la càrrega; els més baixos reflecteixen els canvis abans.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Límit de velocitat (sol·licituds/minut)',
                'rate-limit-hint'                  => 'Nombre màxim de sol·licituds de passaport públic permeses cada minut des d\'un sol visitant abans que se\'l limiti.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Permet la indexació per motors de cerca',
                'indexable-hint'                   => 'Permet que els motors de cerca indexin les pàgines públiques de passaport. Desactiveu-ho per mantenir els passaports accessibles per enllaç però ocults als resultats de cerca.',
                'gs1-passport-channel'             => 'Canal de passaport de GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'El canal al qual es resol un codi de barres GS1 escanejat (/01/{gtin}) quan un producte es publica en diversos canals. Deixeu-ho en blanc per utilitzar el primer canal habilitat.',
                'gs1-passport-channel-placeholder' => 'Primer canal habilitat (automàtic)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Esborrany',
            'published' => 'Publicat',
            'withdrawn' => 'Retirat',
            'redacted'  => 'Censurat',
        ],
        'product-delete-blocked' => 'Aquest producte no es pot suprimir mentre tingui passaports publicats. Retireu-los primer.',
        'channel-delete-blocked' => 'Aquest canal no es pot suprimir mentre tingui passaports publicats. Retireu-los primer.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaport no trobat.',
            'notice'  => 'Aquest passaport de producte no està disponible. És possible que encara no s\'hagi publicat o que l\'enllaç sigui incorrecte.',
        ],
        '429' => [
            'heading' => 'Massa sol·licituds. Torneu-ho a provar d\'aquí a poc.',
            'notice'  => 'Has fet massa sol·licituds. Espera un moment i torna-ho a provar.',
        ],
        'withdrawn' => [
            'heading' => 'Aquest passaport ja no està disponible.',
            'notice'  => 'Aquest registre es conserva per transparència, però ja no es manté activament.',
        ],
    ],
];
