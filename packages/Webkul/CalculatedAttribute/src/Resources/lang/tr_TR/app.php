<?php

return [

    'attribute' => [
        'calculated' => 'Hesaplanan',
    ],

    'formula' => [
        'title'            => 'Formül',
        'label'            => 'Formül',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Değer, ürün her gösterildiğinde, dışa aktarıldığında veya API tarafından döndürüldüğünde bu formülden türetilir. Hiçbir zaman saklanmaz ve içe aktarılamaz.',
        'reference-hint'   => 'Diğer özniteliklere kodlarıyla başvurun.',
        'available'        => 'Kullanılabilir fonksiyonlar',
        'examples'         => 'Örnekler',
        'example-concat'   => 'İki özniteliği birleştir, boş olanları atla',
        'example-category' => '# ile birleştirilmiş kategori etiketi yolu',
        'example-fallback' => 'İlki boşsa ikinci bir özniteliğe geri dön',
        'read-only'        => 'Hesaplanan',
        'read-only-hint'   => 'Bu değer diğer özniteliklerden hesaplanır ve düzenlenemez.',

        'variables' => [
            'title'      => 'Kullanılabilir değişkenler',
            'sku'        => 'ürünün stok kodu',
            'type'       => 'ürün tipi',
            'status'     => 'ürün durumu',
            'family'     => 'öznitelik ailesinin kodu',
            'parent'     => 'varyantlar için üst ürünün stok kodu',
            'categories' => 'atanmış kategori kodları',
            'channel'    => 'oluşturulan kanal',
            'locale'     => 'oluşturulan dil',
        ],

        'errors' => [
            'syntax'              => 'Formül çözümlenemedi: :detail',
            'unknown-variable'    => 'Formülde bilinmeyen öznitelik veya değişken ":name".',
            'disallowed-function' => '":name" fonksiyonuna bir formülde izin verilmez.',
            'disallowed-operator' => '":name" operatörüne bir formülde izin verilmez.',
            'reserved-variable'   => '":name" adı ayrılmıştır ve bir formülde kullanılamaz.',
            'too-long'            => 'Formül :max karakterden uzun olamaz.',
            'empty'               => 'Hesaplanan bir öznitelik için formül gereklidir.',
            'circular'            => 'Formül döngüsel bir bağımlılık oluşturuyor: :cycle',
            'referenced-by'       => 'Bu öznitelik silinemez, çünkü şunların formülünde kullanılıyor: :attributes',
        ],
    ],

];
