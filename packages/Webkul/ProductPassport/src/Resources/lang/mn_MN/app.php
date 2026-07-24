<?php

return [
    'type' => [
        'label' => 'Дижитал бүтээгдэхүүний паспорт',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Бүтээгдэхүүний паспорт',
            'info'     => 'Дижитал бүтээгдэхүүний паспортыг нийтлэх тохиргоо.',
            'settings' => [
                'title'                              => 'Бүтээгдэхүүний паспортын тохиргоо',
                'enabled'                            => 'Идэвхжсэн',
                'auto-publish'                       => 'Хадгалахад автоматаар нийтлэх',
                'completeness-threshold'             => 'Бүрэн байдлын босго (%)',
                'operator-name'                      => 'Эдийн засгийн оператерийн нэр',
                'operator-address'                   => 'Эдийн засгийн оператерийн хаяг',
                'operator-eu-rep'                    => 'ЕХ-ны эрх бүхий төлөөлөгч',
                'support-url'                        => 'Дэмжлэгийн URL',
                'enabled-hint'                       => 'Энэ каталогийн хувьд Дижитал Бүтээгдэхүүний Паспорт функцийг асаана. Унтраалттай үед паспортын самбар болон хүснэгт нуугдана.',
                'auto-publish-hint'                  => 'Бүтээгдэхүүнийг хадгалж, бүрэн байдлын босгыг хангасан үед паспортын хувилбарыг автоматаар нийтэлнэ. Гараар нийтлэхийн тулд унтраалттай орхино уу.',
                'completeness-threshold-hint'        => 'Локалийн хувьд паспорт нийтлэхээс өмнө шаардагдах бүтээгдэхүүний хамгийн бага бүрэн байдал (хувиар).',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'ESPR журмын дагуу бүх нийтийн паспорт дээр харагдах үйлдвэрлэгч эсвэл хариуцлагатай эдийн засгийн операторын албан ёсны нэр.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Мөшгих боломжийн үүднээс нийтийн паспорт дээр харагдах эдийн засгийн операторын бүртгэлтэй шуудангийн хаяг.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Үйлдвэрлэгч нь ЕХ-ноос гадуур байгуулагдсан тохиолдолд шаардагдах ЕХ-ны эрх бүхий төлөөлөгчийн нэр, холбоо барих мэдээлэл.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Хэрэглэгчид тусламж эсвэл баталгааны мэдээлэл олох боломжтой нийтийн хуудас. Бүх паспорт дээр холбоос хэлбэрээр харагдана.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Дижитал бүтээгдэхүүний паспорт',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Материалын бүрэлдэхүүн',
        'dpp_substances_of_concern'     => 'Анхаарал татах бодис',
        'dpp_recycled_content_pct'      => 'Дахин боловсруулсан агуулга (%)',
        'dpp_carbon_footprint'          => 'Нүүрстөрөгчийн ул мөр',
        'dpp_energy_consumption'        => 'Эрчим хүчний хэрэглээ',
        'dpp_durability_statement'      => 'Удаан эдэлгээний тухай мэдэгдэл',
        'dpp_repairability_score'       => 'Засварлах чадварын оноо',
        'dpp_spare_parts_availability'  => 'Сэлбэг хэрэгслийн боломж',
        'dpp_care_instructions'         => 'Арчилгааны заавар',
        'dpp_disassembly_guide'         => 'Задлах заавар',
        'dpp_manufacturer_name'         => 'Үйлдвэрлэгчийн нэр',
        'dpp_manufacturing_site'        => 'Үйлдвэрлэсэн газар',
        'dpp_country_of_origin'         => 'Гарал үүслийн улс',
        'dpp_supply_chain_notes'        => 'Ханган нийлүүлэлтийн сүлжээний тэмдэглэл',
        'dpp_end_of_life_instructions'  => 'Ашиглалтын хугацаа дууссаны заавар',
        'dpp_take_back_scheme'          => 'Буцаан авах систем',
        'dpp_declaration_of_conformity' => 'Нийцлийн мэдэгдэл',
        'dpp_test_reports'              => 'Туршилтын тайлан',
        'dpp_certificates'              => 'Гэрчилгээ',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Загварын дугаар',
        'dpp_batch_identifier'          => 'Багцын дугаар',
        'dpp_warranty_terms'            => 'Баталгааны нөхцөл',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Дижитал бүтээгдэхүүний паспортын шинж чанарууд амжилттай суулгагдлаа.',
        ],
    ],

    'public' => [
        'badge'         => 'EU дижитал бүтээгдэхүүний паспорт',
        'search-locale' => 'Хайлтын хэл',
        'sections'      => [
            'passport' => 'Бүтээгдэхүүний паспорт',
        ],
        'title'      => 'Дижитал бүтээгдэхүүний паспорт',
        'identifier' => [
            'title'        => 'Таних мэдээлэл',
            'gtin'         => 'GTIN',
            'model'        => 'Загвар',
            'batch'        => 'Цуврал',
            'not-provided' => 'Өгөгдөөгүй',
        ],
        'operator' => [
            'title' => 'Эдийн засгийн оператер',
        ],
        'documents' => [
            'title' => 'Баримт бичиг',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'Паспорт нийтлэх одоогоор идэвхгүй байна. Одоо байгаа паспортуудыг удирдах (үзэх болон буцаах) зорилгоор доор харуулав.',
            'title'           => 'Дижитал бүтээгдэхүүний паспортууд',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Суваг',
            'status'          => 'Төлөв',
            'live-locales'    => 'Идэвхтэй хэлүүд',
            'last-published'  => 'Сүүлд нийтэлсэн',
            'withdraw'        => 'Татан авах',
        ],
        'publish-queued' => 'Паспортыг нийтлэх ажил дараалалд орлоо.',
        'withdrawn'      => 'Паспорт амжилттай татагдав.',
        'mass-publish'   => [
            'action' => 'Дижитал бүтээгдэхүүний паспортыг нийтлэх',
            'queued' => ':count бүтээгдэхүүний паспорт нийтлэх ажил дараалалд орлоо.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Паспортууд',
            'view'     => 'Харах',
            'publish'  => 'Нийтлэх',
            'withdraw' => 'Татан авах',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Паспортууд',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Нийтэлж байна…',
                    'queued'              => 'Дараалалд',
                    'title'               => 'Дижитал бүтээгдэхүүний паспорт',
                    'publishing-disabled' => 'Энэ сувагт паспорт нийтлэх боломжгүй болгосон байна.',
                    'locale'              => 'Хэл',
                    'version'             => 'Хувилбар',
                    'published-at'        => 'Нийтэлсэн огноо',
                    'missing-fields'      => 'Дутуу талбарууд',
                    'not-published'       => 'Нийтлэгдээгүй',
                    'unscored'            => 'Үнэлэгдээгүй',
                    'publish'             => 'Нийтлэх',
                    'republish'           => 'Дахин нийтлэх',
                    'publish-all'         => 'Бүх хэлийг нийтлэх',
                    'auto-publish-on'     => 'Автомат нийтлэл асаалттай байна — бүтээгдэхүүнийг хадгалж, бүрэн бүтэн байдлын босгыг хангасан үед паспорт автоматаар нийтлэгдэнэ. Одоо нийтлэхийн тулд товчлууруудыг ашиглана уу.',
                    'auto-publish-off'    => 'Гар аргаар нийтлэх — энэ бүтээгдэхүүний паспортыг хэл тус бүрээр нийтлэхийн тулд товчлууруудыг ашиглана уу.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute нь хүчинтэй GTIN байх ёстой (зөв шалгах оронтой 8, 12, 13 эсвэл 14 орон).',
    ],
];
