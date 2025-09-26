<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Kumpletong',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Matagumpay na na-update ang kumpletong',
                    'title'               => 'Kumpletong',
                    'configure'           => 'I-configure ang Kumpletong',
                    'channel-required'    => 'Kinakailangan sa mga channel',
                    'save-btn'            => 'I-save',
                    'back-btn'            => 'Bumalik',
                    'mass-update-success' => 'Matagumpay na na-update ang kumpletong',

                    'datagrid' => [
                        'code'             => 'Code',
                        'name'             => 'Pangalan',
                        'channel-required' => 'Kinakailangan sa mga channel',

                        'actions' => [
                            'change-requirement' => 'Baguhin ang Kahilingan sa Kumpletong',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Walang setting',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Kumpletong',
                    'subtitle' => 'Average completeness',
                ],

                'required-attributes' => 'mga nawawalang kinakailangang attributes',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Mga na-compute na produkto',

                'suggestion' => [
                    'low'     => 'Mababang kumpletong — magdagdag ng detalye para mapabuti.',
                    'medium'  => 'Magpatuloy, magpatuloy sa pagdaragdag ng impormasyon.',
                    'high'    => 'Halos kumpleto, iilang detalye na lang ang natitira.',
                    'perfect' => 'Ang impormasyon ng produkto ay ganap na kumpleto.',
                ],
            ],
        ],
    ],
];
