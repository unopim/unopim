<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'accepted'        => ':attribute को स्वीकार किया जाना चाहिए।',
    'active_url'      => ':attribute एक मान्य URL नहीं है।',
    'after'           => ':attribute :date के बाद की तारीख होनी चाहिए।',
    'after_or_equal'  => ':attribute :date के बाद की तारीख या उसके बराबर होनी चाहिए।',
    'alpha'           => ':attribute में केवल अक्षर हो सकते हैं।',
    'alpha_dash'      => ':attribute में केवल अक्षर, संख्याएँ, डैश और अंडरस्कोर हो सकते हैं।',
    'alpha_num'       => ':attribute में केवल अक्षर और संख्याएँ हो सकती हैं।',
    'array'           => ':attribute एक सरणी होनी चाहिए।',
    'before'          => ':attribute :date से पहले की तारीख होनी चाहिए।',
    'before_or_equal' => ':attribute :date से पहले की तारीख या उसके बराबर होनी चाहिए।',

    'between' => [
        'numeric' => ':attribute :min और :max के बीच होनी चाहिए।',
        'file'    => ':attribute :min और :max किलोबाइट के बीच होनी चाहिए।',
        'string'  => ':attribute :min और :max वर्णों के बीच होनी चाहिए।',
        'array'   => ':attribute में :min और :max आइटम होने चाहिए।',
    ],

    'boolean'        => ':attribute फ़ील्ड सही या गलत होनी चाहिए।',
    'confirmed'      => ':attribute पुष्टि मेल नहीं खाती।',
    'date'           => ':attribute एक वैध दिनांक नहीं है।',
    'date_format'    => ':attribute फ़ॉर्मेट :format से मेल नहीं खाता।',
    'different'      => ':attribute और :other अलग-अलग होने चाहिए।',
    'digits'         => ':attribute :digits अंक होना चाहिए।',
    'digits_between' => ':attribute :min और :max अंकों के बीच होना चाहिए।',
    'dimensions'     => ':attribute में अमान्य छवि आयाम हैं।',
    'distinct'       => ':attribute फ़ील्ड में डुप्लिकेट मान है।',
    'email'          => ':attribute एक वैध ईमेल पता होना चाहिए।',
    'exists'         => 'चयनित :attribute अमान्य है।',
    'exists-value'   => ':input मौजूद नहीं है।',
    'extensions'     => ':attribute फ़ील्ड में निम्नलिखित एक्सटेंशन में से एक होना चाहिए: :values.',
    'file'           => ':attribute एक फ़ाइल होनी चाहिए।',
    'filled'         => ':attribute फ़ील्ड में एक मान होना चाहिए।',

    'gt' => [
        'numeric' => ':attribute :value से बड़ा होना चाहिए।',
        'file'    => ':attribute :value किलोबाइट से बड़ा होना चाहिए।',
        'string'  => ':attribute :value वर्णों से बड़ा होना चाहिए।',
        'array'   => ':attribute में :value से अधिक आइटम होने चाहिए.',
    ],

    'gte' => [
        'numeric' => ':attribute :value से बड़ा या बराबर होना चाहिए।',
        'file'    => ':attribute :value किलोबाइट से बड़ा या बराबर होना चाहिए।',
        'string'  => ':attribute :value वर्णों से बड़ा या बराबर होना चाहिए।',
        'array'   => ':attribute में :value आइटम या उससे ज़्यादा होने चाहिए।',
    ],

    'image'    => ':attribute एक इमेज होनी चाहिए।',
    'in'       => 'चुनी गई :attribute अमान्य है।',
    'in_array' => ':attribute फ़ील्ड :other में मौजूद नहीं है।',
    'integer'  => ':attribute एक पूर्णांक होना चाहिए।',
    'ip'       => ':attribute एक मान्य IP पता होना चाहिए।',
    'ipv4'     => ':attribute एक मान्य IPv4 पता होना चाहिए।',
    'ipv6'     => ':attribute एक मान्य IPv6 पता होना चाहिए।',
    'json'     => ':attribute एक मान्य JSON स्ट्रिंग होनी चाहिए।',

    'lt' => [
        'numeric' => ':attribute :value से कम होनी चाहिए।',
        'file'    => ':attribute :value किलोबाइट से कम होनी चाहिए।',
        'string'  => ':attribute :value वर्णों से कम होनी चाहिए।',
        'array'   => ':attribute में :value से कम आइटम होने चाहिए.',
    ],

    'lte' => [
        'numeric' => ':attribute :value से कम या बराबर होना चाहिए।',
        'file'    => ':attribute :value किलोबाइट से कम या बराबर होना चाहिए।',
        'string'  => ':attribute :value वर्णों से कम या बराबर होना चाहिए।',
        'array'   => ':attribute में :value से ज़्यादा आइटम नहीं होने चाहिए।',
    ],

    'max' => [
        'numeric' => ':attribute :max से ज़्यादा नहीं हो सकता।',
        'file'    => ':attribute :max किलोबाइट से ज़्यादा नहीं हो सकता।',
        'string'  => ':attribute :max वर्णों से ज़्यादा नहीं हो सकता।',
        'array'   => ':attribute में :max से ज़्यादा आइटम नहीं हो सकते।',
    ],

    'mimes'     => ':attribute :values ​​प्रकार की फ़ाइल होनी चाहिए।',
    'mimetypes' => ':attribute :values ​​प्रकार की फ़ाइल होनी चाहिए।',

    'min' => [
        'numeric' => ':attribute कम से कम :min होनी चाहिए।',
        'file'    => ':attribute कम से कम :min किलोबाइट होनी चाहिए।',
        'string'  => ':attribute कम से कम :min वर्णों की होनी चाहिए।',
        'array'   => ':attribute में कम से कम :min आइटम होने चाहिए।',
    ],

    'not_in'               => 'चयनित :attribute अमान्य है।',
    'not_regex'            => ':attribute प्रारूप अमान्य है।',
    'numeric'              => ':attribute एक संख्या होनी चाहिए।',
    'present'              => ':attribute फ़ील्ड मौजूद होनी चाहिए।',
    'regex'                => ':attribute प्रारूप अमान्य है।',
    'required'             => ':attribute फ़ील्ड आवश्यक है।',
    'required_if'          => ':attribute फ़ील्ड तब आवश्यक है जब :other :value हो।',
    'required_unless'      => ':attribute फ़ील्ड तब आवश्यक है जब :other :values ​​में न हो।',
    'required_with'        => ':attribute फ़ील्ड तब आवश्यक है जब :values ​​मौजूद हो।',
    'required_with_all'    => ':attribute फ़ील्ड की आवश्यकता तब होती है जब :values ​​मौजूद हो।',
    'required_without'     => ':attribute फ़ील्ड की आवश्यकता तब होती है जब :values ​​मौजूद न हो।',
    'required_without_all' => ':attribute फ़ील्ड की आवश्यकता तब होती है जब :values ​​में से कोई भी मौजूद न हो।',
    'same'                 => ':attribute और :other का मिलान होना चाहिए।',

    'size' => [
        'numeric' => ':attribute :size होना चाहिए।',
        'file'    => ':attribute :size किलोबाइट होना चाहिए।',
        'string'  => ':attribute :size वर्ण होना चाहिए।',
        'array'   => ':attribute में :size आइटम होने चाहिए।',
    ],

    'string'   => ':attribute एक स्ट्रिंग होना चाहिए।',
    'timezone' => ':attribute एक वैध ज़ोन होना चाहिए।',
    'unique'   => ':attribute पहले ही ले लिया गया है।',
    'uploaded' => ':attribute अपलोड करने में विफल रहा।',
    'url'      => ':attribute प्रारूप अमान्य है।',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'कस्टम-संदेश',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [],
];
