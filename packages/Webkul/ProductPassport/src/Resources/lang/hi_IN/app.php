<?php

return [
    'type' => [
        'label' => 'डिजिटल उत्पाद पासपोर्ट',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'उत्पाद पासपोर्ट',
            'info'     => 'डिजिटल उत्पाद पासपोर्ट के प्रकाशन की सेटिंग्स।',
            'settings' => [
                'title'                  => 'उत्पाद पासपोर्ट सेटिंग्स',
                'enabled'                => 'सक्षम',
                'auto-publish'           => 'सहेजते समय स्वतः प्रकाशित करें',
                'completeness-threshold' => 'पूर्णता सीमा (%)',
                'operator-name'          => 'आर्थिक ऑपरेटर का नाम',
                'operator-address'       => 'आर्थिक ऑपरेटर का पता',
                'operator-eu-rep'        => 'EU अधिकृत प्रतिनिधि',
                'support-url'            => 'सहायता URL',
            ],
        ],
    ],
];
