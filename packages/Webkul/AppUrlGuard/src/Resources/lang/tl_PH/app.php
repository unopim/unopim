<?php

return [
    'warning' => [
        'title'           => 'May Natuklasang Hindi Pagtutugma sa APP_URL',
        'dismiss'         => 'I-dismiss',
        'lede-before'     => 'Ang iyong mga frontend asset (CSS, JS) ay naka-pin sa naka-configure na',
        'lede-after'      => 'I-update ito upang tumugma sa host na ginagamit mo, kung hindi ay hindi maglo-load ang mga istilo at script.',
        'configured-env'  => 'Naka-configure (.env)',
        'mismatch-tag'    => 'HINDI TUGMA',
        'actual-browser'  => 'Aktwal (browser)',
        'in-use-tag'      => 'GINAGAMIT',
        'toggle-step'     => 'I-toggle ang hakbang :number',
        'step-1-title'    => 'I-update ang APP_URL sa iyong .env file',
        'step-1-hint'     => 'Buksan ang .env ng proyekto at palitan ang linya ng APP_URL.',
        'step-2-title'    => 'I-clear ang cache ng application',
        'step-2-hint'     => 'Patakbuhin ito sa iyong terminal mula sa root ng proyekto.',
        'copy'            => 'Kopyahin',
        'copied'          => 'Nakopya',
        'note-bold'       => 'Pagkatapos ay mag-hard refresh sa pahina',
        'note-rest'       => 'upang muling i-load ng browser ang mga na-update na asset.',
        'progress'        => ':done ng :total na hakbang ang kumpleto',
        'all-done'        => 'Tapos na',
        'powered-by'      => 'Pinapatakbo ng',
        'open-source-by'  => 'Isang open-source na proyekto ng',
        'copied-toast'    => 'Nakopya sa clipboard',
        'still-mismatch'  => 'Hindi pa rin tumutugma ang APP_URL. I-update ang .env at patakbuhin ang "php artisan optimize:clear".',
        'verify-failed'   => 'Hindi ma-verify ang APP_URL. Mangyaring i-refresh ang pahina.',
        'logged-out'      => 'Naka-log out: Hindi tumutugma ang APP_URL sa kasalukuyang host. I-update ang APP_URL sa .env at patakbuhin ang "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'May Natuklasang Hindi Pagtutugma sa APP_URL',
        'hint'     => 'I-update ang APP_URL sa .env sa URL ng request, pagkatapos ay patakbuhin: php artisan optimize:clear',
    ],
];
