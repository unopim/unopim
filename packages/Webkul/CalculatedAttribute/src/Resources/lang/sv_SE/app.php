<?php

return [

    'attribute' => [
        'calculated' => 'Beräknat',
    ],

    'formula' => [
        'title'            => 'Formel',
        'label'            => 'Formel',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Värdet härleds ur den här formeln varje gång produkten visas, exporteras eller returneras av API:et. Det lagras aldrig och kan inte importeras.',
        'reference-hint'   => 'Hänvisa till andra attribut med deras kod.',
        'available'        => 'Tillgängliga funktioner',
        'examples'         => 'Exempel',
        'example-concat'   => 'Slå ihop två attribut och hoppa över de tomma',
        'example-category' => 'Sökvägen med kategorinamn, sammanfogad med #',
        'example-fallback' => 'Falla tillbaka på ett andra attribut när det första är tomt',
        'read-only'        => 'Beräknat',
        'read-only-hint'   => 'Det här värdet beräknas från andra attribut och kan inte redigeras.',

        'variables' => [
            'title'      => 'Tillgängliga variabler',
            'sku'        => 'produktens artikelnummer',
            'type'       => 'produkttypen',
            'status'     => 'produktstatusen',
            'family'     => 'attributfamiljens kod',
            'parent'     => 'huvudproduktens artikelnummer, för varianter',
            'categories' => 'de tilldelade kategorikoderna',
            'channel'    => 'kanalen som visas',
            'locale'     => 'språket som visas',
        ],

        'errors' => [
            'syntax'              => 'Formeln kunde inte tolkas: :detail',
            'unknown-variable'    => 'Okänt attribut eller okänd variabel ":name" i formeln.',
            'disallowed-function' => 'Funktionen ":name" är inte tillåten i en formel.',
            'disallowed-operator' => 'Operatorn ":name" är inte tillåten i en formel.',
            'reserved-variable'   => 'Namnet ":name" är reserverat och kan inte användas i en formel.',
            'too-long'            => 'Formeln får inte vara längre än :max tecken.',
            'empty'               => 'Ett beräknat attribut behöver en formel.',
            'circular'            => 'Formeln skapar ett cirkulärt beroende: :cycle',
            'referenced-by'       => 'Det här attributet kan inte tas bort eftersom det används av formeln för: :attributes',
        ],
    ],

];
