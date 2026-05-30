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
        'category-fields' => [
            'title'      => 'Ангиллын талбарууд',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Ангиллын талбарын код :code аль хэдийн ашиглагдаж байна.',
                    'code_not_found_to_delete' => 'Устгах ангиллын талбарын код олдсонгүй.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Шинж чанарууд',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Шиж чанарын код :code аль хэдийн ашиглагдаж байна.',
                    'code_not_found_to_delete'             => 'Устгах шинж чанарын код олдсонгүй.',
                    'code_is_system_and_cannot_be_deleted' => 'Системийн шинж чанарыг устгах боломжгүй.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Шинж чанарын бүлгүүд',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Шиж чанарын бүлгийн код :code аль хэдийн ашиглагдаж байна.',
                    'code_not_found_to_delete'             => 'Устгах шинж чанарын бүлгийн код олдсонгүй.',
                    'code_is_system_and_cannot_be_deleted' => 'Системийн шинж чанарын бүлгийг устгах боломжгүй.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Шинж чанарын бүлгэмүүд',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Шиж чанарын бүлгэмийн код :code аль хэдийн ашиглагдаж байна.',
                    'code_not_found_to_delete' => 'Устгах шинж чанарын бүлгэмийн код олдсонгүй.',
                    'invalid-attribute-group'  => '":code" шинж чанарын бүлэг байхгүй байна.',
                    'invalid-attribute'        => '":code" шинж чанар байхгүй байна.',
                    'invalid-channel'          => '":code" суваг байхгүй байна.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Шинж чанарын сонголтууд',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Шиж чанарын сонголтын код :code аль хэдийн ашиглагдаж байна.',
                    'code_not_found_to_delete' => 'Устгах шинж чанарын сонголтын код олдсонгүй.',
                    'locale-not-exist'         => '":code" хэл байхгүй байна.',
                    'invalid-attribute'        => '":code" шинж чанар байхгүй байна.',
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
        'products' => [
            'title'      => 'Бүтээгдэхүүн',
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
        'category-fields' => [
            'title' => 'Ангиллын талбарууд',
        ],
        'attributes' => [
            'title' => 'Шинж чанарууд',
        ],
        'attribute-groups' => [
            'title' => 'Шинж чанарын бүлгүүд',
        ],
        'attribute-families' => [
            'title' => 'Шинж чанарын бүлгэмүүд',
        ],
        'attribute-options' => [
            'title' => 'Шинж чанарын сонголтууд',
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
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Ажлын гүйцэтгэл эхэлсэн',
        'completed' => 'Ажлын гүйцэтгэл дууссан',
    ],
];
