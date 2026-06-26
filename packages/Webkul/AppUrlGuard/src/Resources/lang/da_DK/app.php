<?php

return [
    'warning' => [
        'title'           => 'Uoverensstemmelse i APP_URL registreret',
        'dismiss'         => 'Afvis',
        'lede-before'     => 'Dine frontend-aktiver (CSS, JS) er låst til den konfigurerede',
        'lede-after'      => 'Opdater den, så den matcher den vært, du bruger, ellers indlæses typografier og scripts ikke.',
        'configured-env'  => 'Konfigureret (.env)',
        'mismatch-tag'    => 'UOVERENSSTEMMELSE',
        'actual-browser'  => 'Faktisk (browser)',
        'in-use-tag'      => 'I BRUG',
        'toggle-step'     => 'Skift trin :number',
        'step-1-title'    => 'Opdater APP_URL i din .env-fil',
        'step-1-hint'     => 'Åbn projektets .env, og erstat APP_URL-linjen.',
        'step-2-title'    => 'Ryd programmets cache',
        'step-2-hint'     => 'Kør dette i din terminal fra projektets rod.',
        'copy'            => 'Kopiér',
        'copied'          => 'Kopieret',
        'note-bold'       => 'Genindlæs derefter siden helt',
        'note-rest'       => 'så browseren genindlæser de opdaterede aktiver.',
        'progress'        => ':done af :total trin fuldført',
        'all-done'        => 'Alt er færdigt',
        'powered-by'      => 'Drevet af',
        'open-source-by'  => 'Et open source-projekt af',
        'copied-toast'    => 'Kopieret til udklipsholder',
        'still-mismatch'  => 'APP_URL matcher stadig ikke. Opdater .env, og kør "php artisan optimize:clear".',
        'verify-failed'   => 'Kunne ikke verificere APP_URL. Genindlæs venligst siden.',
        'logged-out'      => 'Logget ud: APP_URL matcher ikke den aktuelle vært. Opdater APP_URL i .env, og kør "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Uoverensstemmelse i APP_URL registreret',
        'hint'     => 'Opdater APP_URL i .env til anmodningens URL, og kør derefter: php artisan optimize:clear',
    ],
];
