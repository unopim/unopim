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
    'accepted'        => ':attribute хүлээн зөвшөөрөх ёстой.',
    'active_url'      => ':attribute нь хүчинтэй URL биш байна.',
    'after'           => ':attribute нь :date ээс хойшхи огноо байх ёстой.',
    'after_or_equal'  => ':attribute нь :date тэй тэнцүү эсвэл дараах огноо байх ёстой.',
    'alpha'           => ':attribute нь зөвхөн үсэг агуулж болно.',
    'alpha_dash'      => ':attribute нь зөвхөн үсэг, тоо, зураас, доогуур зураас агуулсан байж болно.',
    'alpha_num'       => ':attribute нь зөвхөн үсэг, тоо агуулсан байж болно.',
    'array'           => ':attribute нь массив байх ёстой.',
    'before'          => ':attribute нь :date ээс өмнөх огноо байх ёстой.',
    'before_or_equal' => ':attribute нь :date өмнөх огноо эсвэл тэнцүү байх ёстой.',

    'between' => [
        'numeric' => ':attribute нь :min болон :max хооронд байх ёстой.',
        'file'    => ':attribute нь :min болон :max килобайт хооронд байх ёстой.',
        'string'  => ':attribute нь :min болон :max тэмдэгтүүдийн хооронд байх ёстой.',
        'array'   => ':attribute нь :min болон :max хооронд байх ёстой.',
    ],

    'boolean'        => ':attribute талбар нь үнэн эсвэл худал байх ёстой.',
    'confirmed'      => ':attribute баталгаажуулалт таарахгүй байна.',
    'date'           => ':attribute нь хүчинтэй огноо биш байна.',
    'date_format'    => ':attribute нь :format форматтай таарахгүй байна.',
    'different'      => ':attribute болон :other нь өөр байх ёстой.',
    'digits'         => ':attribute нь :digits байх ёстой.',
    'digits_between' => ':attribute нь :min ба :max цифрүүдийн хооронд байх ёстой.',
    'dimensions'     => ':attribute-д зургийн хэмжээ буруу байна.',
    'distinct'       => ':attribute талбар нь давхардсан утгатай байна.',
    'email'          => ':attribute нь хүчинтэй имэйл хаяг байх ёстой.',
    'exists'         => 'Сонгосон :attribute буруу байна.',
    'exists-value'   => ':input байхгүй байна.',
    'file'           => ':attribute нь файл байх ёстой.',
    'filled'         => ':attribute талбар нь утгатай байх ёстой.',

    'gt' => [
        'numeric' => ':attribute нь :value их байх ёстой.',
        'file'    => ':attribute нь :value килобайтаас их байх ёстой.',
        'string'  => ':attribute нь :value тэмдэгтээс их байх ёстой.',
        'array'   => ':attribute нь :value зүйлээс илүү байх ёстой.',
    ],

    'gte' => [
        'numeric' => ':attribute нь :value их буюу тэнцүү байх ёстой.',
        'file'    => ':attribute нь :value килобайтаас их буюу тэнцүү байх ёстой.',
        'string'  => ':attribute нь :value тэмдэгтээс их буюу тэнцүү байх ёстой.',
        'array'   => ':attribute нь :value буюу түүнээс дээш зүйлтэй байх ёстой.',
    ],

    'image'    => ':attribute нь зураг байх ёстой.',
    'in'       => 'Сонгосон :attribute буруу байна.',
    'in_array' => ':attribute талбар нь :other-д байхгүй.',
    'integer'  => ':attribute нь бүхэл тоо байх ёстой.',
    'ip'       => ':attribute нь хүчинтэй IP хаяг байх ёстой.',
    'ipv4'     => ':attribute нь хүчинтэй IPv4 хаяг байх ёстой.',
    'ipv6'     => ':attribute нь хүчинтэй IPv6 хаяг байх ёстой.',
    'json'     => ':attribute нь хүчинтэй JSON мөр байх ёстой.',

    'lt' => [
        'numeric' => ':attribute нь :value бага байх ёстой.',
        'file'    => ':attribute нь :value килобайтаас бага байх ёстой.',
        'string'  => ':attribute нь :value тэмдэгтээс бага байх ёстой.',
        'array'   => ':attribute нь :value зүйлээс бага байх ёстой.',
    ],

    'lte' => [
        'numeric' => ':attribute нь :value бага эсвэл тэнцүү байх ёстой.',
        'file'    => ':attribute нь :value килобайтаас бага буюу тэнцүү байх ёстой.',
        'string'  => ':attribute нь :value тэмдэгтээс бага эсвэл тэнцүү байх ёстой.',
        'array'   => ':attribute нь :value зүйлээс илүү байж болохгүй.',
    ],

    'max' => [
        'numeric' => ':attribute нь :max-аас их байж болохгүй.',
        'file'    => ':attribute нь :max килобайтаас их байж болохгүй.',
        'string'  => ':attribute нь :max тэмдэгтээс их байж болохгүй.',
        'array'   => ':attribute нь :max-аас илүүгүй байж болно.',
    ],

    'mimes'     => ':attribute нь :values төрлийн файл байх ёстой.',
    'mimetypes' => ':attribute нь :values ​​төрлийн файл байх ёстой.',

    'min' => [
        'numeric' => ':attribute хамгийн багадаа :min байх ёстой.',
        'file'    => ':attribute нь дор хаяж :min килобайт байх ёстой.',
        'string'  => ':attribute нь дор хаяж :min тэмдэгт байх ёстой.',
        'array'   => ':attribute дор хаяж :min зүйлтэй байх ёстой.',
    ],

    'not_in'               => 'Сонгосон :attribute буруу байна.',
    'not_regex'            => ':attribute формат буруу байна.',
    'numeric'              => ':attribute нь тоо байх ёстой.',
    'present'              => ':attribute талбар байх ёстой.',
    'regex'                => ':attribute формат буруу байна.',
    'required'             => ':attribute талбар шаардлагатай.',
    'required_if'          => ':other нь :value үед :attribute талбар шаардлагатай.',
    'required_unless'      => ':other Бусад нь :values дотор байхгүй бол :attribute талбар шаардлагатай.',
    'required_with'        => ':values байгаа үед :attribute талбар шаардлагатай.',
    'required_with_all'    => ':values байгаа үед :attribute талбар шаардлагатай.',
    'required_without'     => ':values байхгүй үед :attribute талбар шаардлагатай.',
    'required_without_all' => ':values аль нь ч байхгүй үед :attribute талбар шаардлагатай.',
    'same'                 => ':attribute болон :other нь таарч байх ёстой.',

    'size' => [
        'numeric' => ':attribute нь :size байх ёстой.',
        'file'    => ':attribute нь :size килобайт байх ёстой.',
        'string'  => ':attribute нь :size тэмдэгт байх ёстой.',
        'array'   => ':attribute нь :size зүйлсийг агуулсан байх ёстой.',
    ],

    'string'   => ':attribute нь тэмдэгт мөр байх ёстой.',
    'timezone' => ':attribute нь хүчинтэй бүс байх ёстой.',
    'unique'   => ':attribute аль хэдийн авсан.',
    'uploaded' => ':attribute байршуулж чадсангүй.',
    'url'      => ':attribute формат буруу байна.',

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
            'rule-name' => 'захиалгат мессеж',
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
