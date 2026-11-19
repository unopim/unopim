<?php

return [
    'acl' => [
        'variant-structures' => '變體結構',
    ],

    'datagrid' => [
        'copy-of-slug'                  => '複本-:value',
        'copy-of'                       => ':value 的複本',
        'variant-already-exist-message' => '具有相同屬性選項的變體已存在。',
    ],

    'response' => [
        'product-can-not-be-copied' => '類型為 :type 的產品無法複製',
    ],

    'type'     => [
        'configurable'  => '可配置的',
        'simple'        => '簡單的',
        'variant-group' => '變體組',
        'variant_group' => '變體組',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => '偵測到範本循環引用：:path',
            'currency-not-allowed'    => '只有價格屬性可以接受貨幣，但 ":attribute" 不是價格屬性。',
            'currency-not-enabled'    => '範本中使用的貨幣 ":currency" 未在任何頻道中啟用。',
            'currency-not-in-channel' => '貨幣 ":currency" 未在頻道 ":channel" 中啟用。',
            'filter-failed'           => '無法將篩選器 ":filter" 套用至 ":attribute" 的值。',
            'filter-not-supported'    => '篩選器 ":filter" 無法套用至屬性 ":attribute"。',
            'format-date-position'    => 'format_date 篩選器必須是 ":expression" 中的第一個篩選器。',
            'inside-html-tag'         => '範本運算式不能放在 HTML 標籤或屬性（例如連結和圖片）內。',
            'invalid-filter-argument' => '":expression" 中的篩選器 ":filter" 缺少參數或參數無效。',
            'invalid-reference'       => '儲存在 ":attribute" 中的範本無效，無法轉譯。',
            'invalid-syntax'          => '範本運算式 ":expression" 無效。請使用屬性代碼，後接選用的篩選器，例如：name | upper',
            'max-depth'               => '範本引用在 ":attribute" 處的巢狀層級超過 :max 層。',
            'missing-value'           => '屬性 ":attribute" 在此頻道和語言下沒有值。',
            'not-in-family'           => '範本中使用的屬性 ":attribute" 不屬於該產品的屬性族。',
            'output-too-large'        => '渲染後的範本超過 :max 位元組，已被截斷。',
            'self-reference'          => '範本不能引用其自身的屬性 ":attribute"。',
            'single-brace'            => '發現單大括號中的 ":text"。範本運算式需要使用雙大括號。',
            'source-too-long'         => '範本最多只能有 :max 位元組。',
            'too-complex'             => '範本過於複雜，無法檢查。請縮短或簡化範本。',
            'too-many-expressions'    => '一個範本最多只能包含 :max 個運算式。',
            'too-many-filters'        => '範本運算式 ":expression" 最多只能使用 :max 個篩選器。',
            'unclosed-expression'     => '範本運算式已開啟但未關閉。',
            'unknown-attribute'       => '範本中使用的屬性 ":attribute" 不存在。',
            'unknown-currency'        => '範本中使用的貨幣 ":currency" 不存在。',
            'unknown-filter'          => '不支援篩選器 ":filter"。可用的篩選器：:filters',
            'unsupported-attribute'   => '屬性 ":attribute" 的類型不受支援，因此無法在範本中使用。',
        ],
    ],
];
