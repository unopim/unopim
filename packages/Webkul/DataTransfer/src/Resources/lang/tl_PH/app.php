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
        'category-fields' => [
            'title'      => 'Mga Field ng Kategorya',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Ang code ng field ng kategorya :code ay ginagamit na.',
                    'code_not_found_to_delete' => 'Hindi natagpuan ang code ng field ng kategorya para burahin.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Mga Tampok',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Ang code ng tampok na :code ay ginagamit na.',
                    'code_not_found_to_delete'             => 'Hindi natagpuan ang code ng tampok para burahin.',
                    'code_is_system_and_cannot_be_deleted' => 'Hindi maaaring burahin ang system feature.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Mga Asosasyon ng Produkto',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Kinakailangan ang field na \'%s\'.',
                    'self-link-not-allowed'       => 'Hindi maaaring iugnay ang produktong \'%s\' sa sarili nito.',
                    'sku-not-found'               => 'Hindi natagpuan ang produktong may SKU na \'%s\'.',
                    'related-sku-not-found'       => 'Hindi natagpuan ang kaugnay na produktong may SKU na \'%s\'.',
                    'association-type-not-found'  => 'Ang uri ng asosasyon na \'%s\' ay hindi umiiral o hindi aktibo.',
                    'invalid-field-value'         => 'May ibinigay na di-wastong halaga para sa field ng asosasyon.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Mga Grupo ng Tampok',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Ang code ng grupo ng tampok na :code ay ginagamit na.',
                    'code_not_found_to_delete'             => 'Hindi natagpuan ang code ng grupo ng tampok para burahin.',
                    'code_is_system_and_cannot_be_deleted' => 'Hindi maaaring burahin ang system feature group.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Mga Pamilya ng Tampok',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Ang code ng pamilya ng tampok na :code ay ginagamit na.',
                    'code_not_found_to_delete' => 'Hindi natagpuan ang code ng pamilya ng tampok para burahin.',
                    'invalid-attribute-group'  => 'Ang grupo ng tampok na ":code" ay hindi umiiral.',
                    'invalid-attribute'        => 'Ang tampok na ":code" ay hindi umiiral.',
                    'invalid-channel'          => 'Ang channel na ":code" ay hindi umiiral.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Mga Pagpipilian sa Tampok',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Ang code ng opsyon ng tampok na :code ay ginagamit na.',
                    'code_not_found_to_delete' => 'Hindi natagpuan ang code ng opsyon ng tampok para burahin.',
                    'locale-not-exist'         => 'Ang locale na ":code" ay hindi umiiral.',
                    'invalid-attribute'        => 'Ang tampok na ":code" ay hindi umiiral.',
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
            'title'   => 'Mga Pera',
            'filters' => [
                'status' => 'Katayuan',
                'enable' => 'I-enable',
                'all'    => 'Lahat',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Ang status ay dapat 0 o 1 (o walang laman para sa default na naka-enable).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Mga Tungkulin',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Mga Gumagamit',
            'filters' => [
                'status' => 'Katayuan',
                'active' => 'Aktibo',
                'all'    => 'Lahat',
            ],
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
        'export-too-large' => 'Masyadong malaki ang export na ito para patakbuhin: tinatayang :rows na hilera × :columns na kolum (~:estimated) ay lumalampas sa magagamit na espasyo (~:available). Paliitin ang export sa pamamagitan ng pagpili ng mas kaunting channel/locale (at attribute) at subukang muli.',
        'fields'           => [
            'file-format'         => 'Format ng File',
            'with-media'          => 'May Media',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Daan ng File',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Katayuan',
            'enable'         => 'Naka-enable',
            'all'            => 'Lahat',
        ],
        'products' => [
            'title'              => 'Mga Produkto',
            'invalid-locales'    => 'Hindi lahat ng napiling lokal ay available para sa mga napiling channel.',
            'invalid-currencies' => 'Hindi lahat ng napiling currency ay available para sa mga napiling channel.',
            'filters'            => [
                'channels'             => 'Mga Channel',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Mga Pera',
                'currencies-info'      => 'Ang mga price attribute ay ie-export bawat napiling currency. Iwanang blangko para i-export ang lahat ng currency ng channel.',
                'locales'              => 'Mga Lokal',
                'locales-info'         => 'Ang mga localizable attribute ay ie-export nang isang beses bawat napiling lokal. Iwanang blangko para i-export ang lahat ng lokal ng channel.',
                'attributes'           => 'Mga Attribute',
                'attributes-info'      => 'Ang mga napiling attribute lang ang ie-export. Iwanang blangko para i-export ang lahat ng attribute sa pamilya.',
                'attribute-families'   => 'Mga Pamilya ng Attribute',
                'categories'           => 'Mga Kategorya',
                'completeness'         => 'Pagkakumpleto',
                'completeness-options' => [
                    'none'         => 'Walang kundisyon sa pagkakumpleto',
                    'at-least-one' => 'Kumpleto sa hindi bababa sa isang napiling lokal',
                    'all'          => 'Kumpleto sa lahat ng napiling lokal',
                ],
                'time-condition' => 'Kundisyon ng Oras',
                'time-options'   => [
                    'none'              => 'Walang kundisyon sa petsa',
                    'last-n-days'       => 'Mga produktong na-update sa nakalipas na N araw',
                    'between-dates'     => 'Mga produktong na-update sa pagitan ng dalawang petsa',
                    'since-last-export' => 'Mga produktong na-update mula noong huling export',
                ],
                'time-value'     => 'Bilang ng mga araw',
                'time-date'      => 'Petsa ng simula',
                'time-date-end'  => 'Petsa ng pagtatapos',
                'status'         => 'Katayuan',
                'status-options' => [
                    'enable'  => 'Naka-enable',
                    'disable' => 'Naka-disable',
                    'all'     => 'Lahat',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Mga Identifier',
                'identifiers-info' => 'Mag-paste ng isang SKU / identifier bawat linya para i-export lang ang mga produktong iyon. Iwanang blangko para i-export ang lahat ng produkto.',
            ],
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
        'category-fields' => [
            'title' => 'Mga Field ng Kategorya',
        ],
        'attributes' => [
            'title' => 'Mga Tampok',
        ],
        'attribute-groups' => [
            'title' => 'Mga Grupo ng Tampok',
        ],
        'attribute-families' => [
            'title' => 'Mga Pamilya ng Tampok',
        ],
        'attribute-options' => [
            'title' => 'Mga Pagpipilian sa Tampok',
        ],
        'locales' => [
            'title' => 'Mga Wika',
        ],
        'channels' => [
            'title' => 'Mga Channel',
        ],
        'currencies' => [
            'title' => 'Mga Pera',
        ],
        'roles' => [
            'title' => 'Mga Tungkulin',
        ],
        'users' => [
            'title'   => 'Mga Gumagamit',
            'filters' => [
                'status' => 'Katayuan',
                'active' => 'Aktibo',
                'all'    => 'Lahat',
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
