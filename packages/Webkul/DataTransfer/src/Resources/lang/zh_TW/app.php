<?php

return [
    'importers' => [
        'products' => [
            'title'      => '產品',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL 鍵: \'%s\' 已經為 SKU: \'%s\' 的項目生成。',
                    'invalid-attribute-family'                 => '屬性家族的列值無效 (屬性家族不存在?)',
                    'invalid-type'                             => '產品類型無效或不支持',
                    'sku-not-found'                            => '找不到指定的 SKU 的產品',
                    'super-attribute-not-found'                => '配置屬性代碼: \'%s\' 未找到或不屬於屬性家族: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => '配置屬性需要用於建立產品模型',
                    'configurable-attributes-wrong-type'       => '僅支持類型選擇屬性，不基於位置或渠道，可以作為配置屬性',
                    'variant-configurable-attribute-not-found' => '變量配置屬性: :code 需要建立',
                    'not-unique-variant-product'               => '已存在具有相同配置屬性的產品。',
                    'channel-not-exist'                        => '此頻道不存在。',
                    'locale-not-in-channel'                    => '此位置未在頻道中選擇。',
                    'locale-not-exist'                         => '此位置不存在',
                    'not-unique-value'                         => '值 :code 必須唯一。',
                    'incorrect-family-for-variant'             => '家族應與主家族相同',
                    'parent-not-exist'                         => '父類別不存在。',
                ],
            ],
        ],
        'categories' => [
            'title'      => '分類',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => '無法刪除與渠道相關的根分類',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => '分類欄位',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '分類欄位代碼 :code 已被使用。',
                    'code_not_found_to_delete' => '找不到要刪除的分類欄位代碼。',
                ],
            ],
        ],
        'attributes' => [
            'title'      => '屬性',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '屬性代碼 :code 已被使用。',
                    'code_not_found_to_delete'             => '未找到要刪除的屬性代碼。',
                    'code_is_system_and_cannot_be_deleted' => '無法刪除系統屬性。',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => '產品關聯',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => '「%s」欄位為必填項目。',
                    'self-link-not-allowed'       => '產品「%s」不能與自身建立關聯。',
                    'sku-not-found'               => '找不到 SKU 為「%s」的產品。',
                    'related-sku-not-found'       => '找不到 SKU 為「%s」的關聯產品。',
                    'association-type-not-found'  => '關聯類型「%s」不存在或未啟用。',
                    'invalid-field-value'         => '關聯欄位提供了無效的值。',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => '屬性組',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '屬性組代碼 :code 已被使用。',
                    'code_not_found_to_delete'             => '未找到要刪除的屬性組代碼。',
                    'code_is_system_and_cannot_be_deleted' => '無法刪除系統屬性組。',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => '屬性族',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '屬性族代碼 :code 已被使用。',
                    'code_not_found_to_delete' => '未找到要刪除的屬性族代碼。',
                    'invalid-attribute-group'  => '屬性組“:code”不存在。',
                    'invalid-attribute'        => '屬性“:code”不存在。',
                    'invalid-channel'          => '渠道“:code”不存在。',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => '屬性選項',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '屬性選項代碼 :code 已被使用。',
                    'code_not_found_to_delete' => '未找到要刪除的屬性選項代碼。',
                    'locale-not-exist'         => '區域設置“:code”不存在。',
                    'invalid-attribute'        => '屬性“:code”不存在。',
                ],
            ],
        ],
        'locales' => [
            'title'      => '語言',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => '語言代碼 \'%s\' 已在此批次中匯入。',
                    'code-not-found-to-delete'    => '系統中找不到代碼為 \'%s\' 的語言。',
                    'invalid-status'              => '狀態必須為 0 或 1（或留空表示預設啟用）。',
                    'channel-related-locale-root' => '無法刪除代碼為 :code 的語言，因為它與某個渠道相關聯。',
                ],
            ],
        ],
        'channels' => [
            'title'      => '渠道',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => '未找到代碼為 :code 的渠道，無法刪除。',
                    'locale-not-found'         => '一個或多個語言不存在。',
                    'root-category-not-found'  => '根分類不存在。',
                    'currency-not-found'       => '一個或多個貨幣不存在。',
                    'invalid-locale'           => '語言不存在。',
                ],
            ],
        ],
        'currencies' => [
            'title'   => '貨幣',
            'filters' => [
                'status' => '狀態',
                'enable' => '啟用',
                'all'    => '全部',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => '狀態必須為 0 或 1（或留空表示預設啟用）。',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => '角色',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => '用戶',
            'filters' => [
                'status' => '狀態',
                'active' => '活躍',
                'all'    => '全部',
            ],
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
        'export-too-large' => '此匯出過大，無法執行：預計 :rows 列 × :columns 欄（~:estimated）超出可用空間（~:available）。請選擇較少的渠道/語系（與屬性）以縮小匯出範圍，然後重試。',
        'fields'           => [
            'file-format'         => '檔案格式',
            'with-media'          => '包含媒體',
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
            'file-path'      => '文件路徑',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => '狀態',
            'enable'         => '啟用',
            'all'            => '全部',
        ],
        'products' => [
            'title'              => '產品',
            'invalid-locales'    => '並非所有所選語言環境都適用於所選管道。',
            'invalid-currencies' => '並非所有所選貨幣都適用於所選管道。',
            'filters'            => [
                'channels'             => '管道',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => '貨幣',
                'currencies-info'      => '價格屬性會依每個所選貨幣匯出。留空則匯出所有管道貨幣。',
                'locales'              => '語言環境',
                'locales-info'         => '可在地化屬性會依每個所選語言環境匯出一次。留空則匯出所有管道語言環境。',
                'attributes'           => '屬性',
                'attributes-info'      => '僅匯出所選屬性。留空則匯出該屬性族中的所有屬性。',
                'attribute-families'   => '屬性族',
                'categories'           => '類別',
                'completeness'         => '完整度',
                'completeness-options' => [
                    'none'         => '無完整度條件',
                    'at-least-one' => '在至少一個所選語言環境中完整',
                    'all'          => '在所有所選語言環境中完整',
                ],
                'time-condition' => '時間條件',
                'time-options'   => [
                    'none'              => '無日期條件',
                    'last-n-days'       => '過去 N 天內更新的產品',
                    'between-dates'     => '在兩個日期之間更新的產品',
                    'since-last-export' => '自上次匯出以來更新的產品',
                ],
                'time-value'     => '天數',
                'time-date'      => '開始日期',
                'time-date-end'  => '結束日期',
                'status'         => '狀態',
                'status-options' => [
                    'enable'  => '啟用',
                    'disable' => '停用',
                    'all'     => '全部',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => '識別碼',
                'identifiers-info' => '每行貼上一個 SKU / 識別碼，僅匯出這些產品。留空則匯出所有產品。',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL 鍵: \'%s\' 已經為 SKU: \'%s\' 的項目生成。',
                    'invalid-attribute-family'  => '屬性家族的列值無效 (屬性家族不存在?)',
                    'invalid-type'              => '產品類型無效或不支持',
                    'sku-not-found'             => '找不到指定的 SKU 的產品',
                    'super-attribute-not-found' => '配置屬性代碼: \'%s\' 未找到或不屬於屬性家族: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => '分類',
        ],
        'category-fields' => [
            'title' => '分類欄位',
        ],
        'attributes' => [
            'title' => '屬性',
        ],
        'product-associations' => [
            'title' => '產品關聯',
        ],
        'attribute-groups' => [
            'title' => '屬性組',
        ],
        'attribute-families' => [
            'title' => '屬性族',
        ],
        'attribute-options' => [
            'title' => '屬性選項',
        ],
        'locales' => [
            'title' => '語言',
        ],
        'channels' => [
            'title' => '渠道',
        ],
        'currencies' => [
            'title' => '貨幣',
        ],
        'roles' => [
            'title' => '角色',
        ],
        'users' => [
            'title'   => '用戶',
            'filters' => [
                'status' => '狀態',
                'active' => '啟用',
                'all'    => '全部',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => '列數 "%s" 的標題為空。',
            'column-name-invalid'  => '無效的列名稱: "%s".',
            'column-not-found'     => '找不到需要的列: %s.',
            'column-numbers'       => '列數量不匹配標題的行數。',
            'invalid-attribute'    => '標題包含無效屬性: "%s".',
            'system'               => '發生了一個意外的系統錯誤。',
            'wrong-quotes'         => '使用了弓形引號而不是直引號。',
            'file-empty'           => '檔案為空或不包含標題列。請上傳包含資料的有效檔案。',
        ],
    ],
    'job' => [
        'started'   => '任務已開始',
        'completed' => '任務已完成',
    ],
];
