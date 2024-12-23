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
    'accepted'        => ':attribute precisa ser aceito.',
    'active_url'      => ':attribute não é uma URL válida.',
    'after'           => ':attribute precisa ser uma data posterior a :date.',
    'after_or_equal'  => ':attribute precisa ser uma data posterior ou igual a :date.',
    'alpha'           => ':attribute pode conter apenas letras.',
    'alpha_dash'      => ':attribute pode conter apenas letras, números, hifens e sublinhados.',
    'alpha_num'       => ':attribute pode conter apenas letras e números.',
    'array'           => ':attribute precisa ser um array.',
    'before'          => ':attribute precisa ser uma data anterior a :date.',
    'before_or_equal' => ':attribute precisa ser uma data anterior ou igual a :date.',

    'between' => [
        'numeric' => ':attribute precisa estar entre :min e :max.',
        'file'    => ':attribute precisa estar entre :min e :max kilobytes.',
        'string'  => ':attribute precisa estar entre :min e :max caracteres.',
        'array'   => ':attribute precisa ter entre :min e :max itens.',
    ],

    'boolean'        => ':attribute precisa ser verdadeiro ou falso.',
    'confirmed'      => 'A confirmação de :attribute não confere.',
    'date'           => ':attribute não é uma data válida.',
    'date_format'    => ':attribute não corresponde ao formato :format.',
    'different'      => ':attribute e :other precisam ser diferentes.',
    'digits'         => ':attribute precisa ter :digits dígitos.',
    'digits_between' => ':attribute precisa ter entre :min e :max dígitos.',
    'dimensions'     => 'As dimensões da imagem de :attribute são inválidas.',
    'distinct'       => ':attribute possui um valor duplicado.',
    'email'          => ':attribute precisa ser um endereço de e-mail válido.',
    'exists'         => 'O :attribute selecionado é inválido.',
    'exists-value'   => ':input não existe.',
    'file'           => ':attribute precisa ser um arquivo.',
    'filled'         => ':attribute precisa ter um valor.',

    'gt' => [
        'numeric' => ':attribute precisa ser maior que :value.',
        'file'    => ':attribute precisa ser maior que :value kilobytes.',
        'string'  => ':attribute precisa ser maior que :value caracteres.',
        'array'   => ':attribute precisa ter mais de :value itens.',
    ],

    'gte' => [
        'numeric' => ':attribute precisa ser maior ou igual a :value.',
        'file'    => ':attribute precisa ser maior ou igual a :value kilobytes.',
        'string'  => ':attribute precisa ser maior ou igual a :value caracteres.',
        'array'   => ':attribute precisa ter pelo menos :value itens.',
    ],

    'image'    => ':attribute precisa ser uma imagem.',
    'in'       => 'O :attribute selecionado é inválido.',
    'in_array' => ':attribute precisa existir em :other.',
    'integer'  => ':attribute precisa ser um número inteiro.',
    'ip'       => ':attribute precisa ser um endereço de IP válido.',
    'ipv4'     => ':attribute precisa ser um endereço IPv4 válido.',
    'ipv6'     => ':attribute precisa ser um endereço IPv6 válido.',
    'json'     => ':attribute precisa ser uma string JSON válida.',

    'lt' => [
        'numeric' => ':attribute precisa ser menor que :value.',
        'file'    => ':attribute precisa ser menor que :value kilobytes.',
        'string'  => ':attribute precisa ser menor que :value caracteres.',
        'array'   => ':attribute precisa ter menos de :value itens.',
    ],

    'lte' => [
        'numeric' => ':attribute precisa ser menor ou igual a :value.',
        'file'    => ':attribute precisa ser menor ou igual a :value kilobytes.',
        'string'  => ':attribute precisa ser menor ou igual a :value caracteres.',
        'array'   => ':attribute não pode ter mais que :value itens.',
    ],

    'max' => [
        'numeric' => ':attribute não pode ser maior que :max.',
        'file'    => ':attribute não pode ser maior que :max kilobytes.',
        'string'  => ':attribute não pode ter mais que :max caracteres.',
        'array'   => ':attribute não pode ter mais que :max itens.',
    ],

    'mimes'     => ':attribute precisa ser um arquivo do tipo: :values.',
    'mimetypes' => ':attribute precisa ser um arquivo do tipo: :values.',
    
    'min' => [
        'numeric' => ':attribute precisa ser no mínimo :min.',
        'file'    => ':attribute precisa ter no mínimo :min kilobytes.',
        'string'  => ':attribute precisa ter no mínimo :min caracteres.',
        'array'   => ':attribute precisa ter no mínimo :min itens.',
    ],

    'not_in'               => 'O :attribute selecionado é inválido.',
    'not_regex'            => 'O formato de :attribute é inválido.',
    'numeric'              => ':attribute precisa ser um número.',
    'present'              => 'O campo :attribute precisa estar presente.',
    'regex'                => 'O formato de :attribute é inválido.',
    'required'             => 'O campo :attribute é obrigatório.',
    'required_if'          => 'O campo :attribute é obrigatório quando :other for :value.',
    'required_unless'      => 'O campo :attribute é obrigatório a menos que :other seja :values.',
    'required_with'        => 'O campo :attribute é obrigatório quando :values estiver presente.',
    'required_with_all'    => 'O campo :attribute é obrigatório quando :values estiverem presentes.',
    'required_without'     => 'O campo :attribute é obrigatório quando :values não estiver presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum de :values estiver presente.',
    'same'                 => ':attribute e :other precisam ser iguais.',

    'size' => [
        'numeric' => ':attribute precisa ser :size.',
        'file'    => ':attribute precisa ter :size kilobytes.',
        'string'  => ':attribute precisa ter :size caracteres.',
        'array'   => ':attribute precisa ter :size itens.',
    ],

    'string'   => ':attribute precisa ser uma string.',
    'timezone' => ':attribute precisa ser uma zona horária válida.',
    'unique'   => ':attribute já foi tomado.',
    'uploaded' => ':attribute falhou ao ser carregado.',
    'url'      => 'O formato de :attribute é inválido.',

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
            'rule-name' => 'mensagem personalizada',
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
