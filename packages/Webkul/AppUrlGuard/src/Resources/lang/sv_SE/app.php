<?php

return [
    'warning' => [
        'title'           => 'APP_URL-avvikelse upptäckt',
        'dismiss'         => 'Stäng',
        'lede-before'     => 'Dina frontend-resurser (CSS, JS) är låsta till det konfigurerade',
        'lede-after'      => 'Uppdatera det så att det matchar värden du använder, annars laddas inte stilarna och skripten.',
        'configured-env'  => 'Konfigurerad (.env)',
        'mismatch-tag'    => 'AVVIKELSE',
        'actual-browser'  => 'Faktisk (webbläsare)',
        'in-use-tag'      => 'ANVÄNDS',
        'toggle-step'     => 'Växla steg :number',
        'step-1-title'    => 'Uppdatera APP_URL i din .env-fil',
        'step-1-hint'     => 'Öppna projektets .env och ersätt APP_URL-raden.',
        'step-2-title'    => 'Rensa applikationens cache',
        'step-2-hint'     => 'Kör detta i din terminal från projektets rot.',
        'copy'            => 'Kopiera',
        'copied'          => 'Kopierad',
        'note-bold'       => 'Gör sedan en hård uppdatering av sidan',
        'note-rest'       => 'så att webbläsaren laddar om de uppdaterade resurserna.',
        'progress'        => ':done av :total steg slutförda',
        'all-done'        => 'Klart',
        'powered-by'      => 'Drivs av',
        'open-source-by'  => 'Ett projekt med öppen källkod av',
        'copied-toast'    => 'Kopierat till urklipp',
        'still-mismatch'  => 'APP_URL matchar fortfarande inte. Uppdatera .env och kör "php artisan optimize:clear".',
        'verify-failed'   => 'Kunde inte verifiera APP_URL. Vänligen uppdatera sidan.',
        'logged-out'      => 'Utloggad: APP_URL matchar inte den aktuella värden. Uppdatera APP_URL i .env och kör "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'APP_URL-avvikelse upptäckt',
        'hint'     => 'Uppdatera APP_URL i .env till begärans URL och kör sedan: php artisan optimize:clear',
    ],
];
