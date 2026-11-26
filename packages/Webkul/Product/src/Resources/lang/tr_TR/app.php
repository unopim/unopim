<?php

return [
    'acl' => [
        'variant-structures' => 'Varyant Yapıları',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopyası-:value',
        'copy-of'                       => ':value\'in kopyası',
        'variant-already-exist-message' => 'Aynı özellik seçeneklerine sahip varyant zaten mevcut.',
    ],

    'response' => [
        'product-can-not-be-copied' => ':type türündeki ürünler kopyalanamaz',
    ],

    'type'     => [
        'configurable'  => 'Ayarlanabilir',
        'simple'        => 'Basit',
        'variant-group' => 'Varyant Grubu',
        'variant_group' => 'Varyant Grubu',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Döngüsel şablon referansı algılandı: :path.',
            'currency-not-allowed'    => 'Yalnızca fiyat öznitelikleri para birimi kabul eder, ancak ":attribute" bir fiyat özniteliği değildir.',
            'currency-not-enabled'    => 'Şablonda kullanılan ":currency" para birimi hiçbir kanal için etkin değil.',
            'currency-not-in-channel' => '":currency" para birimi ":channel" kanalı için etkin değil.',
            'filter-failed'           => '":filter" filtresi ":attribute" değerine uygulanamadı.',
            'filter-not-supported'    => '":filter" filtresi ":attribute" özniteliğine uygulanamaz.',
            'format-date-position'    => 'format_date filtresi ":expression" içindeki ilk filtre olmalıdır.',
            'inside-html-tag'         => 'Şablon ifadeleri, bağlantılar ve görseller gibi HTML etiketlerinin veya özniteliklerinin içine yerleştirilemez.',
            'invalid-filter-argument' => '":expression" içindeki ":filter" filtresinin argümanı eksik veya geçersiz.',
            'invalid-reference'       => '":attribute" içinde saklanan şablon geçersiz ve oluşturulamadı.',
            'invalid-syntax'          => '":expression" şablon ifadesi geçersiz. Bir öznitelik kodu ve ardından isteğe bağlı filtreler kullanın, örneğin: name | upper.',
            'max-depth'               => 'Şablon referansları ":attribute" konumunda :max seviyeden daha derin iç içe geçmiş.',
            'missing-value'           => '":attribute" özniteliğinin bu kanal ve yerel ayar için bir değeri yok.',
            'not-in-family'           => 'Şablonda kullanılan ":attribute" özniteliği ürün ailesine ait değil.',
            'output-too-large'        => 'İşlenen şablon :max bayttan büyük olduğu için kesildi.',
            'self-reference'          => 'Şablon, kendi özniteliği olan ":attribute" özniteliğine referans veremez.',
            'single-brace'            => 'Tek süslü parantez içinde ":text" bulundu. Şablon ifadeleri çift süslü parantez gerektirir.',
            'source-too-long'         => 'Bir şablon en fazla :max bayt uzunluğunda olabilir.',
            'too-complex'             => 'Şablon kontrol edilemeyecek kadar karmaşık. Kısaltın veya basitleştirin.',
            'too-many-expressions'    => 'Bir şablon en fazla :max ifade içerebilir.',
            'too-many-filters'        => '":expression" şablon ifadesi en fazla :max filtre kullanabilir.',
            'unclosed-expression'     => 'Bir şablon ifadesi açıldı ancak hiç kapatılmadı.',
            'unknown-attribute'       => 'Şablonda kullanılan ":attribute" özniteliği mevcut değil.',
            'unknown-currency'        => 'Şablonda kullanılan ":currency" para birimi mevcut değil.',
            'unknown-filter'          => '":filter" filtresi desteklenmiyor. Kullanılabilir filtreler: :filters.',
            'unsupported-attribute'   => '":attribute" özniteliği, türü desteklenmediği için şablonda kullanılamaz.',
        ],
    ],
];
