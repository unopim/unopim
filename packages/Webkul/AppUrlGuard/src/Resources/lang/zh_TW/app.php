<?php

return [
    'warning' => [
        'title'           => '偵測到 APP_URL 不相符',
        'dismiss'         => '關閉',
        'lede-before'     => '您的前端資源（CSS、JS）已綁定到所設定的',
        'lede-after'      => '請將其更新為您正在使用的主機，否則樣式與指令碼將無法載入。',
        'configured-env'  => '已設定（.env）',
        'mismatch-tag'    => '不相符',
        'actual-browser'  => '實際（瀏覽器）',
        'in-use-tag'      => '使用中',
        'toggle-step'     => '切換步驟 :number',
        'step-1-title'    => '在您的 .env 檔案中更新 APP_URL',
        'step-1-hint'     => '開啟專案的 .env 檔案並替換 APP_URL 那一行。',
        'step-2-title'    => '清除應用程式快取',
        'step-2-hint'     => '在專案根目錄的終端機中執行此命令。',
        'copy'            => '複製',
        'copied'          => '已複製',
        'note-bold'       => '然後強制重新整理頁面',
        'note-rest'       => '讓瀏覽器重新載入更新後的資源。',
        'progress'        => '已完成 :done / :total 個步驟',
        'all-done'        => '全部完成',
        'powered-by'      => '技術支援',
        'open-source-by'  => '一個開源專案，來自',
        'copied-toast'    => '已複製到剪貼簿',
        'still-mismatch'  => 'APP_URL 仍然不相符。請更新 .env 並執行 "php artisan optimize:clear"。',
        'verify-failed'   => '無法驗證 APP_URL。請重新整理頁面。',
        'logged-out'      => '已登出：APP_URL 與目前的主機不相符。請在 .env 中更新 APP_URL 並執行 "php artisan optimize:clear"。',
    ],

    'log' => [
        'mismatch' => '偵測到 APP_URL 不相符',
        'hint'     => '將 .env 中的 APP_URL 更新為請求的 URL，然後執行：php artisan optimize:clear',
    ],
];
