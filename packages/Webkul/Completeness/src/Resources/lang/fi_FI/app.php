<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Täydellisyys',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Täydellisyys päivitetty onnistuneesti',
                    'title'               => 'Täydellisyys',
                    'configure'           => 'Määritä täydellisyys',
                    'channel-required'    => 'Vaaditaan kanavissa',
                    'save-btn'            => 'Tallenna',
                    'back-btn'            => 'Takaisin',
                    'mass-update-success' => 'Täydellisyys päivitetty onnistuneesti',
                    'datagrid'            => [
                        'code'             => 'Koodi',
                        'name'             => 'Nimi',
                        'channel-required' => 'Vaaditaan kanavissa',
                        'actions'          => [
                            'change-requirement' => 'Muuta täydellisyysvaatimusta',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ei saatavilla',
                    'completeness'                 => 'Valmis',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Täydellisyys',
                    'subtitle' => 'Keskimääräinen täydellisyys',
                ],
                'required-attributes' => 'puuttuvat pakolliset attribuutit',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Täydellisyyslaskenta valmis',
        'completeness-calculated'        => 'Täydellisyys laskettu :count tuotteelle.',
        'completeness-calculated-family' => 'Täydellisyys laskettu :count tuotteelle perheessä ":family".',
        'email-subject'                  => 'Täydellisyyslaskenta valmis',
        'email-greeting'                 => 'Hei,',
        'email-body'                     => 'Täydellisyyslaskenta on suoritettu :count tuotteelle.',
        'email-body-family'              => 'Täydellisyyslaskenta on suoritettu :count tuotteelle attribuuttiperheessä ":family".',
        'email-footer'                   => 'Voit tarkastella täydellisyystietoja hallintapaneelissasi.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Lasketut tuotteet',
                'suggestion'          => [
                    'low'     => 'Alhainen täydellisyys, lisää tietoja parantaaksesi.',
                    'medium'  => 'Jatka, lisää tietoja edelleen.',
                    'high'    => 'Lähes valmis, vain muutama yksityiskohta puuttuu.',
                    'perfect' => 'Tuotetiedot ovat täysin valmiit.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Täydellisyyspisteiden värit',
            'info'            => 'Määritä pisteet, joilla täydellisyysilmaisimet muuttuvat vihreiksi tai punaisiksi.',
            'green-from'      => 'Vihreä alkaen (%)',
            'green-from-info' => 'Tämän arvon suuruiset tai suuremmat pisteet näytetään vihreänä.',
            'red-below'       => 'Punainen alle (%)',
            'red-below-info'  => 'Tätä arvoa pienemmät pisteet näytetään punaisena. Niiden väliin jäävät näytetään keltaisena.',
            'order-error'     => 'Vihreän raja-arvon on oltava suurempi kuin punaisen raja-arvon.',
        ],
    ],
];
