<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Pagkakumpleto',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Matagumpay na na-update ang pagkakumpleto',
                    'title'               => 'Pagkakumpleto',
                    'configure'           => 'I-configure ang pagkakumpleto',
                    'channel-required'    => 'Kinakailangan sa mga channel',
                    'save-btn'            => 'I-save',
                    'back-btn'            => 'Bumalik',
                    'mass-update-success' => 'Matagumpay na na-update ang pagkakumpleto',
                    'datagrid'            => [
                        'code'             => 'Kodigo',
                        'name'             => 'Pangalan',
                        'channel-required' => 'Kinakailangan sa mga channel',
                        'actions'          => [
                            'change-requirement' => 'Baguhin ang kinakailangan sa pagkakumpleto',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Walang N/A',
                    'completeness'                 => 'Kumpleto',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Pagkakumpleto',
                    'subtitle' => 'Average na pagkakumpleto',
                ],
                'required-attributes' => 'mga nawawalang kinakailangang attribute',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Nakumpleto na ang pagkalkula ng pagkakumpleto',
        'completeness-calculated'        => 'Nakalkula ang pagkakumpleto para sa :count na produkto.',
        'completeness-calculated-family' => 'Nakalkula ang pagkakumpleto para sa :count na produkto sa pamilya na ":family".',
        'email-subject'                  => 'Nakumpleto na ang pagkalkula ng pagkakumpleto',
        'email-greeting'                 => 'Kumusta,',
        'email-body'                     => 'Ang pagkalkula ng pagkakumpleto ay nakumpleto na para sa :count na produkto.',
        'email-body-family'              => 'Ang pagkalkula ng pagkakumpleto ay nakumpleto na para sa :count na produkto sa attribute family na ":family".',
        'email-footer'                   => 'Maaari mong tingnan ang mga detalye ng pagkakumpleto sa iyong dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Mga nakalkula na produkto',
                'suggestion'          => [
                    'low'     => 'Mababang pagkakumpleto, magdagdag ng mga detalye para mapabuti.',
                    'medium'  => 'Ipagpatuloy, patuloy na magdagdag ng impormasyon.',
                    'high'    => 'Halos kumpleto na, kaunting detalye na lang ang kulang.',
                    'perfect' => 'Ganap na kumpleto ang impormasyon ng produkto.',
                ],
            ],
        ],
    ],
];
