<?php

return [
    'warning' => [
        'title'           => 'APP_URL-ristiriita havaittu',
        'dismiss'         => 'Hylkää',
        'lede-before'     => 'Frontend-resurssisi (CSS, JS) on kiinnitetty määritettyyn',
        'lede-after'      => 'Päivitä se vastaamaan käyttämääsi isäntänimeä, muuten tyylit ja skriptit eivät lataudu.',
        'configured-env'  => 'Määritetty (.env)',
        'mismatch-tag'    => 'RISTIRIITA',
        'actual-browser'  => 'Todellinen (selain)',
        'in-use-tag'      => 'KÄYTÖSSÄ',
        'toggle-step'     => 'Vaihda vaihe :number',
        'step-1-title'    => 'Päivitä APP_URL .env-tiedostossasi',
        'step-1-hint'     => 'Avaa projektin .env ja korvaa APP_URL-rivi.',
        'step-2-title'    => 'Tyhjennä sovelluksen välimuisti',
        'step-2-hint'     => 'Suorita tämä terminaalissa projektin juurikansiosta.',
        'copy'            => 'Kopioi',
        'copied'          => 'Kopioitu',
        'note-bold'       => 'Päivitä sitten sivu kovalla päivityksellä',
        'note-rest'       => 'jotta selain lataa päivitetyt resurssit uudelleen.',
        'progress'        => ':done / :total vaihetta valmiina',
        'all-done'        => 'Kaikki valmista',
        'powered-by'      => 'Tarjoaa',
        'open-source-by'  => 'Avoimen lähdekoodin projekti, tekijä',
        'copied-toast'    => 'Kopioitu leikepöydälle',
        'still-mismatch'  => 'APP_URL ei vieläkään täsmää. Päivitä .env ja suorita "php artisan optimize:clear".',
        'verify-failed'   => 'APP_URL:ää ei voitu vahvistaa. Päivitä sivu.',
        'logged-out'      => 'Kirjauduttu ulos: APP_URL ei vastaa nykyistä isäntänimeä. Päivitä APP_URL .env-tiedostossa ja suorita "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'APP_URL-ristiriita havaittu',
        'hint'     => 'Päivitä APP_URL .env-tiedostossa pyynnön URL-osoitteeksi ja suorita sitten: php artisan optimize:clear',
    ],
];
