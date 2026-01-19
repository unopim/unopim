<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'デフォルト',
            ],

            'attribute-groups' => [
                'description'      => '説明',
                'general'          => '一般',
                'meta-description' => 'メタ説明',
                'price'            => '価格',
                'media'            => 'メディア',
            ],

            'attributes' => [
                'brand'                => 'ブランド',
                'color'                => '色',
                'cost'                 => '料金',
                'description'          => '説明',
                'featured'             => '注目の',
                'guest-checkout'       => 'ゲストチェックアウト',
                'height'               => '身長',
                'image'                => '画像',
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
                'tax-category'         => '税区分',
                'url-key'              => 'URLキー',
                'visible-individually' => '個別に表示',
                'weight'               => '重さ',
                'width'                => '幅',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'ルートカテゴリの説明',
                'name'        => '根',
            ],

            'category_fields' => [
                'name'        => '名前',
                'description' => '説明',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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
        'middleware' => [
            'already-installed' => 'アプリケーションは既にインストールされています。',
        ],

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

            'installation-processing' => [
                'unopim'      => 'UnoPimのインストール',
                'unopim-info' => 'データベーステーブルを作成しています。これには少し時間がかかる場合があります',
                'title'       => 'インストール',
            ],

            'installation-completed' => [
                'admin-panel'               => '管理者パネル',
                'unopim-forums'             => 'ウノピムフォーラム',
                'explore-unopim-extensions' => 'UnoPim 拡張機能を探索する',
                'title-info'                => 'UnoPim はシステムに正常にインストールされました。',
                'title'                     => 'インストールが完了しました',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'データベーステーブルを作成する',
                'install-info-button'     => '下のボタンをクリックしてください',
                'install-info'            => 'インストール用の UnoPim',
                'install'                 => 'インストール',
                'populate-database-table' => 'データベーステーブルにデータを取り込む',
                'start-installation'      => 'インストールの開始',
                'title'                   => 'インストールの準備ができました',
            ],

            'start' => [
                'locale'        => 'ロケール',
                'main'          => '始める',
                'select-locale' => 'ロケールの選択',
                'title'         => 'UnoPim のインストール',
                'welcome-title' => 'UnoPimへようこそ :version',
            ],

            'server-requirements' => [
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

            'back'                     => '戻る',
            'unopim-info'              => 'によるコミュニティプロジェクト',
            'unopim-logo'              => 'ウノピムのロゴ',
            'unopim'                   => 'UnoPim',
            'continue'                 => '続く',
            'installation-description' => '通常、UnoPim のインストールにはいくつかの手順が必要です。 UnoPim のインストール プロセスの概要は次のとおりです。',
            'wizard-language'          => 'インストールウィザードの言語',
            'installation-info'        => '皆様にお会いできて嬉しいです！',
            'installation-title'       => 'インストールへようこそ',
            'save-configuration'       => '設定の保存',
            'skip'                     => 'スキップ',
            'title'                    => 'UnoPim インストーラー',
            'webkul'                   => 'Webkul',
        ],
    ],
];
