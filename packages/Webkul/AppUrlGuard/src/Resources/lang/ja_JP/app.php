<?php

return [
    'warning' => [
        'title'           => 'APP_URL の不一致を検出しました',
        'dismiss'         => '閉じる',
        'lede-before'     => 'フロントエンドのアセット (CSS、JS) は設定された値に固定されています',
        'lede-after'      => '使用しているホストに合わせて更新してください。そうしないとスタイルやスクリプトが読み込まれません。',
        'configured-env'  => '設定値 (.env)',
        'mismatch-tag'    => '不一致',
        'actual-browser'  => '実際の値 (ブラウザ)',
        'in-use-tag'      => '使用中',
        'toggle-step'     => 'ステップ :number を切り替え',
        'step-1-title'    => '.env ファイルの APP_URL を更新する',
        'step-1-hint'     => 'プロジェクトの .env を開き、APP_URL の行を置き換えてください。',
        'step-2-title'    => 'アプリケーションのキャッシュをクリアする',
        'step-2-hint'     => 'プロジェクトのルートでターミナルからこれを実行してください。',
        'copy'            => 'コピー',
        'copied'          => 'コピーしました',
        'note-bold'       => 'その後、ページをハードリフレッシュしてください',
        'note-rest'       => 'ブラウザが更新されたアセットを再読み込みするようにします。',
        'progress'        => ':total ステップ中 :done 完了',
        'all-done'        => 'すべて完了',
        'powered-by'      => '提供',
        'open-source-by'  => 'オープンソースプロジェクト提供元',
        'copied-toast'    => 'クリップボードにコピーしました',
        'still-mismatch'  => 'APP_URL がまだ一致しません。.env を更新して "php artisan optimize:clear" を実行してください。',
        'verify-failed'   => 'APP_URL を確認できませんでした。ページを再読み込みしてください。',
        'logged-out'      => 'ログアウトしました: APP_URL が現在のホストと一致しません。.env の APP_URL を更新して "php artisan optimize:clear" を実行してください。',
    ],

    'log' => [
        'mismatch' => 'APP_URL の不一致を検出しました',
        'hint'     => '.env の APP_URL をリクエストの URL に更新し、次を実行してください: php artisan optimize:clear',
    ],
];
