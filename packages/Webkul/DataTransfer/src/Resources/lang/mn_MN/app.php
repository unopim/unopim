<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Бүтээгдэхүүн',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL түлхүүр: \'%s\' SKU-тай зүйлд аль хэдийн үүсгэгдсэн: \'%s\'.',
                    'invalid-attribute-family'                 => 'Атрибутын гэр бүлийн баганын утга буруу байна (атрибутын гэр бүл байхгүй юу?)',
                    'invalid-type'                             => 'Бүтээгдэхүүний төрөл буруу эсвэл дэмжигдээгүй байна',
                    'sku-not-found'                            => 'Заасан SKU-тай бүтээгдэхүүн олдсонгүй',
                    'super-attribute-not-found'                => 'Кодтой тохируулах боломжтой атрибут :code олдоогүй эсвэл атрибутын гэр бүлд хамаарахгүй :familyCode',
                    'configurable-attributes-not-found'        => 'Бүтээгдэхүүний загварыг бий болгохын тулд тохируулж болох шинж чанарууд шаардлагатай',
                    'configurable-attributes-wrong-type'       => 'Зөвхөн локал болон сувагт тулгуурлаагүй сонгосон төрлийн шинж чанаруудыг тохируулж болох бүтээгдэхүүний тохируулж болох шинж чанаруудыг зөвшөөрдөг.',
                    'variant-configurable-attribute-not-found' => 'Хувилбарыг тохируулах боломжтой шинж чанар :code үүсгэхэд шаардлагатай',
                    'not-unique-variant-product'               => 'Тохируулж болох шинж чанаруудтай бүтээгдэхүүн аль хэдийн байна.',
                    'channel-not-exist'                        => 'Энэ суваг байхгүй байна.',
                    'locale-not-in-channel'                    => 'Суваг дээр энэ хэлийг сонгоогүй байна.',
                    'locale-not-exist'                         => 'Энэ локал байхгүй байна',
                    'not-unique-value'                         => ':code утга нь өвөрмөц байх ёстой.',
                    'incorrect-family-for-variant'             => 'Гэр бүл нь эцэг эхтэй ижил байх ёстой',
                    'parent-not-exist'                         => 'Эцэг эх нь байхгүй.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Ангилал',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Та сувагтай холбоотой үндсэн категорийг устгах боломжгүй',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Хэлүүд',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Хэлний код \'%s\' энэ багцад аль хэдийн импортлогдсон байна.',
                    'code-not-found-to-delete'    => 'Код \'%s\' бүхий хэл системд олдсонгүй.',
                    'invalid-status'              => 'Төлөв 0 эсвэл 1 байх ёстой (эсвэл анхдагчаар идэвхжүүлэхийн тулд хоосон).',
                    'channel-related-locale-root' => 'Та :code кодтой хэлийг устгах боломжгүй, учир нь энэ нь сувагтай холбоотой.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Сувгууд',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => ' :code кодтой суваг устгахад олдсонгүй.',
                    'locale-not-found'         => 'Нэг эсвэл хэд хэдэн хэл байхгүй байна.',
                    'root-category-not-found'  => 'Үндсэн ангилал байхгүй байна.',
                    'currency-not-found'       => 'Нэг эсвэл хэд хэдэн валют байхгүй байна.',
                    'invalid-locale'           => 'Хэл байхгүй байна.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'Энэ экспорт хэт том тул ажиллуулах боломжгүй: ойролцоогоор :rows мөр × :columns багана (~:estimated) нь боломжтой зайнаас (~:available) хэтэрсэн байна. Цөөн суваг/хэл (болон шинж чанар) сонгож экспортыг багасгаад дахин оролдоно уу.',
        'fields'           => [
            'file-format'         => 'Файлын формат',
            'with-media'          => 'Медиатай',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Төлөв',
            'enable'         => 'Идэвхтэй',
            'all'            => 'Бүгд',
        ],
        'products' => [
            'title'              => 'Бүтээгдэхүүн',
            'invalid-locales'    => 'Сонгосон бүх локаль сонгосон сувгуудад боломжтой биш байна.',
            'invalid-currencies' => 'Сонгосон бүх валют сонгосон сувгуудад боломжтой биш байна.',
            'filters'            => [
                'channels'             => 'Сувгууд',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Валют',
                'currencies-info'      => 'Үнийн шинж чанаруудыг сонгосон валют бүрээр экспортолно. Сувгийн бүх валютыг экспортлохын тулд хоосон үлдээнэ үү.',
                'locales'              => 'Локаль',
                'locales-info'         => 'Нутагшуулж болох шинж чанаруудыг сонгосон локаль бүрт нэг удаа экспортолно. Сувгийн бүх локалийг экспортлохын тулд хоосон үлдээнэ үү.',
                'attributes'           => 'Шинж чанарууд',
                'attributes-info'      => 'Зөвхөн сонгосон шинж чанаруудыг экспортолно. Бүлгийн бүх шинж чанарыг экспортлохын тулд хоосон үлдээнэ үү.',
                'attribute-families'   => 'Шинж чанарын бүлэг',
                'categories'           => 'Ангиллууд',
                'completeness'         => 'Бүрэн байдал',
                'completeness-options' => [
                    'none'         => 'Бүрэн байдлын нөхцөл байхгүй',
                    'at-least-one' => 'Сонгосон дор хаяж нэг локальд бүрэн',
                    'all'          => 'Сонгосон бүх локальд бүрэн',
                ],
                'time-condition' => 'Цагийн нөхцөл',
                'time-options'   => [
                    'none'              => 'Огнооны нөхцөл байхгүй',
                    'last-n-days'       => 'Сүүлийн N хоногт шинэчлэгдсэн бүтээгдэхүүн',
                    'between-dates'     => 'Хоёр огнооны хооронд шинэчлэгдсэн бүтээгдэхүүн',
                    'since-last-export' => 'Сүүлийн экспортоос хойш шинэчлэгдсэн бүтээгдэхүүн',
                ],
                'time-value'     => 'Хоногийн тоо',
                'time-date'      => 'Эхлэх огноо',
                'time-date-end'  => 'Дуусах огноо',
                'status'         => 'Төлөв',
                'status-options' => [
                    'enable'  => 'Идэвхтэй',
                    'disable' => 'Идэвхгүй',
                    'all'     => 'Бүгд',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Танигчид',
                'identifiers-info' => 'Зөвхөн тэдгээр бүтээгдэхүүнийг экспортлохын тулд мөр бүрт нэг SKU / танигч буулгана уу. Бүх бүтээгдэхүүнийг экспортлохын тулд хоосон үлдээнэ үү.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL түлхүүр: \'%s\' SKU-тай зүйлд аль хэдийн үүсгэгдсэн: \'%s\'.',
                    'invalid-attribute-family'  => 'Атрибутын гэр бүлийн баганын утга буруу байна (атрибутын гэр бүл байхгүй юу?)',
                    'invalid-type'              => 'Бүтээгдэхүүний төрөл буруу эсвэл дэмжигдээгүй байна',
                    'sku-not-found'             => 'Заасан SKU-тай бүтээгдэхүүн олдсонгүй',
                    'super-attribute-not-found' => 'Кодтой супер атрибут: \'%s\' олдсонгүй эсвэл атрибутын бүлэгт хамаарахгүй: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Ангилал',
        ],
        'locales' => [
            'title' => 'Хэлүүд',
        ],
        'channels' => [
            'title' => 'Сувгууд',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Төлөв',
                'active' => 'Active',
                'all'    => 'Бүгд',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => '"%s" дугаартай баганын толгой хоосон байна.',
            'column-name-invalid'  => 'Баганын нэр буруу: "%s".',
            'column-not-found'     => 'Шаардлагатай баганууд олдсонгүй: %s.',
            'column-numbers'       => 'Баганын тоо толгойн мөрийн тоотой тохирохгүй байна.',
            'invalid-attribute'    => 'Толгой хэсэгт хүчингүй атрибут(ууд) агуулагдаж байна: "%s".',
            'system'               => 'Гэнэтийн системийн алдаа гарлаа.',
            'wrong-quotes'         => 'Шулуун ишлэлийн оронд буржгар ишлэл ашигласан.',
            'file-empty'           => 'Файл хоосон эсвэл толгой мөр агуулаагүй байна. Өгөгдөл бүхий зөв файл байршуулна уу.',
        ],
    ],
    'job' => [
        'started'   => 'Ажлын гүйцэтгэл эхэлсэн',
        'completed' => 'Ажлын гүйцэтгэл дууссан',
    ],
];
