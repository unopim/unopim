<?php

return [

    'attribute' => [
        'calculated' => '計算屬性',
    ],

    'formula' => [
        'title'            => '公式',
        'label'            => '公式',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => '每次顯示商品、匯出或由 API 回傳時，該值都會由此公式推導而得。它不會被儲存，也無法匯入。',
        'reference-hint'   => '以代碼引用其他屬性。',
        'available'        => '可用函式',
        'examples'         => '範例',
        'example-concat'   => '連接兩個屬性，略過為空的屬性',
        'example-category' => '以 # 連接的分類標籤路徑',
        'example-fallback' => '當第一個屬性為空時改用第二個屬性',
        'read-only'        => '計算屬性',
        'read-only-hint'   => '此值由其他屬性計算而得，無法編輯。',

        'variables' => [
            'title'      => '可用變數',
            'sku'        => '商品的 SKU',
            'type'       => '商品類型',
            'status'     => '商品狀態',
            'family'     => '屬性族代碼',
            'parent'     => '變體的上層商品 SKU',
            'categories' => '已指派的分類代碼',
            'channel'    => '正在算繪的通路',
            'locale'     => '正在算繪的語言',
        ],

        'errors' => [
            'syntax'              => '無法解析公式：:detail',
            'unknown-variable'    => '公式中有未知的屬性或變數「:name」。',
            'disallowed-function' => '公式中不允許使用函式「:name」。',
            'disallowed-operator' => '公式中不允許使用運算子「:name」。',
            'reserved-variable'   => '名稱「:name」為保留名稱，不能用於公式。',
            'too-long'            => '公式長度不得超過 :max 個字元。',
            'empty'               => '計算屬性需要一個公式。',
            'circular'            => '此公式造成循環相依：:cycle',
            'referenced-by'       => '無法刪除此屬性，因為下列屬性的公式正在使用它：:attributes',
        ],
    ],

];
