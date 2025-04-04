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
    'accepted'        => ':attribute يجب أن تكون مقبولة.',
    'active_url'      => ':attribute ليس عنوان URL صالحًا.',
    'after'           => ':attribute يجب أن تكون تاريخًا بعد :date.',
    'after_or_equal'  => ':attribute يجب أن تكون تاريخًا بعد أو مساويًا لـ :date.',
    'alpha'           => ':attribute لا يمكن أن تحتوي إلا على أحرف.',
    'alpha_dash'      => ':attribute لا يمكن أن تحتوي إلا على أحرف وأرقام وواصلات وشرطات سفلية.',
    'alpha_num'       => ':attribute لا يمكن أن تحتوي إلا على أحرف وأرقام.',
    'array'           => ':attribute يجب أن تكون مصفوفة.',
    'before'          => ':attribute يجب أن تكون تاريخًا قبل :date.',
    'before_or_equal' => ':attribute يجب أن تكون تاريخًا قبل أو مساويًا لـ :date.',

    'between' => [
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'file'    => 'يجب أن يكون :attribute بين :min و :max كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute بين :min و :max حرف.',
        'array'   => 'يجب أن يكون :attribute بين :min و :max عناصر.',
    ],

    'boolean'        => 'يجب أن يكون حقل :attribute صحيحًا أو خطأً.',
    'confirmed'      => 'لا يتطابق تأكيد :attribute.',
    'date'           => 'لا يمثل :attribute تاريخًا صالحًا.',
    'date_format'    => 'لا يتطابق :attribute مع التنسيق :format.',
    'different'      => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits'         => 'يجب أن يكون :attribute عبارة عن :digits أرقامًا.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقامًا.',
    'dimensions'     => 'يحتوي :attribute على أبعاد صورة غير صالحة.',
    'distinct'       => 'يحتوي حقل :attribute على قيمة مكررة.',
    'email'          => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'exists'         => 'السمة المحددة غير صالحة:attribute .',
    'exists-value'   => 'لا يوجد :input.',
    'extensions'     => 'يجب أن يحتوي حقل :attribute على أحد الامتدادات التالية: :values.',
    'file'           => 'يجب أن يكون :attribute ملفًا.',
    'filled'         => 'يجب أن يحتوي حقل :attribute على قيمة.',

    'gt' => [
        'numeric' => 'يجب أن تكون :attribute أكبر من :value.',
        'file'    => 'يجب أن تكون :attribute أكبر من :value كيلوبايت.',
        'string'  => 'يجب أن تكون :attribute أكبر من :value أحرف.',
        'array'   => 'يجب أن تحتوي :attribute على أكثر من :value عناصر.',
    ],

    'gte' => [
        'numeric' => 'يجب أن تكون :attribute أكبر من أو تساوي :value.',
        'file'    => 'يجب أن تكون :attribute أكبر من أو تساوي :value كيلوبايت.',
        'string'  => 'يجب أن تكون :attribute أكبر من أو تساوي :value أحرف.',
        'array'   => 'يجب أن تحتوي :attribute على عناصر :value أو أكثر.',
    ],

    'image'    => 'يجب أن تكون :attribute صورة.',
    'in'       => 'يجب أن تكون :attribute المحددة غير صالحة.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer'  => 'يجب أن تكون :attribute عددًا صحيحًا.',
    'ip'       => 'يجب أن تكون :attribute عنوان IP صالحًا.',
    'ipv4'     => 'يجب أن تكون :attribute عنوان IPv4 صالحًا.',
    'ipv6'     => 'يجب أن تكون :attribute عنوان IPv6 صالحًا.',
    'json'     => 'يجب أن تكون :attribute سلسلة JSON صالحة.',

    'lt' => [
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
        'file'    => 'يجب أن يكون :attribute أقل من :value كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute أقل من :value أحرف.',
        'array'   => 'يجب أن يكون :attribute أقل من :value عناصر.',
    ],

    'lte' => [
        'numeric' => 'يجب أن يكون :attribute أقل من أو يساوي :value.',
        'file'    => 'يجب أن يكون :attribute أقل من أو يساوي :value كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute أقل من أو يساوي :value أحرف.',
        'array'   => 'يجب ألا يحتوي :attribute على أكثر من :value عناصر.',
    ],

    'max' => [
        'numeric' => 'لا يجوز أن يكون حجم :attribute أكبر من :max.',
        'file'    => 'لا يجوز أن يكون حجم :attribute أكبر من :max كيلوبايت.',
        'string'  => 'لا يجوز أن يكون حجم :attribute أكبر من :max أحرف.',
        'array'   => 'لا يجوز أن يحتوي :attribute على أكثر من :max عناصر.',
    ],

    'mimes'     => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',

    'min' => [
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
        'file'    => 'يجب أن يكون :attribute على الأقل :min كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute على الأقل :min حرف.',
        'array'   => 'يجب أن يحتوي :attribute على الأقل :min عناصر.',
    ],

    'not_in'               => 'المحدد غير صالح :attribute.',
    'not_regex'            => 'تنسيق :attribute غير صالح.',
    'numeric'              => 'يجب أن يكون :attribute رقمًا.',
    'present'              => 'يجب أن يكون حقل :attribute موجودًا.',
    'regex'                => 'تنسيق :attribute غير صالح.',
    'required'             => 'حقل :attribute مطلوب.',
    'required_if'          => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_unless'      => 'حقل :attribute مطلوب ما لم يكن :other في :values.',
    'required_with'        => 'حقل :attribute مطلوب عندما يكون :values ​​موجودًا.',
    'required_with_all'    => 'حقل :attribute مطلوب عندما يكون :values ​​موجودًا.',
    'required_without'     => 'حقل :attribute مطلوب عندما لا يكون :values ​​موجودًا.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا يكون أي من :values ​​موجودًا.',
    'same'                 => 'يجب أن يتطابق :attribute و :other.',

    'size' => [
        'numeric' => 'يجب أن يكون :attribute بحجم :size.',
        'file'    => 'يجب أن يكون :attribute بحجم :size كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute بحجم :size أحرف.',
        'array'   => 'يجب أن يحتوي :attribute على عناصر بحجم :size.',
    ],

    'string'   => 'يجب أن تكون :attribute عبارة عن سلسلة.',
    'timezone' => 'يجب أن تكون :attribute منطقة صالحة.',
    'unique'   => 'تم أخذ :attribute بالفعل.',
    'uploaded' => 'فشل تحميل :attribute.',
    'url'      => 'تنسيق :attribute غير صالح.',

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
            'rule-name' => 'رسالة مخصصة',
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
