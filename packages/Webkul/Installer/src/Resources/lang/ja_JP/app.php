<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'デフォルト',
            ],

            'attribute-groups'   => [
                'description'       => '説明',
                'general'           => '一般的な',
                'inventories'       => '在庫',
                'meta-description'  => 'メタディスクリプション',
                'price'             => '価格',
                'technical'         => 'テクニカル',
                'shipping'          => '配送',
            ],

            'attributes'         => [
                'brand'                => 'ブランド',
                'color'                => '色',
                'cost'                 => '料金',
                'description'          => '説明',
                'featured'             => '注目の',
                'guest-checkout'       => 'ゲストチェックアウト',
                'height'               => '身長',
                'length'               => '長さ',
                'manage-stock'         => '在庫の管理',
                'meta-description'     => 'メタディスクリプション',
                'meta-keywords'        => 'メタキーワード',
                'meta-title'           => 'メタタイトル',
                'name'                 => '名前',
                'new'                  => '新しい',
                'price'                => '価格',
                'product-number'       => '製品番号',
                'short-description'    => '簡単な説明',
                'size'                 => 'サイズ',
                'sku'                  => 'SKU',
                'special-price-from'   => 'からの特別価格',
                'special-price-to'     => '特別価格へ',
                'special-price'        => '特別価格',
                'status'               => '状態',
                'tax-category'         => '税区分',
                'url-key'              => 'URLキー',
                'visible-individually' => '個別に表示',
                'weight'               => '重さ',
                'width'                => '幅',
            ],

            'attribute-options'  => [
                'black'  => '黒',
                'green'  => '緑',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => '赤',
                's'      => 'S',
                'white'  => '白',
                'xl'     => 'XL',
                'yellow' => '黄色',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'ルートカテゴリの説明',
                'name'        => '根',
            ],

            'category_fields' => [
                'name'        => '名前',
                'description' => '説明',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => '会社概要ページのコンテンツ',
                    'title'   => '私たちについて',
                ],

                'contact-us'       => [
                    'content' => 'お問い合わせページのコンテンツ',
                    'title'   => 'お問い合わせ',
                ],

                'customer-service' => [
                    'content' => 'カスタマーサービスページのコンテンツ',
                    'title'   => '顧客サービス',
                ],

                'payment-policy'   => [
                    'content' => '支払いポリシーページのコンテンツ',
                    'title'   => '支払いポリシー',
                ],

                'privacy-policy'   => [
                    'content' => 'プライバシーポリシーページの内容',
                    'title'   => 'プライバシーポリシー',
                ],

                'refund-policy'    => [
                    'content' => '返金ポリシーページのコンテンツ',
                    'title'   => '返金ポリシー',
                ],

                'return-policy'    => [
                    'content' => '返品ポリシーページのコンテンツ',
                    'title'   => '返品規則',
                ],

                'shipping-policy'  => [
                    'content' => '配送ポリシーページのコンテンツ',
                    'title'   => '配送ポリシー',
                ],

                'terms-conditions' => [
                    'content' => '利用規約ページのコンテンツ',
                    'title'   => '利用規約',
                ],

                'terms-of-use'     => [
                    'content' => '利用規約ページのコンテンツ',
                    'title'   => '利用規約',
                ],

                'whats-new'        => [
                    'content' => '「新着情報」ページのコンテンツ',
                    'title'   => '新着情報',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'デモストア',
                'meta-keywords'    => 'デモストアのメタキーワード',
                'meta-description' => 'デモストアのメタディスクリプション',
                'name'             => 'デフォルト',
            ],

            'currencies' => [
                'AED' => 'ディルハム',
                'AFN' => 'イスラエルシェケル',
                'CNY' => '中国人民元',
                'EUR' => 'ユーロ',
                'GBP' => '英ポンド',
                'INR' => 'インドルピー',
                'IRR' => 'イランリアル',
                'JPY' => '日本円',
                'RUB' => 'ロシアルーブル',
                'SAR' => 'サウジアラビアリヤル',
                'TRY' => 'トルコリラ',
                'UAH' => 'ウクライナ・グリブナ',
                'USD' => '米ドル',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => '一般的な',
                'guest'     => 'ゲスト',
                'wholesale' => '卸売',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'デフォルト',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'すべての製品',

                    'options' => [
                        'title' => 'すべての製品',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'すべて見る',
                        'description' => '新しい大胆なコレクションをご紹介します!大胆なデザインと鮮やかなステートメントでスタイルを高めましょう。ワードローブを再定義する印象的なパターンと大胆な色を探してください。非日常を受け入れる準備をしましょう！',
                        'title'       => '新しい大胆なコレクションに備えましょう!',
                    ],

                    'name'    => '大胆なコレクション',
                ],

                'categories-collections' => [
                    'name' => 'カテゴリ コレクション',
                ],

                'featured-collections'   => [
                    'name'    => '注目のコレクション',

                    'options' => [
                        'title' => '注目の製品',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'フッターリンク',

                    'options' => [
                        'about-us'         => '私たちについて',
                        'contact-us'       => 'お問い合わせ',
                        'customer-service' => '顧客サービス',
                        'payment-policy'   => '支払いポリシー',
                        'privacy-policy'   => 'プライバシーポリシー',
                        'refund-policy'    => '返金ポリシー',
                        'return-policy'    => '返品規則',
                        'shipping-policy'  => '配送ポリシー',
                        'terms-conditions' => '利用規約',
                        'terms-of-use'     => '利用規約',
                        'whats-new'        => '新着情報',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => '私たちのコレクション',
                        'sub-title-2' => '私たちのコレクション',
                        'title'       => '新しい追加要素を備えたゲームです！',
                    ],

                    'name'    => 'ゲームコンテナ',
                ],

                'image-carousel'         => [
                    'name'    => '画像カルーセル',

                    'sliders' => [
                        'title' => '新しいコレクションの準備をしましょう',
                    ],
                ],

                'new-products'           => [
                    'name'    => '新製品',

                    'options' => [
                        'title' => '新製品',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => '初回注文で最大 40% オフ 今すぐ購入',
                    ],

                    'name' => 'オファー情報',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'すべての主要なクレジット カードで無料の EMI をご利用いただけます',
                        'free-shipping-info'   => 'すべてのご注文で送料無料をお楽しみください',
                        'product-replace-info' => '簡単に製品交換が可能！',
                        'time-support-info'    => 'チャットとメールによる年中無休の専用サポート',
                    ],

                    'name'        => 'サービス内容',

                    'title'       => [
                        'emi-available'   => 'エミが利用可能',
                        'free-shipping'   => '送料無料',
                        'product-replace' => '製品の交換',
                        'time-support'    => '年中無休のサポート',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => '私たちのコレクション',
                        'sub-title-2' => '私たちのコレクション',
                        'sub-title-3' => '私たちのコレクション',
                        'sub-title-4' => '私たちのコレクション',
                        'sub-title-5' => '私たちのコレクション',
                        'sub-title-6' => '私たちのコレクション',
                        'title'       => '新しい追加要素を備えたゲームです！',
                    ],

                    'name'    => 'トップコレクション',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'この役割のユーザーはすべてのアクセス権を持ちます',
                'name'        => '管理者',
            ],

            'users' => [
                'name' => '例',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => '管理者',
                'unopim'           => 'ウノピム',
                'confirm-password' => 'パスワードを認証する',
                'email-address'    => 'admin@example.com',
                'email'            => '電子メール',
                'password'         => 'パスワード',
                'title'            => '管理者の作成',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => '使用できる通貨',
                'allowed-locales'     => '許可されるロケール',
                'application-name'    => 'アプリケーション名',
                'unopim'              => 'ウノピム',
                'chinese-yuan'        => '中国人民元 (CNY)',
                'database-connection' => 'データベース接続',
                'database-hostname'   => 'データベースのホスト名',
                'database-name'       => 'データベース名',
                'database-password'   => 'データベースのパスワード',
                'database-port'       => 'データベースポート',
                'database-prefix'     => 'データベースプレフィックス',
                'database-username'   => 'データベースのユーザー名',
                'default-currency'    => 'デフォルトの通貨',
                'default-locale'      => 'デフォルトのロケール',
                'default-timezone'    => 'デフォルトのタイムゾーン',
                'default-url-link'    => 'https://ローカルホスト',
                'default-url'         => 'デフォルトのURL',
                'dirham'              => 'ディルハム (AED)',
                'euro'                => 'ユーロ (EUR)',
                'iranian'             => 'イラン リアル (IRR)',
                'israeli'             => 'イスラエルシェケル (AFN)',
                'japanese-yen'        => '日本円(JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => '英ポンド (GBP)',
                'rupee'               => 'インドルピー (INR)',
                'russian-ruble'       => 'ロシアルーブル (RUB)',
                'saudi'               => 'サウジアラビア リヤル (SAR)',
                'select-timezone'     => 'タイムゾーンの選択',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'データベース構成',
                'turkish-lira'        => 'トルコリラ (TRY)',
                'ukrainian-hryvnia'   => 'ウクライナ グリブナ (UAH)',
                'usd'                 => '米ドル (USD)',
                'warning-message'     => '気をつけて！デフォルトのシステム言語およびデフォルトの通貨の設定は永続的であり、二度と変更することはできません。',
            ],

            'installation-processing'   => [
                'unopim'            => 'UnoPimのインストール',
                'unopim-info'       => 'データベーステーブルを作成しています。これには少し時間がかかる場合があります',
                'title'             => 'インストール',
            ],

            'installation-completed'    => [
                'admin-panel'                   => '管理者パネル',
                'unopim-forums'                 => 'ウノピムフォーラム',
                'explore-unopim-extensions'     => 'UnoPim 拡張機能を探索する',
                'title-info'                    => 'UnoPim はシステムに正常にインストールされました。',
                'title'                         => 'インストールが完了しました',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'データベーステーブルを作成する',
                'install-info-button'     => '下のボタンをクリックしてください',
                'install-info'            => 'インストール用の UnoPim',
                'install'                 => 'インストール',
                'populate-database-table' => 'データベーステーブルにデータを取り込む',
                'start-installation'      => 'インストールの開始',
                'title'                   => 'インストールの準備ができました',
            ],

            'start'                     => [
                'locale'        => 'ロケール',
                'main'          => '始める',
                'select-locale' => 'ロケールの選択',
                'title'         => 'UnoPim のインストール',
                'welcome-title' => 'UnoPimへようこそ '.core()->version(),
            ],

            'server-requirements'       => [
                'calendar'    => 'カレンダー',
                'ctype'       => 'cタイプ',
                'curl'        => 'カール',
                'dom'         => 'ドム',
                'fileinfo'    => 'ファイル情報',
                'filter'      => 'フィルター',
                'gd'          => 'GD',
                'hash'        => 'ハッシュ',
                'intl'        => '国際',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'オープンSSL',
                'pcre'        => 'ピークレ',
                'pdo'         => 'プドゥ',
                'php-version' => '8.2以上',
                'php'         => 'PHP',
                'session'     => 'セッション',
                'title'       => 'システム要件',
                'tokenizer'   => 'トークナイザー',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'アラビア語',
            'back'                      => '戻る',
            'UnoPim-info'               => 'によるコミュニティプロジェクト',
            'unopim-logo'               => 'ウノピムのロゴ',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'ベンガル語',
            'chinese'                   => '中国語',
            'continue'                  => '続く',
            'dutch'                     => 'オランダ語',
            'english'                   => '英語',
            'french'                    => 'フランス語',
            'german'                    => 'ドイツ語',
            'hebrew'                    => 'ヘブライ語',
            'hindi'                     => 'ヒンディー語',
            'installation-description'  => '通常、UnoPim のインストールにはいくつかの手順が必要です。 UnoPim のインストール プロセスの概要は次のとおりです。',
            'wizard-language'           => 'インストールウィザードの言語',
            'installation-info'         => '皆様にお会いできて嬉しいです！',
            'installation-title'        => 'インストールへようこそ',
            'italian'                   => 'イタリア語',
            'japanese'                  => '日本語',
            'persian'                   => 'ペルシア語',
            'polish'                    => '研磨',
            'portuguese'                => 'ブラジル系ポルトガル語',
            'russian'                   => 'ロシア',
            'save-configuration'        => '設定の保存',
            'sinhala'                   => 'シンハラ語',
            'skip'                      => 'スキップ',
            'spanish'                   => 'スペイン語',
            'title'                     => 'UnoPim インストーラー',
            'turkish'                   => 'トルコ語',
            'ukrainian'                 => 'ウクライナ語',
            'webkul'                    => 'Webkul',
        ],
    ],
];
