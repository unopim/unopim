<?php

return [
    'warning' => [
        'title'           => 'APP_URL में बेमेल पाया गया',
        'dismiss'         => 'खारिज करें',
        'lede-before'     => 'आपके फ्रंटएंड संसाधन (CSS, JS) कॉन्फ़िगर किए गए मान से जुड़े हैं',
        'lede-after'      => 'इसे उस होस्ट से मेल खाने के लिए अपडेट करें जिसका आप उपयोग कर रहे हैं, अन्यथा स्टाइल और स्क्रिप्ट लोड नहीं होंगी।',
        'configured-env'  => 'कॉन्फ़िगर किया गया (.env)',
        'mismatch-tag'    => 'बेमेल',
        'actual-browser'  => 'वास्तविक (ब्राउज़र)',
        'in-use-tag'      => 'उपयोग में',
        'toggle-step'     => 'चरण :number टॉगल करें',
        'step-1-title'    => 'अपनी .env फ़ाइल में APP_URL अपडेट करें',
        'step-1-hint'     => 'प्रोजेक्ट की .env खोलें और APP_URL लाइन को बदलें।',
        'step-2-title'    => 'एप्लिकेशन कैश साफ़ करें',
        'step-2-hint'     => 'इसे प्रोजेक्ट रूट से अपने टर्मिनल में चलाएं।',
        'copy'            => 'कॉपी करें',
        'copied'          => 'कॉपी हो गया',
        'note-bold'       => 'फिर पेज को हार्ड रिफ्रेश करें',
        'note-rest'       => 'ताकि ब्राउज़र अपडेट किए गए संसाधनों को फिर से लोड करे।',
        'progress'        => ':total में से :done चरण पूर्ण',
        'all-done'        => 'सब हो गया',
        'powered-by'      => 'द्वारा संचालित',
        'open-source-by'  => 'एक ओपन-सोर्स प्रोजेक्ट, द्वारा',
        'copied-toast'    => 'क्लिपबोर्ड पर कॉपी किया गया',
        'still-mismatch'  => 'APP_URL अभी भी मेल नहीं खाता। .env अपडेट करें और "php artisan optimize:clear" चलाएं।',
        'verify-failed'   => 'APP_URL सत्यापित नहीं किया जा सका। कृपया पेज को रिफ्रेश करें।',
        'logged-out'      => 'लॉग आउट: APP_URL वर्तमान होस्ट से मेल नहीं खाता। .env में APP_URL अपडेट करें और "php artisan optimize:clear" चलाएं।',
    ],

    'log' => [
        'mismatch' => 'APP_URL में बेमेल पाया गया',
        'hint'     => '.env में APP_URL को अनुरोध URL से अपडेट करें, फिर चलाएं: php artisan optimize:clear',
    ],
];
