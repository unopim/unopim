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
    'accepted'        => ':attribute ay kailangang tanggapin.',
    'active_url'      => ':attribute ay hindi isang valid na URL.',
    'after'           => ':attribute ay kailangang isang petsa pagkatapos ng :date.',
    'after_or_equal'  => ':attribute ay kailangang isang petsa pagkatapos o katumbas ng :date.',
    'alpha'           => ':attribute ay maaaring maglaman lamang ng mga letra.',
    'alpha_dash'      => ':attribute ay maaaring maglaman lamang ng mga letra, numero, gitling, at underscore.',
    'alpha_num'       => ':attribute ay maaaring maglaman lamang ng mga letra at numero.',
    'array'           => ':attribute ay kailangang isang array.',
    'before'          => ':attribute ay kailangang isang petsa bago ang :date.',
    'before_or_equal' => ':attribute ay kailangang isang petsa bago o katumbas ng :date.',

    'between' => [
        'numeric' => ':attribute ay kailangang nasa pagitan ng :min at :max.',
        'file'    => ':attribute ay kailangang nasa pagitan ng :min at :max kilobytes.',
        'string'  => ':attribute ay kailangang nasa pagitan ng :min at :max na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng pagitan ng :min at :max na item.',
    ],

    'boolean'        => ':attribute ay kailangang tama o mali.',
    'confirmed'      => 'Ang pagkumpirma ng :attribute ay hindi tumutugma.',
    'date'           => ':attribute ay hindi isang valid na petsa.',
    'date_format'    => ':attribute ay hindi tumutugma sa format na :format.',
    'different'      => ':attribute at :other ay kailangang magkaiba.',
    'digits'         => ':attribute ay kailangang magkaroon ng :digits na digits.',
    'digits_between' => ':attribute ay kailangang magkaroon ng pagitan ng :min at :max na digits.',
    'dimensions'     => 'Ang dimensyon ng imahe para sa :attribute ay hindi valid.',
    'distinct'       => ':attribute ay may dobleng halaga.',
    'email'          => ':attribute ay kailangang isang valid na email address.',
    'exists'         => 'Ang napiling :attribute ay hindi valid.',
    'extensions'     => 'Ang :attribute na field ay dapat may isa sa mga sumusunod na extension: :values.',
    'file'           => ':attribute ay kailangang isang file.',
    'filled'         => ':attribute ay kailangang mayroong halaga.',

    'gt' => [
        'numeric' => ':attribute ay kailangang mas malaki kaysa sa :value.',
        'file'    => ':attribute ay kailangang mas malaki kaysa sa :value kilobytes.',
        'string'  => ':attribute ay kailangang mas mahaba kaysa sa :value na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng higit sa :value na item.',
    ],

    'gte' => [
        'numeric' => ':attribute ay kailangang mas malaki o katumbas ng :value.',
        'file'    => ':attribute ay kailangang mas malaki o katumbas ng :value kilobytes.',
        'string'  => ':attribute ay kailangang mas mahaba o katumbas ng :value na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng hindi bababa sa :value na item.',
    ],

    'image'    => ':attribute ay kailangang isang imahe.',
    'in'       => 'Ang napiling :attribute ay hindi valid.',
    'in_array' => 'Ang :attribute ay hindi umiiral sa :other.',
    'integer'  => ':attribute ay kailangang isang integer.',
    'ip'       => ':attribute ay kailangang isang valid na IP address.',
    'ipv4'     => ':attribute ay kailangang isang valid na IPv4 address.',
    'ipv6'     => ':attribute ay kailangang isang valid na IPv6 address.',
    'json'     => ':attribute ay kailangang isang valid na JSON string.',

    'lt' => [
        'numeric' => ':attribute ay kailangang mas maliit kaysa sa :value.',
        'file'    => ':attribute ay kailangang mas maliit kaysa sa :value kilobytes.',
        'string'  => ':attribute ay kailangang mas maliit kaysa sa :value na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng mas kaunting item kaysa sa :value.',
    ],

    'lte' => [
        'numeric' => ':attribute ay kailangang mas maliit o katumbas ng :value.',
        'file'    => ':attribute ay kailangang mas maliit o katumbas ng :value kilobytes.',
        'string'  => ':attribute ay kailangang mas maliit o katumbas ng :value na karakter.',
        'array'   => ':attribute ay hindi maaaring magkaroon ng higit sa :value na item.',
    ],

    'max' => [
        'numeric' => ':attribute ay hindi maaaring mas malaki kaysa sa :max.',
        'file'    => ':attribute ay hindi maaaring mas malaki kaysa sa :max kilobytes.',
        'string'  => ':attribute ay hindi maaaring mas mahaba kaysa sa :max na karakter.',
        'array'   => ':attribute ay hindi maaaring magkaroon ng higit sa :max na item.',
    ],

    'mimes'     => ':attribute ay kailangang isang file na may uri: :values.',
    'mimetypes' => ':attribute ay kailangang isang file na may uri: :values.',

    'min' => [
        'numeric' => ':attribute ay kailangang hindi bababa sa :min.',
        'file'    => ':attribute ay kailangang hindi bababa sa :min kilobytes.',
        'string'  => ':attribute ay kailangang hindi bababa sa :min na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng hindi bababa sa :min na item.',
    ],

    'not_in'               => 'Ang napiling :attribute ay hindi valid.',
    'not_regex'            => 'Ang format ng :attribute ay hindi valid.',
    'numeric'              => ':attribute ay kailangang isang numero.',
    'present'              => 'Ang :attribute field ay kailangang naroroon.',
    'regex'                => 'Ang format ng :attribute ay hindi valid.',
    'required'             => 'Ang :attribute field ay kinakailangan.',
    'required_if'          => 'Ang :attribute field ay kinakailangan kapag ang :other ay :value.',
    'required_unless'      => 'Ang :attribute field ay kinakailangan maliban kung ang :other ay :values.',
    'required_with'        => 'Ang :attribute field ay kinakailangan kapag ang :values ay naroroon.',
    'required_with_all'    => 'Ang :attribute field ay kinakailangan kapag ang :values ay naroroon.',
    'required_without'     => 'Ang :attribute field ay kinakailangan kapag ang :values ay wala.',
    'required_without_all' => 'Ang :attribute field ay kinakailangan kapag ang :values ay wala.',
    'same'                 => 'Ang :attribute at :other ay kailangang magkatulad.',

    'size' => [
        'numeric' => ':attribute ay kailangang :size.',
        'file'    => ':attribute ay kailangang :size kilobytes.',
        'string'  => ':attribute ay kailangang :size na karakter.',
        'array'   => ':attribute ay kailangang magkaroon ng :size na item.',
    ],

    'string'   => ':attribute ay kailangang isang string.',
    'timezone' => ':attribute ay kailangang isang valid na timezone.',
    'unique'   => ':attribute ay ginagamit na.',
    'uploaded' => ':attribute ay nabigong ma-upload.',
    'url'      => 'Ang format ng :attribute ay hindi valid.',

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
            'rule-name' => 'pasadyang mensahe',
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
