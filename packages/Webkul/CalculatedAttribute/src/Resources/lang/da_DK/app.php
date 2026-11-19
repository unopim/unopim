<?php

return [

    'attribute' => [
        'calculated' => 'Beregnet',
    ],

    'formula' => [
        'title'            => 'Formel',
        'label'            => 'Formel',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => "Værdien udledes af denne formel, hver gang produktet vises, eksporteres eller returneres af API'et. Den gemmes aldrig og kan ikke importeres.",
        'reference-hint'   => 'Henvis til andre attributter med deres kode.',
        'available'        => 'Tilgængelige funktioner',
        'examples'         => 'Eksempler',
        'example-concat'   => 'Sammensæt to attributter, og spring de tomme over',
        'example-category' => 'Stien med kategorinavne, sammensat med #',
        'example-fallback' => 'Falde tilbage på en anden attribut, når den første er tom',
        'read-only'        => 'Beregnet',
        'read-only-hint'   => 'Denne værdi beregnes ud fra andre attributter og kan ikke redigeres.',

        'variables' => [
            'title'      => 'Tilgængelige variabler',
            'sku'        => 'produktets varenummer',
            'type'       => 'produkttypen',
            'status'     => 'produktstatus',
            'family'     => 'attributfamiliens kode',
            'parent'     => 'varenummeret på det overordnede produkt, for varianter',
            'categories' => 'de tildelte kategorikoder',
            'channel'    => 'den kanal der vises',
            'locale'     => 'det sprog der vises',
        ],

        'errors' => [
            'syntax'              => 'Formlen kunne ikke læses: :detail',
            'unknown-variable'    => 'Ukendt attribut eller variabel ":name" i formlen.',
            'disallowed-function' => 'Funktionen ":name" er ikke tilladt i en formel.',
            'disallowed-operator' => 'Operatoren ":name" er ikke tilladt i en formel.',
            'reserved-variable'   => 'Navnet ":name" er reserveret og kan ikke bruges i en formel.',
            'too-long'            => 'Formlen må højst være :max tegn lang.',
            'empty'               => 'En beregnet attribut kræver en formel.',
            'circular'            => 'Formlen skaber en cirkulær afhængighed: :cycle',
            'referenced-by'       => 'Denne attribut kan ikke slettes, fordi den bruges af formlen for: :attributes',
        ],
    ],

];
