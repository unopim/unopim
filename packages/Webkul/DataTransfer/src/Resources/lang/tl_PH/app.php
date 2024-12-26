<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Mga Produkto',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Ang URL key: \'%s\' ay na-generate na para sa isang item na may SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Walang katanggap-tanggap na halaga para sa kolum ng pamilya ng mga attribute (ang pamilya ng mga attribute ay wala?)',
                    'invalid-type'                             => 'Ang uri ng produkto ay hindi wasto o hindi suportado',
                    'sku-not-found'                            => 'Hindi natagpuan ang produkto na may tukoy na SKU',
                    'super-attribute-not-found'                => 'Ang konfigurableng attribute na may code: \'%s\' ay hindi natagpuan o hindi kabilang sa pamilya ng mga attribute: \'%s\'',
                    'configurable-attributes-not-found'        => 'Kailangan ang mga konfigurableng attribute para lumikha ng modelo ng produkto',
                    'configurable-attributes-wrong-type'       => 'Ang mga atributong uri na hindi kanais-nais o batay sa lokasyon ay hindi maaaring maging mga konfigurableng atribut para sa isang konfigurableng produkto',
                    'variant-configurable-attribute-not-found' => 'Ang variant ng konfigurableng attribute: :code ay kinakailangan para lumikha',
                    'not-unique-variant-product'               => 'May produkto na may parehong mga konfigurableng attribute.',
                    'channel-not-exist'                        => 'Walang ganitong channel.',
                    'locale-not-in-channel'                    => 'Ang lokasyon na ito ay hindi napili sa channel.',
                    'locale-not-exist'                         => 'Walang ganitong lokasyon',
                    'not-unique-value'                         => 'Ang halaga :code ay dapat na natatangi.',
                    'incorrect-family-for-variant'             => 'Ang pamilya ay dapat na parehong-pareho sa pangunahing pamilya',
                    'parent-not-exist'                         => 'Walang magulang.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Mga Kategorya',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Hindi mo maaaring burahin ang ugat na kategorya na nauugnay sa isang channel',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Mga Produkto',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Ang URL key: \'%s\' ay na-generate na para sa isang item na may SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Walang katanggap-tanggap na halaga para sa kolum ng pamilya ng mga attribute (ang pamilya ng mga attribute ay wala?)',
                    'invalid-type'              => 'Ang uri ng produkto ay hindi wasto o hindi suportado',
                    'sku-not-found'             => 'Hindi natagpuan ang produkto na may tukoy na SKU',
                    'super-attribute-not-found' => 'Ang konfigurableng attribute na may code: \'%s\' ay hindi natagpuan o hindi kabilang sa pamilya ng mga attribute: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Mga Kategorya',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Mga kolum na may mga numero "%s" ay may mga walang laman na mga ulo.',
            'column-name-invalid'  => 'Mga hindi wasto na pangalan ng mga kolum: "%s".',
            'column-not-found'     => 'Hindi natagpuan ang mga kinakailangang kolum: %s.',
            'column-numbers'       => 'Ang bilang ng mga kolum ay hindi tumutugma sa bilang ng mga linya sa ulo.',
            'invalid-attribute'    => 'Ang ulo ay may hindi wasto na mga attribute: "%s".',
            'system'               => 'Isang hindi inaasahang error ng sistema ang nangyari.',
            'wrong-quotes'         => 'Ginamit ang mga kurbadong mga quote sa halip na mga tuwid na quote.',
        ],
    ],
    'job' => [
        'started'   => 'Nagsimula ang trabaho sa trabaho',
        'completed' => 'Nagtapos ang trabaho sa trabaho',
    ],
];
