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
                    'super-attribute-not-found'                => '配置屬性代碼: \'%s\' 未找到或不屬於屬性家族: \'%s\'',
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
    ],
    'exporters' => [
        'products' => [
            'title'      => '產品',
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
        ],
    ],
    'job' => [
        'started'   => '任務已開始',
        'completed' => '任務已完成',
    ],
];
