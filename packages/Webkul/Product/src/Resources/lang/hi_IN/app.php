<?php

return [
    'acl' => [
        'variant-structures' => 'वैरिएंट संरचनाएँ',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'की प्रति -:value',
        'copy-of'                       => 'की प्रति :value',
        'variant-already-exist-message' => 'समान विशेषता विकल्पों वाला वैरिएंट पहले से मौजूद है।',
    ],

    'response' => [
        'product-can-not-be-copied' => 'प्रकार :type के उत्पादों की प्रतिलिपि नहीं बनाई जा सकती',
    ],

    'type'     => [
        'configurable'  => 'कॉन्फ़िगर करने योग्य',
        'simple'        => 'सरल',
        'variant-group' => 'वैरिएंट समूह',
        'variant_group' => 'वैरिएंट समूह',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'चक्रीय टेम्पलेट संदर्भ का पता चला: :path।',
            'currency-not-allowed'    => 'केवल कीमत विशेषताएँ मुद्रा स्वीकार करती हैं, लेकिन ":attribute" कीमत विशेषता नहीं है।',
            'currency-not-enabled'    => 'टेम्पलेट में उपयोग की गई मुद्रा ":currency" किसी भी चैनल के लिए सक्षम नहीं है।',
            'currency-not-in-channel' => 'मुद्रा ":currency" चैनल ":channel" के लिए सक्षम नहीं है।',
            'filter-failed'           => 'फ़िल्टर ":filter" को ":attribute" के मान पर लागू नहीं किया जा सका।',
            'filter-not-supported'    => 'फ़िल्टर ":filter" को विशेषता ":attribute" पर लागू नहीं किया जा सकता।',
            'format-date-position'    => 'format_date फ़िल्टर ":expression" में पहला फ़िल्टर होना चाहिए।',
            'inside-html-tag'         => 'टेम्पलेट एक्सप्रेशन को लिंक और इमेज जैसे HTML टैग या एट्रिब्यूट के अंदर नहीं रखा जा सकता।',
            'invalid-filter-argument' => '":expression" में फ़िल्टर ":filter" का आर्गुमेंट अनुपलब्ध या अमान्य है।',
            'invalid-reference'       => '":attribute" में संग्रहीत टेम्पलेट अमान्य है और उसे रेंडर नहीं किया जा सका।',
            'invalid-syntax'          => 'टेम्पलेट एक्सप्रेशन ":expression" अमान्य है। विशेषता कोड के बाद वैकल्पिक फ़िल्टर का उपयोग करें, उदाहरण के लिए: name | upper।',
            'max-depth'               => '":attribute" पर टेम्पलेट संदर्भ :max स्तरों से अधिक गहराई तक नेस्टेड हैं।',
            'missing-value'           => 'विशेषता ":attribute" का इस चैनल और लोकेल के लिए कोई मान नहीं है।',
            'not-in-family'           => 'टेम्पलेट में उपयोग की गई विशेषता ":attribute" उत्पाद परिवार से संबंधित नहीं है।',
            'output-too-large'        => 'रेंडर किया गया टेम्पलेट :max बाइट से बड़ा है और उसे काट दिया गया।',
            'self-reference'          => 'टेम्पलेट अपनी ही विशेषता ":attribute" का संदर्भ नहीं दे सकता।',
            'single-brace'            => 'एकल कर्ली ब्रेसेस में ":text" मिला। टेम्पलेट एक्सप्रेशन के लिए दोहरे कर्ली ब्रेसेस आवश्यक हैं।',
            'source-too-long'         => 'एक टेम्पलेट अधिकतम :max बाइट लंबा हो सकता है।',
            'too-complex'             => 'टेम्पलेट जाँचने के लिए बहुत जटिल है। इसे छोटा या सरल करें।',
            'too-many-expressions'    => 'एक टेम्पलेट में अधिकतम :max एक्सप्रेशन हो सकते हैं।',
            'too-many-filters'        => 'टेम्पलेट एक्सप्रेशन ":expression" अधिकतम :max फ़िल्टर का उपयोग कर सकता है।',
            'unclosed-expression'     => 'एक टेम्पलेट एक्सप्रेशन खोला गया, लेकिन कभी बंद नहीं किया गया।',
            'unknown-attribute'       => 'टेम्पलेट में उपयोग की गई विशेषता ":attribute" मौजूद नहीं है।',
            'unknown-currency'        => 'टेम्पलेट में उपयोग की गई मुद्रा ":currency" मौजूद नहीं है।',
            'unknown-filter'          => 'फ़िल्टर ":filter" समर्थित नहीं है। उपलब्ध फ़िल्टर: :filters।',
            'unsupported-attribute'   => 'विशेषता ":attribute" का उपयोग टेम्पलेट में नहीं किया जा सकता, क्योंकि इसका प्रकार समर्थित नहीं है।',
        ],
    ],
];
