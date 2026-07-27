<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '發布',
            'info'     => '針對已發布、依語言區分內容的公開發佈層。',
            'settings' => [
                'title'                            => '發布設定',
                'enabled'                          => '已啟用',
                'base-url'                         => '基礎 URL',
                'cache-ttl'                        => '快取 TTL(秒)',
                'rate-limit'                       => '速率限制(請求數/分鐘)',
                'indexable'                        => '允許搜尋引擎索引',
                'enabled-hint'                     => '公開服務層的總開關。關閉時，每個公開護照 URL 都會回傳 404，護照選單也會被隱藏。',
                'base-url-hint'                    => '提供護照的公開位址，用於產生 QR 碼和可分享連結。留空則使用本站自身的網域。',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => '已算繪的公開護照在重新產生之前的快取時長。數值越高負載越低；數值越低越能更快反映編輯。',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => '單一訪客每分鐘允許的公開護照請求上限，超出後將被限流。',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => '允許搜尋引擎索引公開護照頁面。關閉後護照仍可透過連結存取，但會從搜尋結果中隱藏。',
                'gs1-passport-channel'             => 'GS1 Digital Link 護照通路',
                'gs1-passport-channel-hint'        => '當同一產品在多個通路發佈時，掃描的 GS1 條碼（/01/{gtin}）解析到的通路。留空則使用第一個已啟用的通路。',
                'gs1-passport-channel-placeholder' => '第一個已啟用的通路（自動）',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => '草稿',
            'published' => '已發布',
            'withdrawn' => '已撤回',
            'redacted'  => '已遮蔽(編輯隱藏)',
        ],
        'product-delete-blocked' => '該產品存在已發布的護照時無法刪除,請先撤回。',
        'channel-delete-blocked' => '該通路存在已發布的護照時無法刪除,請先撤回。',
    ],

    'public' => [
        '404' => [
            'heading' => '找不到護照資訊。',
            'notice'  => '此產品護照無法使用。它可能尚未發布，或連結不正確。',
        ],
        '429' => [
            'heading' => '請求過多,請稍後再試。',
            'notice'  => '您的請求過於頻繁。請稍候片刻再試。',
        ],
        'withdrawn' => [
            'heading' => '此護照資訊已不再提供。',
            'notice'  => '此記錄基於透明度予以保留,但已不再主動維護。',
        ],
    ],
];
