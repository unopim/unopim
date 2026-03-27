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
                    'back-btn'            => 'वापस',
                    'mass-update-success' => 'पूर्णता सफलतापूर्वक अपडेट की गई',
                    'datagrid'            => [
                        'code'             => 'कोड',
                        'name'             => 'नाम',
                        'channel-required' => 'चैनलों में आवश्यक',
                        'actions'          => [
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
                    'completeness'                 => 'पूर्ण',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'पूर्णता',
                    'subtitle' => 'औसत पूर्णता',
                ],
                'required-attributes' => 'आवश्यक गुण अनुपलब्ध',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'पूर्णता गणना पूरी हुई',
        'completeness-calculated'        => ':count उत्पादों के लिए पूर्णता की गणना की गई।',
        'completeness-calculated-family' => 'परिवार ":family" में :count उत्पादों के लिए पूर्णता की गणना की गई।',
        'email-subject'                  => 'पूर्णता गणना पूरी हुई',
        'email-greeting'                 => 'नमस्कार,',
        'email-body'                     => ':count उत्पादों के लिए पूर्णता गणना पूरी हो गई है।',
        'email-body-family'              => 'गुण परिवार ":family" में :count उत्पादों के लिए पूर्णता गणना पूरी हो गई है।',
        'email-footer'                   => 'आप अपने डैशबोर्ड पर पूर्णता विवरण देख सकते हैं।',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'गणना किए गए उत्पाद',
                'suggestion'          => [
                    'low'     => 'कम पूर्णता, सुधार के लिए विवरण जोड़ें।',
                    'medium'  => 'जारी रखें, जानकारी जोड़ते रहें।',
                    'high'    => 'लगभग पूर्ण, बस कुछ विवरण शेष हैं।',
                    'perfect' => 'उत्पाद की जानकारी पूरी तरह से पूर्ण है।',
                ],
            ],
        ],
    ],
];
