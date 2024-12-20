<?php

return [
    'users' => [
        'sessions' => [
            'email'                => '電子郵件地址',
            'forget-password-link' => '忘記密碼？',
            'password'             => '密碼',
            'submit-btn'           => '登入',
            'title'                => '登入',
        ],

        'forget-password' => [
            'create' => [
                'email'                => '已註冊的電子郵件',
                'email-not-exist'      => '電子郵件不存在',
                'page-title'           => '忘記密碼',
                'reset-link-sent'      => '重置密碼鏈接已發送',
                'email-settings-error' => '無法發送電子郵件。請檢查您的電子郵件配置詳情',
                'sign-in-link'         => '返回登入？',
                'submit-btn'           => '重設',
                'title'                => '恢復密碼',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => '返回登入？',
            'confirm-password' => '確認密碼',
            'email'            => '已註冊的電子郵件',
            'password'         => '密碼',
            'submit-btn'       => '重置密碼',
            'title'            => '重置密碼',
        ],
    ],

    'notifications' => [
        'description-text' => '列出所有通知',
        'marked-success'   => '通知成功標記',
        'no-record'        => '找不到記錄',
        'read-all'         => '標記為已讀',
        'title'            => '通知',
        'view-all'         => '查看全部',
        'status'           => [
            'all'        => '全部',
            'canceled'   => '取消',
            'closed'     => '關閉',
            'completed'  => '已完成',
            'pending'    => '待處理',
            'processing' => '處理中',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => '返回',
            'change-password'   => '更改密碼',
            'confirm-password'  => '確認密碼',
            'current-password'  => '當前密碼',
            'email'             => '電子郵件',
            'general'           => '常規',
            'invalid-password'  => '您輸入的當前密碼不正確。',
            'name'              => '名稱',
            'password'          => '密碼',
            'profile-image'     => '個人資料圖片',
            'save-btn'          => '保存賬戶',
            'title'             => '我的賬戶',
            'ui-locale'         => 'UI 地區設置',
            'update-success'    => '賬戶更新成功',
            'upload-image-info' => '上傳個人資料圖片 (110px X 110px)',
            'user-timezone'     => '用戶時區',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => '儀表板',
            'user-info'        => '快速監控，PIM 中的計數',
            'user-name'        => '你好！ :user_name',
            'catalog-details'  => '目錄',
            'total-families'   => '總家庭',
            'total-attributes' => '總屬性',
            'total-groups'     => '總組',
            'total-categories' => '總類別',
            'total-products'   => '總產品',
            'settings-details' => '目錄結構',
            'total-locales'    => '總地區',
            'total-currencies' => '總貨幣',
            'total-channels'   => '總頻道',
        ],
    ],

    'acl' => [
        'addresses'                => '地址',
        'attribute-families'       => '屬性家庭',
        'attribute-groups'         => '屬性群組',
        'attributes'               => '屬性',
        'cancel'                   => '取消',
        'catalog'                  => '目錄',
        'categories'               => '類別',
        'channels'                 => '頻道',
        'configure'                => '配置',
        'configuration'            => '配置',
        'copy'                     => '複製',
        'create'                   => '創建',
        'currencies'               => '貨幣',
        'dashboard'                => '儀表板',
        'data-transfer'            => '數據傳輸',
        'delete'                   => '刪除',
        'edit'                     => '編輯',
        'email-templates'          => '電子郵件模板',
        'events'                   => '事件',
        'groups'                   => '組',
        'import'                   => '導入',
        'imports'                  => '導入',
        'invoices'                 => '發票',
        'locales'                  => '語言環境',
        'magic-ai'                 => 'Magic AI',
        'marketing'                => '營銷',
        'newsletter-subscriptions' => '電子報訂閱',
        'note'                     => '筆記',
        'orders'                   => '訂單',
        'products'                 => '產品',
        'promotions'               => '促銷活動',
        'refunds'                  => '退款',
        'reporting'                => '報告',
        'reviews'                  => '評論',
        'roles'                    => '角色',
        'sales'                    => '銷售',
        'search-seo'               => '搜索 & SEO',
        'search-synonyms'          => '搜索同義詞',
        'search-terms'             => '搜索條款',
        'settings'                 => '設置',
        'shipments'                => '發貨',
        'sitemaps'                 => '網站地圖',
        'subscribers'              => '訂閱者',
        'tax-categories'           => '稅類別',
        'tax-rates'                => '稅率',
        'taxes'                    => '稅',
        'themes'                   => '主題',
        'integration'              => '集成',
        'url-rewrites'             => 'URL 重新編寫',
        'users'                    => '用戶',
        'category_fields'          => '分類字段',
        'view'                     => '查看',
        'execute'                  => '執行',
        'history'                  => '歷史記錄',
        'restore'                  => '恢復',
        'integrations'             => '集成',
        'api'                      => 'API',
        'tracker'                  => '工作追踪器',
        'imports'                  => '導入',
        'exports'                  => '出口',
    ],

    'errors' => [
        'dashboard' => '儀表板',
        'go-back'   => '返回',
        'support'   => '如果問題仍然存在，請通過電子郵件<a href=":link" class=":class">:email</a>聯繫我們尋求幫助。',

        '404' => [
            'description' => '哎呀！您查找的頁面在度假。似乎我們找不到您正在尋找的內容。',
            'title'       => '404 頁面未找到',
        ],

        '401' => [
            'description' => '哎呀！似乎您無權訪問此頁面。似乎您缺少必要的許可權。',
            'title'       => '401 未授權',
            'message'     => '身份驗證失敗，由於無效的憑證或過期的令牌。',
        ],

        '403' => [
            'description' => '哎呀！此頁面禁止訪問。似乎您無權查看此內容。',
            'title'       => '403 禁止',
        ],

        '413' => [
            'description' => '哎呀！似乎您嘗試上傳一個太大的文件。如果要上傳該文件，請根據需要更新PHP配置。',
            'title'       => '413 内容太大',
        ],

        '419' => [
            'description' => '哎呀！您的會话已過期。請刷新頁面並重新登錄以繼續。',
            'title'       => '419 会话已过期',
        ],

        '500' => [
            'description' => '哎呀！出了點问题。似乎我们在加载您正在寻找的页面时遇到问题。',
            'title'       => '500 内部服务器错误',
        ],

        '503' => [
            'description' => '哎呀！似乎我们暂时无法进行维护。请稍后再检查。',
            'title'       => '503 服务不可用',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => '下載',
        'export'     => '快速匯出',
        'no-records' => '無需匯出資料',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => '這個slug正在用於分類或產品。',
        'slug-reserved'   => '這個slug是保留的。',
        'invalid-locale'  => '無效的地區 :locales',
    ],

    'footer' => [
        'copy-right' => '由UnoPim支持，由Webkul的社區項目',
    ],

    'emails' => [
        'dear'   => '親愛的 :admin_name',
        'thanks' => '如果需要任何幫助，請聯繫我們：<a href=":link" style=":style">:email</a>。<br/>謝謝！',

        'admin' => [
            'forgot-password' => [
                'description'    => '您收到此電子郵件是因為我們收到了您的密碼重置請求。',
                'greeting'       => '忘記密碼！',
                'reset-password' => '重置密碼',
                'subject'        => '重置密碼電子郵件',
            ],
        ],
    ],

    'common' => [
        'yes'     => '是',
        'no'      => '否',
        'true'    => '真實',
        'false'   => '虛假',
        'enable'  => '啟用',
        'disable' => '停用',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => '刪除',
            'no-result-found'              => '未找到結果',
            'save-btn'                     => '保存配置',
            'save-message'                 => '配置成功保存',
            'search'                       => '搜尋',
            'title'                        => '配置',

            'general' => [
                'info'  => '',
                'title' => '常規',

                'general' => [
                    'info'  => '',
                    'title' => '常規',
                ],

                'magic-ai' => [
                    'info'  => '設定Magic AI選項。',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API密鑰',
                        'enabled'        => '啟用',
                        'llm-api-domain' => 'LLM API域名',
                        'organization'   => '組織ID',
                        'title'          => '常規設定',
                        'title-info'     => '提升您的Magic AI體驗，通過輸入您的專用API密鑰並確定相關的組織進行無縫集成。控制您的OpenAI憑證並根據您的具體需求定制設定。',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => '創建',
                'title'      => '集成',

                'datagrid' => [
                    'delete'          => '刪除',
                    'edit'            => '編輯',
                    'id'              => 'ID',
                    'name'            => '名稱',
                    'user'            => '用戶',
                    'client-id'       => '客戶端ID',
                    'permission-type' => '許可類型',
                ],
            ],

            'create' => [
                'access-control' => '訪問控制',
                'all'            => '全部',
                'back-btn'       => '返回',
                'custom'         => '自定義',
                'assign-user'    => '分配用戶',
                'general'        => '常規',
                'name'           => '名稱',
                'permissions'    => '許可權',
                'save-btn'       => '保存',
                'title'          => '新建集成',
            ],

            'edit' => [
                'access-control' => '訪問控制',
                'all'            => '全部',
                'back-btn'       => '返回',
                'custom'         => '自定義',
                'assign-user'    => '分配用戶',
                'general'        => '常規',
                'name'           => '名稱',
                'credentials'    => '憑證',
                'client-id'      => '客戶端ID',
                'secret-key'     => '秘密密鑰',
                'generate-btn'   => '生成',
                're-secret-btn'  => '重新生成秘密密鑰',
                'permissions'    => '許可權',
                'save-btn'       => '保存',
                'title'          => '編輯集成',
            ],

            'being-used'                     => 'API集成已被管理用戶使用',
            'create-success'                 => 'API集成成功創建',
            'delete-failed'                  => 'API集成刪除失敗',
            'delete-success'                 => 'API集成成功刪除',
            'last-delete-error'              => '最後的API集成不能被刪除',
            'update-success'                 => 'API集成成功更新',
            'generate-key-success'           => 'API密鑰成功生成',
            're-generate-secret-key-success' => 'API秘密密鑰成功重新生成',
            'client-not-found'               => '客戶端未找到',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => '帳戶',
                'app-version'   => '版本 : :version',
                'logout'        => '登出',
                'my-account'    => '我的帳戶',
                'notifications' => '通知',
                'visit-shop'    => '訪問商店',
            ],

            'sidebar' => [
                'attribute-families'       => '屬性族群',
                'attribute-groups'         => '屬性群組',
                'attributes'               => '屬性',
                'history'                  => '歷史',
                'edit-section'             => '資料',
                'general'                  => '一般',
                'catalog'                  => '目錄',
                'categories'               => '分類',
                'category_fields'          => '分類字段',
                'channels'                 => '頻道',
                'collapse'                 => '折疊',
                'configure'                => '配置',
                'currencies'               => '貨幣',
                'dashboard'                => '儀表板',
                'data-transfer'            => '數據傳輸',
                'groups'                   => '群組',
                'tracker'                  => '工作跟踪器',
                'imports'                  => '導入',
                'exports'                  => '出口',
                'locales'                  => '語言地區',
                'magic-ai'                 => '魔法AI',
                'mode'                     => '黑暗模式',
                'products'                 => '產品',
                'roles'                    => '角色',
                'settings'                 => '設置',
                'themes'                   => '主題',
                'users'                    => '用戶',
                'integrations'             => '集成',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => '未選擇任何記錄。',
                'must-select-a-mass-action-option' => '必須選擇一個批量操作選項。',
                'must-select-a-mass-action'        => '必須選擇一個批量操作。',
            ],

            'toolbar' => [
                'length-of' => ':length of',
                'of'        => '的',
                'per-page'  => '每頁',
                'results'   => ':total 結果',
                'selected'  => ':total 選擇',

                'mass-actions' => [
                    'submit'        => '提交',
                    'select-option' => '選擇選項',
                    'select-action' => '選擇操作',
                ],

                'filter' => [
                    'title' => '過濾',
                ],

                'search_by' => [
                    'code'       => '按代碼搜索',
                    'code_or_id' => '按代碼或ID搜索',
                ],

                'search' => [
                    'title' => '搜索',
                ],
            ],

            'filters' => [
                'select'   => '選擇',
                'title'    => '應用過濾器',
                'save'     => '保存',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => '至少輸入2個字符...',
                        'no-results'        => '未找到結果...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => '清除所有',
                    'title'     => '自定義過濾器',
                ],

                'boolean-options' => [
                    'false' => '假',
                    'true'  => '真',
                ],

                'date-options' => [
                    'last-month'        => '上個月',
                    'last-six-months'   => '過去6個月',
                    'last-three-months' => '過去3個月',
                    'this-month'        => '本月',
                    'this-week'         => '本周',
                    'this-year'         => '今年',
                    'today'             => '今天',
                    'yesterday'         => '昨天',
                ],
            ],

            'table' => [
                'actions'              => '操作',
                'no-records-available' => '無可用記錄。',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => '同意',
                'disagree-btn' => '不同意',
                'message'      => '您確定要執行此操作嗎？',
                'title'        => '您確定嗎？',
            ],

            'delete' => [
                'agree-btn'    => '刪除',
                'disagree-btn' => '取消',
                'message'      => '您確定要刪除嗎？',
                'title'        => '確認刪除',
            ],

            'history' => [
                'title'           => '歷史預覽',
                'subtitle'        => '快速檢視您的更新和變更。',
                'close-btn'       => '關閉',
                'version-label'   => '版本',
                'date-time-label' => '日期/時間',
                'user-label'      => '用戶',
                'name-label'      => '關鍵',
                'old-value-label' => '舊值',
                'new-value-label' => '新值',
                'no-history'      => '未找到歷史記錄',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => '添加選定產品',
                'empty-info'    => '搜索詞條無可用產品。',
                'empty-title'   => '未找到產品',
                'product-image' => '產品圖片',
                'qty'           => ':qty 可用',
                'sku'           => 'SKU - :sku',
                'title'         => '選擇產品',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => '添加圖片',
                'ai-add-image-btn'  => '魔法AI',
                'ai-btn-info'       => '生成圖片',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => '僅允許圖片文件 (.jpeg, .jpg, .png, ..)。',
            ],

            'videos' => [
                'add-video-btn'     => '添加視頻',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => '僅允許視頻文件 (.mp4, .mov, .ogg ..)。',
            ],

            'files' => [
                'add-file-btn'      => '添加文件',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => '僅允許pdf文件',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => '魔法AI',

            'ai-generation' => [
                'apply'                  => '應用',
                'generate'               => '生成',
                'generated-content'      => '生成的內容',
                'generated-content-info' => 'AI內容可能會引起誤導。請在應用之前仔細檢查生成的內容。',
                'generating'             => '正在生成...',
                'prompt'                 => '提示',
                'title'                  => 'AI輔助',
                'model'                  => '模型',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 不審查',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],
];
