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
                    'channel-required'    => 'Сувагт шаардлагатай',
                    'save-btn'            => 'Хадгалах',
                    'back-btn'            => 'Буцах',
                    'mass-update-success' => 'Бүрэн байдал амжилттай шинэчлэгдлээ',

                    'datagrid' => [
                        'code'             => 'Код',
                        'name'             => 'Нэр',
                        'channel-required' => 'Сувагт шаардлагатай',

                        'actions' => [
                            'change-requirement' => 'Бүрэн байдлын шаардлагыг өөрчлөх',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Тохиргоо байхгүй байна',
                    'completeness'                 => 'Бүрэн',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Бүрэн байдал',
                    'subtitle' => 'Дундаж бүрэн байдал',
                ],

                'required-attributes' => 'дахин харах шаардлагатай заавал шаардлагатай шинж чанарууд',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Тооцоолсон бүтээгдэхүүнүүд',

                'suggestion' => [
                    'low'     => 'Бүрэн байдал нь бага байна — сайжруулахын тулд дэлгэрэнгүй мэдээлэл оруулна уу.',
                    'medium'  => 'Үргэлжлүүлэн мэдээлэл оруулсаар байгаарай.',
                    'high'    => 'Бараг төгс — цөөн хэдэн дэлгэрэнгүй мэдээлэл үлджээ.',
                    'perfect' => 'Бүтээгдэхүүний мэдээлэл бүрэн бөгөөд дууссан байна.',
                ],
            ],
        ],
    ],
];
