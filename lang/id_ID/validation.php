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
    'accepted'             => ':attribute harus diterima.',
    'active_url'           => ':attribute bukan URL yang valid.',
    'after'                => ':attribute harus berupa tanggal setelah :date.',
    'after_or_equal'       => ':attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa array.',
    'before'               => ':attribute harus berupa tanggal sebelum :date.',
    'before_or_equal'      => ':attribute harus berupa tanggal sebelum atau sama dengan :date.',

    'between'              => [
        'numeric' => ':attribute harus berada di antara :min dan :max.',
        'file'    => ':attribute :harus berada di antara kilobyte :min dan :max.',
        'string'  => ':attribute :harus berada di antara karakter :min dan :max.',
        'array'   => ':attribute harus memiliki antara :min dan :max item.',
    ],

    'boolean'              => 'Kolom :attribute harus benar atau salah.',
    'confirmed'            => 'Konfirmasi :attribute tidak cocok.',
    'date'                 => ':attribute bukan tanggal yang valid.',
    'date_format'          => ':attribute tidak cocok dengan format :format.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus berupa :digits digit.',
    'digits_between'       => ':attribute harus berada di antara angka :min dan :max.',
    'dimensions'           => 'Atribut :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct'             => 'Bidang :attribute memiliki nilai duplikat.',
    'email'                => ':attribute harus berupa alamat email yang valid.',
    'exists'               => ':attribute yang dipilih tidak valid.',
    'exists-value'         => ':input tidak ada.',
    'extensions'           => 'Field :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file'                 => ':attribute harus berupa file.',
    'filled'               => 'Bidang :attribute harus memiliki nilai.',

    'gt'                   => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'file'    => ':attribute harus lebih besar dari :value kilobyte.',
        'string'  => ':attribute harus lebih besar dari :value karakter.',
        'array'   => ':attribute harus memiliki lebih dari :value item.',
    ],

    'gte'                  => [
        'numeric' => ':attribute harus lebih besar atau sama dengan :value.',
        'file'    => ':attribute harus lebih besar atau sama dengan :value kilobyte.',
        'string'  => ':attribute harus lebih besar atau sama dengan :value karakter.',
        'array'   => ':attribute harus memiliki item :value atau lebih.',
    ],

    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak valid.',
    'in_array'             => 'Bidang :attribute tidak ada di :other.',
    'integer'              => ':attribute harus berupa bilangan bulat.',
    'ip'                   => ':attribute harus berupa alamat IP yang valid.',
    'ipv4'                 => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6'                 => ':attribute harus berupa alamat IPv6 yang valid.',
    'json'                 => ':attribute harus berupa string JSON yang valid.',

    'lt'                   => [
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'file'    => ':attribute harus kurang dari :value kilobyte.',
        'string'  => ':attribute harus kurang dari :value karakter.',
        'array'   => ':attribute harus memiliki item yang kurang dari :value.',
    ],

    'lte'                  => [
        'numeric' => ':attribute harus lebih kecil atau sama dengan :value.',
        'file'    => ':attribute harus kurang dari atau sama dengan :value kilobyte.',
        'string'  => ':attribute harus kurang dari atau sama dengan :value karakter.',
        'array'   => ':attribute tidak boleh memiliki lebih dari :value item.',
    ],

    'max'                  => [
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'file'    => ':attribute tidak boleh lebih besar dari :max kilobyte.',
        'string'  => ':attribute tidak boleh lebih besar dari :max karakter.',
        'array'   => ':attribute tidak boleh memiliki lebih dari :max item.',
    ],

    'mimes'                => ':attribute harus berupa file dengan tipe: :values.',
    'mimetypes'            => ':attribute harus berupa file dengan tipe: :values.',

    'min'                  => [
        'numeric' => ':attribute minimal harus :min.',
        'file'    => ':attribute minimal harus :min kilobyte.',
        'string'  => ':attribute setidaknya harus terdiri dari :min karakter.',
        'array'   => ':attribute harus memiliki setidaknya :min item.',
    ],

    'not_in'               => ':attribute yang dipilih tidak valid.',
    'not_regex'            => 'Format :attribute tidak valid.',
    'numeric'              => ':attribute harus berupa angka.',
    'present'              => 'Bidang :attribute harus ada.',
    'regex'                => 'Format :attribute tidak valid.',
    'required'             => 'Bidang :attribute wajib diisi.',
    'required_if'          => 'Bidang :attribute diperlukan bila :other adalah :value.',
    'required_unless'      => 'Bidang :attribute wajib diisi kecuali :other ada dalam :values.',
    'required_with'        => 'Bidang :attribute diperlukan bila :values ​​ada.',
    'required_with_all'    => 'Bidang :attribute diperlukan bila :values ​​ada.',
    'required_without'     => 'Bidang :attribute diperlukan bila :values ​​tidak ada.',
    'required_without_all' => 'Bidang :attribute diperlukan bila tidak ada :values ​​yang ada.',
    'same'                 => ':attribute dan :other harus cocok.',

    'size'                 => [
        'numeric' => ':attribute harus :size.',
        'file'    => ':attribute harus :size kilobyte.',
        'string'  => ':attribute harus berupa karakter :size.',
        'array'   => ':attribute harus berisi :size item.',
    ],

    'string'               => ':attribute harus berupa string.',
    'timezone'             => ':attribute harus berupa zona yang valid.',
    'unique'               => ':attribute telah diambil.',
    'uploaded'             => ':attribute gagal diunggah.',
    'url'                  => 'Format :attribute tidak valid.',
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
            'rule-name' => 'pesan khusus',
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
