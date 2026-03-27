<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Бүрэн байдал',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Бүрэн байдал амжилттай шинэчлэгдлээ',
                    'title'               => 'Бүрэн байдал',
                    'configure'           => 'Бүрэн байдлыг тохируулах',
                    'channel-required'    => 'Сувгуудад шаардлагатай',
                    'save-btn'            => 'Хадгалах',
                    'back-btn'            => 'Буцах',
                    'mass-update-success' => 'Бүрэн байдал амжилттай шинэчлэгдлээ',
                    'datagrid'            => [
                        'code'             => 'Код',
                        'name'             => 'Нэр',
                        'channel-required' => 'Сувгуудад шаардлагатай',
                        'actions'          => [
                            'change-requirement' => 'Бүрэн байдлын шаардлагыг өөрчлөх',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Байхгүй',
                    'completeness'                 => 'Бүрэн',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Бүрэн байдал',
                    'subtitle' => 'Дундаж бүрэн байдал',
                ],
                'required-attributes' => 'шаардлагатай шинж чанарууд дутуу байна',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Бүрэн байдлын тооцоо дууслаа',
        'completeness-calculated'        => ':count бүтээгдэхүүний бүрэн байдал тооцоологдлоо.',
        'completeness-calculated-family' => '":family" гэр бүлийн :count бүтээгдэхүүний бүрэн байдал тооцоологдлоо.',
        'email-subject'                  => 'Бүрэн байдлын тооцоо дууслаа',
        'email-greeting'                 => 'Сайн байна уу,',
        'email-body'                     => ':count бүтээгдэхүүний бүрэн байдлын тооцоо дууслаа.',
        'email-body-family'              => '":family" шинж чанарын гэр бүлийн :count бүтээгдэхүүний бүрэн байдлын тооцоо дууслаа.',
        'email-footer'                   => 'Та хяналтын самбараас бүрэн байдлын дэлгэрэнгүйг харах боломжтой.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Тооцоолсон бүтээгдэхүүнүүд',
                'suggestion'          => [
                    'low'     => 'Бүрэн байдал бага, сайжруулахын тулд дэлгэрэнгүй мэдээлэл нэмнэ үү.',
                    'medium'  => 'Үргэлжлүүлээрэй, мэдээлэл нэмсээр байгаарай.',
                    'high'    => 'Бараг бүрэн, зөвхөн хэдэн дэлгэрэнгүй мэдээлэл үлдлээ.',
                    'perfect' => 'Бүтээгдэхүүний мэдээлэл бүрэн гүйцэд байна.',
                ],
            ],
        ],
    ],
];
