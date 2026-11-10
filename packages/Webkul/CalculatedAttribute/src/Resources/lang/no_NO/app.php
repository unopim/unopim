<?php

return [

    'attribute' => [
        'calculated' => 'Beregnet',
    ],

    'formula' => [
        'title'            => 'Formel',
        'label'            => 'Formel',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Verdien utledes av denne formelen hver gang produktet vises, eksporteres eller returneres av API-et. Den lagres aldri og kan ikke importeres.',
        'reference-hint'   => 'Vis til andre attributter med koden deres.',
        'available'        => 'Tilgjengelige funksjoner',
        'examples'         => 'Eksempler',
        'example-concat'   => 'Slå sammen to attributter og hoppe over de tomme',
        'example-category' => 'Stien med kategorinavn, satt sammen med #',
        'example-fallback' => 'Falle tilbake på en annen attributt når den første er tom',
        'read-only'        => 'Beregnet',
        'read-only-hint'   => 'Denne verdien beregnes fra andre attributter og kan ikke redigeres.',

        'variables' => [
            'title'      => 'Tilgjengelige variabler',
            'sku'        => 'produktets varenummer',
            'type'       => 'produkttypen',
            'status'     => 'produktstatusen',
            'family'     => 'koden til attributtfamilien',
            'parent'     => 'varenummeret til hovedproduktet, for varianter',
            'categories' => 'de tildelte kategorikodene',
            'channel'    => 'kanalen som vises',
            'locale'     => 'språket som vises',
        ],

        'errors' => [
            'syntax'              => 'Formelen kunne ikke tolkes: :detail',
            'unknown-variable'    => 'Ukjent attributt eller variabel ":name" i formelen.',
            'disallowed-function' => 'Funksjonen ":name" er ikke tillatt i en formel.',
            'disallowed-operator' => 'Operatoren ":name" er ikke tillatt i en formel.',
            'reserved-variable'   => 'Navnet ":name" er reservert og kan ikke brukes i en formel.',
            'too-long'            => 'Formelen kan ikke være lengre enn :max tegn.',
            'empty'               => 'En beregnet attributt trenger en formel.',
            'circular'            => 'Formelen skaper en sirkulær avhengighet: :cycle',
            'referenced-by'       => 'Denne attributten kan ikke slettes fordi den brukes av formelen til: :attributes',
        ],
    ],

];
