<?php

return [
    'warning' => [
        'title'           => 'APP_URL-Konflikt erkannt',
        'dismiss'         => 'Verwerfen',
        'lede-before'     => 'Ihre Frontend-Assets (CSS, JS) sind an den konfigurierten Wert gebunden',
        'lede-after'      => 'Aktualisieren Sie ihn so, dass er mit dem von Ihnen verwendeten Host übereinstimmt, andernfalls werden die Stile und Skripte nicht geladen.',
        'configured-env'  => 'Konfiguriert (.env)',
        'mismatch-tag'    => 'KONFLIKT',
        'actual-browser'  => 'Tatsächlich (Browser)',
        'in-use-tag'      => 'IN VERWENDUNG',
        'toggle-step'     => 'Schritt :number umschalten',
        'step-1-title'    => 'Aktualisieren Sie APP_URL in Ihrer .env-Datei',
        'step-1-hint'     => 'Öffnen Sie die .env des Projekts und ersetzen Sie die APP_URL-Zeile.',
        'step-2-title'    => 'Leeren Sie den Anwendungscache',
        'step-2-hint'     => 'Führen Sie dies in Ihrem Terminal im Projektstammverzeichnis aus.',
        'copy'            => 'Kopieren',
        'copied'          => 'Kopiert',
        'note-bold'       => 'Aktualisieren Sie anschließend die Seite vollständig',
        'note-rest'       => 'damit der Browser die aktualisierten Assets neu lädt.',
        'progress'        => ':done von :total Schritten abgeschlossen',
        'all-done'        => 'Alles erledigt',
        'powered-by'      => 'Bereitgestellt von',
        'open-source-by'  => 'Ein Open-Source-Projekt von',
        'copied-toast'    => 'In die Zwischenablage kopiert',
        'still-mismatch'  => 'APP_URL stimmt immer noch nicht überein. Aktualisieren Sie .env und führen Sie "php artisan optimize:clear" aus.',
        'verify-failed'   => 'APP_URL konnte nicht überprüft werden. Bitte aktualisieren Sie die Seite.',
        'logged-out'      => 'Abgemeldet: APP_URL stimmt nicht mit dem aktuellen Host überein. Aktualisieren Sie APP_URL in .env und führen Sie "php artisan optimize:clear" aus.',
    ],

    'log' => [
        'mismatch' => 'APP_URL-Konflikt erkannt',
        'hint'     => 'Aktualisieren Sie APP_URL in der .env auf die Anfrage-URL und führen Sie dann aus: php artisan optimize:clear',
    ],
];
