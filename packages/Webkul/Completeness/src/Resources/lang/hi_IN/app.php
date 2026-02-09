<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'पूर्णता',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'पूर्णता सफलतापूर्वक अपडेट की गई',
                    'title'               => 'पूर्णता',
                    'configure'           => 'पूर्णता कॉन्फ़िगर करें',
                    'channel-required'    => 'चैनलों में आवश्यक',
                    'save-btn'            => 'सहेजें',
                    'back-btn'            => 'वापस जाएँ',
                    'mass-update-success' => 'पूर्णता सफलतापूर्वक अपडेट की गई',

                    'datagrid' => [
                        'code'             => 'कोड',
                        'name'             => 'नाम',
                        'channel-required' => 'चैनलों में आवश्यक',

                        'actions' => [
                            'change-requirement' => 'पूर्णता आवश्यकता बदलें',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'उपलब्ध नहीं',
                    'completeness'                 => 'पूर्णता',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'पूर्णता',
                    'subtitle' => 'औसत पूर्णता',
                ],

                'required-attributes' => 'आवश्यक गुण अनुपलब्ध हैं',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'गणना किए गए उत्पाद',

                'suggestion' => [
                    'low'     => 'पूर्णता कम है, सुधार के लिए विवरण जोड़ें।',
                    'medium'  => 'अच्छा काम जारी रखें, और जानकारी जोड़ते रहें।',
                    'high'    => 'लगभग पूर्ण, केवल कुछ विवरण बाकी हैं।',
                    'perfect' => 'उत्पाद की जानकारी पूरी तरह पूर्ण है।',
                ],
            ],
        ],
    ],
];
