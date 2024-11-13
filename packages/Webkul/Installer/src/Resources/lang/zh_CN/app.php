<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => '默认',
            ],

            'attribute-groups'   => [
                'description'       => '描述',
                'general'           => '一般的',
                'inventories'       => '库存',
                'meta-description'  => '元描述',
                'price'             => '价格',
                'technical'         => '技术的',
                'shipping'          => '船运',
            ],

            'attributes'         => [
                'brand'                => '品牌',
                'color'                => '颜色',
                'cost'                 => '成本',
                'description'          => '描述',
                'featured'             => '精选',
                'guest-checkout'       => '宾客结帐',
                'height'               => '高度',
                'length'               => '长度',
                'manage-stock'         => '管理库存',
                'meta-description'     => '元描述',
                'meta-keywords'        => '元关键词',
                'meta-title'           => '元标题',
                'name'                 => '姓名',
                'new'                  => '新的',
                'price'                => '价格',
                'product-number'       => '产品编号',
                'short-description'    => '简短描述',
                'size'                 => '尺寸',
                'sku'                  => '存货单位',
                'special-price-from'   => '特价从',
                'special-price-to'     => '特价至',
                'special-price'        => '特价',
                'status'               => '地位',
                'tax-category'         => '税种',
                'url-key'              => '网址键',
                'visible-individually' => '单独可见',
                'weight'               => '重量',
                'width'                => '宽度',
            ],

            'attribute-options'  => [
                'black'  => '黑色的',
                'green'  => '绿色的',
                'l'      => 'L',
                'm'      => '中号',
                'red'    => '红色的',
                's'      => 'S',
                'white'  => '白色的',
                'xl'     => 'XL',
                'yellow' => '黄色的',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => '根类别描述',
                'name'        => '根',
            ],

            'category_fields' => [
                'name'        => '姓名',
                'description' => '描述',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => '关于我们页面内容',
                    'title'   => '关于我们',
                ],

                'contact-us'       => [
                    'content' => '联系我们页面内容',
                    'title'   => '联系我们',
                ],

                'customer-service' => [
                    'content' => '客户服务页面内容',
                    'title'   => '客户服务',
                ],

                'payment-policy'   => [
                    'content' => '付款政策页面内容',
                    'title'   => '付款政策',
                ],

                'privacy-policy'   => [
                    'content' => '隐私政策页面内容',
                    'title'   => '隐私政策',
                ],

                'refund-policy'    => [
                    'content' => '退款政策页面内容',
                    'title'   => '退款政策',
                ],

                'return-policy'    => [
                    'content' => '退货政策页面内容',
                    'title'   => '退货政策',
                ],

                'shipping-policy'  => [
                    'content' => '运输政策页面内容',
                    'title'   => '运输政策',
                ],

                'terms-conditions' => [
                    'content' => '条款和条件页面内容',
                    'title'   => '条款及条件',
                ],

                'terms-of-use'     => [
                    'content' => '使用条款页面内容',
                    'title'   => '使用条款',
                ],

                'whats-new'        => [
                    'content' => '新增内容页面内容',
                    'title'   => '什么是新的',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => '示范店',
                'meta-keywords'    => '演示商店元关键字',
                'meta-description' => '演示商店元描述',
                'name'             => '默认',
            ],

            'currencies' => [
                'AED' => '迪拉姆',
                'AFN' => '以色列谢克尔',
                'CNY' => '人民币',
                'EUR' => '欧元',
                'GBP' => '英镑',
                'INR' => '印度卢比',
                'IRR' => '伊朗里亚尔',
                'JPY' => '日圆',
                'RUB' => '俄罗斯卢布',
                'SAR' => '沙特里亚尔',
                'TRY' => '土耳其里拉',
                'UAH' => '乌克兰格里夫纳',
                'USD' => '美元',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => '一般的',
                'guest'     => '客人',
                'wholesale' => '批发的',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => '默认',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => '所有产品',

                    'options' => [
                        'title' => '所有产品',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => '查看全部',
                        'description' => '隆重推出我们大胆的新系列！通过大胆的设计和充满活力的宣言提升您的风格。探索引人注目的图案和大胆的色彩，重新定义您的衣柜。准备好拥抱非凡吧！',
                        'title'       => '准备好迎接我们新的大胆系列吧！',
                    ],

                    'name'    => '大胆系列',
                ],

                'categories-collections' => [
                    'name' => '类别 收藏',
                ],

                'featured-collections'   => [
                    'name'    => '特色收藏',

                    'options' => [
                        'title' => '特色产品',
                    ],
                ],

                'footer-links'           => [
                    'name'    => '页脚链接',

                    'options' => [
                        'about-us'         => '关于我们',
                        'contact-us'       => '联系我们',
                        'customer-service' => '客户服务',
                        'payment-policy'   => '付款政策',
                        'privacy-policy'   => '隐私政策',
                        'refund-policy'    => '退款政策',
                        'return-policy'    => '退货政策',
                        'shipping-policy'  => '运输政策',
                        'terms-conditions' => '条款及条件',
                        'terms-of-use'     => '使用条款',
                        'whats-new'        => '什么是新的',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => '我们的系列',
                        'sub-title-2' => '我们的系列',
                        'title'       => '游戏新增了我们的新内容！',
                    ],

                    'name'    => '游戏容器',
                ],

                'image-carousel'         => [
                    'name'    => '图像轮播',

                    'sliders' => [
                        'title' => '为新系列做好准备',
                    ],
                ],

                'new-products'           => [
                    'name'    => '新产品',

                    'options' => [
                        'title' => '新产品',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => '第一份订单可享受高达 40% 的折扣 现在购买',
                    ],

                    'name' => '优惠资讯',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => '所有主要信用卡均提供免费 EMI',
                        'free-shipping-info'   => '所有订单均可享受免费送货服务',
                        'product-replace-info' => '可以轻松更换产品！',
                        'time-support-info'    => '通过聊天和电子邮件提供 24/7 专门支持',
                    ],

                    'name'        => '服务内容',

                    'title'       => [
                        'emi-available'   => '艾米可用',
                        'free-shipping'   => '免运费',
                        'product-replace' => '产品更换',
                        'time-support'    => '24/7 支持',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => '我们的系列',
                        'sub-title-2' => '我们的系列',
                        'sub-title-3' => '我们的系列',
                        'sub-title-4' => '我们的系列',
                        'sub-title-5' => '我们的系列',
                        'sub-title-6' => '我们的系列',
                        'title'       => '游戏新增了我们的新内容！',
                    ],

                    'name'    => '热门收藏',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => '该角色用户将拥有所有访问权限',
                'name'        => '行政人员',
            ],

            'users' => [
                'name' => '例子',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => '行政',
                'unopim'           => '乌诺皮姆',
                'confirm-password' => '确认密码',
                'email-address'    => 'admin@example.com',
                'email'            => '电子邮件',
                'password'         => '密码',
                'title'            => '创建管理员',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => '允许的货币',
                'allowed-locales'     => '允许的区域设置',
                'application-name'    => '应用名称',
                'unopim'              => '乌诺皮姆',
                'chinese-yuan'        => '人民币 (CNY)',
                'database-connection' => '数据库连接',
                'database-hostname'   => '数据库主机名',
                'database-name'       => '数据库名称',
                'database-password'   => '数据库密码',
                'database-port'       => '数据库端口',
                'database-prefix'     => '数据库前缀',
                'database-username'   => '数据库用户名',
                'default-currency'    => '默认货币',
                'default-locale'      => '默认区域设置',
                'default-timezone'    => '默认时区',
                'default-url-link'    => 'https://本地主机',
                'default-url'         => '默认网址',
                'dirham'              => '迪拉姆 (AED)',
                'euro'                => '欧元 (EUR)',
                'iranian'             => '伊朗里亚尔 (IRR)',
                'israeli'             => '以色列谢克尔 (AFN)',
                'japanese-yen'        => '日元 (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => '英镑 (GBP)',
                'rupee'               => '印度卢比 (INR)',
                'russian-ruble'       => '俄罗斯卢布 (RUB)',
                'saudi'               => '沙特里亚尔 (SAR)',
                'select-timezone'     => '选择时区',
                'sqlsrv'              => 'SQLSRV',
                'title'               => '数据库配置',
                'turkish-lira'        => '土耳其里拉 (TRY)',
                'ukrainian-hryvnia'   => '乌克兰格里夫纳 (UAH)',
                'usd'                 => '美元 (USD)',
                'warning-message'     => '提防！默认系统语言和默认货币的设置是永久性的，不能再次更改。',
            ],

            'installation-processing'   => [
                'unopim'            => '安装 UnoPim',
                'unopim-info'       => '创建数据库表，这可能需要一些时间',
                'title'             => '安装',
            ],

            'installation-completed'    => [
                'admin-panel'                   => '管理面板',
                'unopim-forums'                 => 'UnoPim 论坛',
                'explore-unopim-extensions'     => '探索 UnoPim 扩展',
                'title-info'                    => 'UnoPim 已成功安装在您的系统上。',
                'title'                         => '安装完成',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => '创建数据库表',
                'install-info-button'     => '点击下面的按钮即可',
                'install-info'            => 'UnoPim 安装',
                'install'                 => '安装',
                'populate-database-table' => '填充数据库表',
                'start-installation'      => '开始安装',
                'title'                   => '准备安装',
            ],

            'start'                     => [
                'locale'        => '语言环境',
                'main'          => '开始',
                'select-locale' => '选择区域设置',
                'title'         => '您的 UnoPim 安装',
                'welcome-title' => '欢迎来到乌诺皮姆 :version',
            ],

            'server-requirements'       => [
                'calendar'    => '日历',
                'ctype'       => '类型',
                'curl'        => '卷曲',
                'dom'         => '多姆',
                'fileinfo'    => '文件信息',
                'filter'      => '筛选',
                'gd'          => 'GD',
                'hash'        => '哈希值',
                'intl'        => '国际',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => '开放式SSL',
                'pcre'        => '聚合酶链反应',
                'pdo'         => '普多',
                'php-version' => '8.2 或更高',
                'php'         => 'PHP',
                'session'     => '会议',
                'title'       => '系统要求',
                'tokenizer'   => '分词器',
                'xml'         => 'XML',
            ],

            'arabic'                    => '阿拉伯',
            'back'                      => '后退',
            'unopim-info'               => '社区项目',
            'unopim-logo'               => '乌诺皮姆标志',
            'unopim'                    => '乌诺皮姆',
            'bengali'                   => '孟加拉',
            'chinese'                   => '中国人',
            'continue'                  => '继续',
            'dutch'                     => '荷兰语',
            'english'                   => '英语',
            'french'                    => '法语',
            'german'                    => '德语',
            'hebrew'                    => '希伯来语',
            'hindi'                     => '印地语',
            'installation-description'  => 'UnoPim 安装通常涉及几个步骤。以下是 UnoPim 安装过程的概要：',
            'wizard-language'           => '安装向导语言',
            'installation-info'         => '我们很高兴在这里见到您！',
            'installation-title'        => '欢迎来到安装',
            'italian'                   => '意大利语',
            'japanese'                  => '日本人',
            'persian'                   => '波斯语',
            'polish'                    => '抛光',
            'portuguese'                => '巴西葡萄牙语',
            'russian'                   => '俄语',
            'save-configuration'        => '保存配置',
            'sinhala'                   => '僧伽罗语',
            'skip'                      => '跳过',
            'spanish'                   => '西班牙语',
            'title'                     => 'UnoPim 安装程序',
            'turkish'                   => '土耳其',
            'ukrainian'                 => '乌克兰',
            'webkul'                    => '韦伯库尔',
        ],
    ],
];
