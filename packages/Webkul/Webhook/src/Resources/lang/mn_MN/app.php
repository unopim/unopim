<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Вебхууд',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Тохиргооноос Webhook-г идэвхжүүлнэ үү',
        'success'       => 'Бүтээгдэхүүний мэдээлэл Webhook руу амжилттай илгээгдлээ',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Вебхууд',
            'create' => 'Үүсгэх',
            'edit'   => 'Засах',
            'delete' => 'Устгах',
        ],
        'settings' => [
            'index'  => 'Тохиргоо',
            'update' => 'Тохиргоог шинэчлэх',
        ],
        'logs' => [
            'index'       => 'Логууд',
            'view'        => 'Харах',
            'delete'      => 'Устгах',
            'mass-delete' => 'Бөөнөөр устгах',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Бүтээгдэхүүн үүсгэгдсэн',
            'updated' => 'Бүтээгдэхүүн шинэчлэгдсэн',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Вебхууд',
            'create-btn'   => 'Webhook үүсгэх',
            'logs-btn'     => 'Логууд',
            'back-btn'     => 'Вебхуудруу буцах',
            'default-name' => 'Өгөгдмөл',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Нэр',
                'url'        => 'URL',
                'events'     => 'Үйл явдлууд',
                'status'     => 'Төлөв',
                'active'     => 'Идэвхтэй',
                'inactive'   => 'Идэвхгүй',
                'created_at' => 'Үүсгэсэн огноо',
                'edit'       => 'Засах',
                'delete'     => 'Устгах',
            ],
        ],
        'create' => [
            'title'    => 'Webhook үүсгэх',
            'cancel'   => 'Цуцлах',
            'save-btn' => 'Хадгалах',
        ],
        'edit' => [
            'title'    => 'Webhook засах',
            'cancel'   => 'Цуцлах',
            'save-btn' => 'Хадгалах',
        ],
        'form' => [
            'general'       => 'Ерөнхий',
            'name'          => 'Нэр',
            'url'           => 'URL',
            'events'        => 'Үйл явдлууд',
            'select-events' => 'Үйл явдлуудыг сонгох',
            'secret'        => 'Гарын үсгийн нууц түлхүүр',
            'secret-set'    => 'Нууц түлхүүр аль хэдийн тохируулагдсан байна',
            'secret-hint'   => 'Өгөгдөл бүрийг HMAC SHA-256 гарын үсгээр баталгаажуулахад ашиглагдана. Одоогийн нууц түлхүүрийг хадгалахын тулд хоосон орхино уу.',
            'settings'      => 'Тохиргоо',
            'active'        => 'Идэвхтэй',
            'test'          => 'Холболтыг шалгах',
            'test-hint'     => 'Дээрх URL руу туршилтын хүсэлт илгээнэ.',
            'test-btn'      => 'Туршилт илгээх',
            'test-no-url'   => 'Эхлээд URL оруулна уу.',
            'test-failed'   => 'Туршилтын хүсэлт амжилтгүй боллоо.',
            'headers'       => 'Тусгай толгойнууд',
            'add-header'    => 'Толгой нэмэх',
            'no-headers'    => 'Тусгай толгой нэмэгдээгүй байна.',
            'header-key'    => 'Толгой',
            'header-value'  => 'Утга',
        ],
        'create-success' => 'Webhook амжилттай үүсгэгдлээ',
        'update-success' => 'Webhook амжилттай шинэчлэгдлээ',
        'delete-success' => 'Webhook амжилттай устгагдлаа',
        'delete-failed'  => 'Webhook устгаж чадсангүй',
        'validation'     => [
            'unsafe-url' => 'URL нь хувийн, эргэлтийн буюу дотоод хаяг руу зааж байгаа тул зөвшөөрөгдөөгүй.',
            'scheme'     => 'URL нь http:// эсвэл https:// гэж эхэлсэн байх ёстой.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook туршилтын хүсэлт',
            'connection-failed' => 'URL-д хүрэх боломжгүй байна. URL-аа шалгана уу.',
            'unreachable'       => 'URL-д хүрэх боломжгүй байна (HTTP :code).',
            'reachable'         => 'URL-д хүрэх боломжтой байна.',
        ],
        'prune' => [
            'disabled' => 'Webhook логийн хадгалалт идэвхгүй тул юу ч устгагдсангүй.',
            'done'     => ':days өдрөөс хуучин :count webhook лог устгагдлаа.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Тохиргоо',
                    'save'    => 'Хадгалах',
                    'general' => 'Ерөнхий',
                    'active'  => [
                        'label' => 'Идэвхтэй Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Вебхуудын URL',
                        'required'          => 'Webhook идэвхтэй үед Webhook URL шаардлагатай.',
                        'scheme'            => 'Webhook URL нь http:// эсвэл https:// гэж эхэлсэн байх ёстой.',
                        'connection_failed' => 'Webhook URL-д хүрэх боломжгүй байна. URL-аа шалгана уу.',
                        'unreachable'       => 'Webhook URL хүчингүй байна (HTTP :code).',
                        'unsafe'            => 'Webhook URL хувийн, эргэлтийн буюу дотоод хаяг руу зааж байгаа тул зөвшөөрөгдөөгүй.',
                    ],
                    'success'    => 'Webhook тохиргоо амжилттай хадгалагдлаа',
                    'title'      => 'Webhook тохиргоо',
                    'logs-title' => 'Логууд',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Вебхууд',
                        'sku'              => 'SKU',
                        'event'            => 'Үйл явдал',
                        'created_at'       => 'Огноо/Цаг',
                        'user'             => 'Хэрэглэгч',
                        'status'           => 'Төлөв',
                        'success'          => 'Амжилттай',
                        'failed'           => 'Амжилтгүй',
                        'server_error'     => 'Серверийн алдаа',
                        'timeout_or_error' => 'Хугацаа дууссан/Алдаа',
                        'delete'           => 'Устгах',
                        'view'             => 'Харах',
                    ],
                    'title'          => 'Webhook логууд',
                    'show-title'     => 'Webhook бүртгэлийн дэлгэрэнгүй',
                    'sent-payload'   => 'Илгээсэн өгөгдөл',
                    'response'       => 'Хариу',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Энэ бүртгэлд өгөгдөл бичигдээгүй байна.',
                    'load-failed'    => 'Бүртгэлийн дэлгэрэнгүйг ачааллаж чадсангүй.',
                    'delete-success' => 'Webhook логууд амжилттай устгагдлаа',
                    'delete-failed'  => 'Webhook логуудыг устгах нь гэнэтийн алдаа гарлаа',
                    'unauthorized'   => 'Энэ үйлдэл зөвшөөрөгдөөгүй байна',
                ],
            ],
        ],
    ],
];
