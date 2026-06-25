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
                    'super-attribute-not-found'                => 'Ang konfigurableng attribute na may code: \'%s\' ay hindi natagpuan o hindi kabilang sa pamilya ng mga attribute: \'%s\' :code :familyCode',
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
        'locales' => [
            'title'      => 'Mga Wika',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Ang code ng wika \'%s\' ay na-import na sa batch na ito.',
                    'code-not-found-to-delete'    => 'Ang wikang may code \'%s\' ay hindi natagpuan sa system.',
                    'invalid-status'              => 'Ang status ay dapat 0 o 1 (o walang laman para sa default na naka-enable).',
                    'channel-related-locale-root' => 'Hindi mo maaaring tanggalin ang wikang may code :code dahil ito ay naka-ugnay sa isang channel.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Mga Channel',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Hindi nahanap ang channel na may code :code para tanggalin.',
                    'locale-not-found'         => 'Isa o higit pang wika ay hindi umiiral.',
                    'root-category-not-found'  => 'Ang root na kategorya ay hindi umiiral.',
                    'currency-not-found'       => 'Isa o higit pang currency ay hindi umiiral.',
                    'invalid-locale'           => 'Ang wika ay hindi umiiral.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'locales' => [
            'title' => 'Mga Wika',
        ],
        'channels' => [
            'title' => 'Mga Channel',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
            'file-empty'           => 'Ang file ay walang laman o walang header row. Mangyaring mag-upload ng wastong file na may datos.',
        ],
    ],
    'job' => [
        'started'   => 'Nagsimula ang trabaho sa trabaho',
        'completed' => 'Nagtapos ang trabaho sa trabaho',
    ],
];
