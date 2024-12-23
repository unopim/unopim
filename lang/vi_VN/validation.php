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
    'accepted'        => ':attribute phải được chấp nhận.',
    'active_url'      => ':attribute không phải là một URL hợp lệ.',
    'after'           => ':attribute phải là một ngày sau :date.',
    'after_or_equal'  => ':attribute phải là một ngày bằng hoặc sau :date.',
    'alpha'           => ':attribute chỉ có thể chứa các chữ cái.',
    'alpha_dash'      => ':attribute chỉ có thể chứa các chữ cái, chữ số, dấu gạch ngang và dấu gạch dưới.',
    'alpha_num'       => ':attribute chỉ có thể chứa các chữ cái và chữ số.',
    'array'           => ':attribute phải là một mảng.',
    'before'          => ':attribute phải là một ngày trước :date.',
    'before_or_equal' => ':attribute phải là một ngày bằng hoặc trước :date.',

    'between' => [
        'numeric' => ':attribute phải trong khoảng từ :min đến :max.',
        'file'    => ':attribute phải có kích thước từ :min đến :max kilobytes.',
        'string'  => ':attribute phải có độ dài từ :min đến :max ký tự.',
        'array'   => ':attribute phải có từ :min đến :max phần tử.',
    ],

    'boolean'        => ':attribute phải là đúng hoặc sai.',
    'confirmed'      => ':attribute xác nhận không khớp.',
    'date'           => ':attribute không phải là một ngày hợp lệ.',
    'date_format'    => ':attribute không khớp với định dạng :format.',
    'different'      => ':attribute và :other phải khác nhau.',
    'digits'         => ':attribute phải có :digits chữ số.',
    'digits_between' => ':attribute phải có từ :min đến :max chữ số.',
    'dimensions'     => 'Kích thước hình ảnh của :attribute không hợp lệ.',
    'distinct'       => ':attribute có giá trị trùng lặp.',
    'email'          => ':attribute phải là một địa chỉ email hợp lệ.',
    'exists'         => ':attribute đã chọn không hợp lệ.',
    'file'           => ':attribute phải là một tệp tin.',
    'filled'         => ':attribute phải có giá trị.',

    'gt' => [
        'numeric' => ':attribute phải lớn hơn :value.',
        'file'    => ':attribute phải lớn hơn :value kilobytes.',
        'string'  => ':attribute phải dài hơn :value ký tự.',
        'array'   => ':attribute phải có nhiều hơn :value phần tử.',
    ],

    'gte' => [
        'numeric' => ':attribute phải lớn hơn hoặc bằng :value.',
        'file'    => ':attribute phải lớn hơn hoặc bằng :value kilobytes.',
        'string'  => ':attribute phải dài hơn hoặc bằng :value ký tự.',
        'array'   => ':attribute phải có ít nhất :value phần tử.',
    ],

    'image'    => ':attribute phải là một hình ảnh.',
    'in'       => ':attribute đã chọn không hợp lệ.',
    'in_array' => ':attribute không tồn tại trong :other.',
    'integer'  => ':attribute phải là một số nguyên.',
    'ip'       => ':attribute phải là một địa chỉ IP hợp lệ.',
    'ipv4'     => ':attribute phải là một địa chỉ IPv4 hợp lệ.',
    'ipv6'     => ':attribute phải là một địa chỉ IPv6 hợp lệ.',
    'json'     => ':attribute phải là một chuỗi JSON hợp lệ.',

    'lt' => [
        'numeric' => ':attribute phải nhỏ hơn :value.',
        'file'    => ':attribute phải nhỏ hơn :value kilobytes.',
        'string'  => ':attribute phải ngắn hơn :value ký tự.',
        'array'   => ':attribute phải có ít hơn :value phần tử.',
    ],

    'lte' => [
        'numeric' => ':attribute phải nhỏ hơn hoặc bằng :value.',
        'file'    => ':attribute phải nhỏ hơn hoặc bằng :value kilobytes.',
        'string'  => ':attribute phải ngắn hơn hoặc bằng :value ký tự.',
        'array'   => ':attribute không thể có nhiều hơn :value phần tử.',
    ],

    'max' => [
        'numeric' => ':attribute không được lớn hơn :max.',
        'file'    => ':attribute không được lớn hơn :max kilobytes.',
        'string'  => ':attribute không được dài hơn :max ký tự.',
        'array'   => ':attribute không được có nhiều hơn :max phần tử.',
    ],

    'mimes'     => ':attribute phải là một tệp có định dạng: :values.',
    'mimetypes' => ':attribute phải là một tệp có định dạng: :values.',
    
    'min' => [
        'numeric' => ':attribute phải lớn hơn hoặc bằng :min.',
        'file'    => ':attribute phải lớn hơn hoặc bằng :min kilobytes.',
        'string'  => ':attribute phải có ít nhất :min ký tự.',
        'array'   => ':attribute phải có ít nhất :min phần tử.',
    ],

    'not_in'               => ':attribute đã chọn không hợp lệ.',
    'not_regex'            => 'Định dạng của :attribute không hợp lệ.',
    'numeric'              => ':attribute phải là một số.',
    'present'              => ':attribute phải có mặt.',
    'regex'                => 'Định dạng của :attribute không hợp lệ.',
    'required'             => ':attribute là bắt buộc.',
    'required_if'          => ':attribute là bắt buộc khi :other là :value.',
    'required_unless'      => ':attribute là bắt buộc trừ khi :other là :values.',
    'required_with'        => ':attribute là bắt buộc khi :values có mặt.',
    'required_with_all'    => ':attribute là bắt buộc khi :values có mặt.',
    'required_without'     => ':attribute là bắt buộc khi :values không có mặt.',
    'required_without_all' => ':attribute là bắt buộc khi :values không có mặt.',
    'same'                 => ':attribute và :other phải giống nhau.',

    'size' => [
        'numeric' => ':attribute phải có giá trị là :size.',
        'file'    => ':attribute phải có kích thước :size kilobytes.',
        'string'  => ':attribute phải có độ dài :size ký tự.',
        'array'   => ':attribute phải có :size phần tử.',
    ],

    'string'   => ':attribute phải là một chuỗi ký tự.',
    'timezone' => ':attribute phải là một múi giờ hợp lệ.',
    'unique'   => ':attribute đã tồn tại.',
    'uploaded' => ':attribute tải lên không thành công.',
    'url'      => ':attribute không phải là một URL hợp lệ.',

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
            'rule-name' => 'tin nhắn tùy chỉnh',
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
