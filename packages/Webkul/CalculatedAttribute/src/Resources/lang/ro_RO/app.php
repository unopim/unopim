<?php

return [

    'attribute' => [
        'calculated' => 'Calculat',
    ],

    'formula' => [
        'title'            => 'Formulă',
        'label'            => 'Formulă',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Valoarea este derivată din această formulă de fiecare dată când produsul este afișat, exportat sau returnat de API. Nu este niciodată stocată și nu poate fi importată.',
        'reference-hint'   => 'Faceți referire la alte atribute prin codul lor.',
        'available'        => 'Funcții disponibile',
        'examples'         => 'Exemple',
        'example-concat'   => 'Unește două atribute, sărind peste cele goale',
        'example-category' => 'Calea etichetelor de categorie, unită cu #',
        'example-fallback' => 'Folosește un al doilea atribut când primul este gol',
        'read-only'        => 'Calculat',
        'read-only-hint'   => 'Această valoare este calculată din alte atribute și nu poate fi editată.',

        'variables' => [
            'title'      => 'Variabile disponibile',
            'sku'        => 'codul produsului',
            'type'       => 'tipul produsului',
            'status'     => 'starea produsului',
            'family'     => 'codul familiei de atribute',
            'parent'     => 'codul produsului părinte, pentru variante',
            'categories' => 'codurile categoriilor atribuite',
            'channel'    => 'canalul pentru care se face randarea',
            'locale'     => 'limba pentru care se face randarea',
        ],

        'errors' => [
            'syntax'              => 'Formula nu a putut fi analizată: :detail',
            'unknown-variable'    => 'Atribut sau variabilă ":name" necunoscut în formulă.',
            'disallowed-function' => 'Funcția ":name" nu este permisă într-o formulă.',
            'disallowed-operator' => 'Operatorul ":name" nu este permis într-o formulă.',
            'reserved-variable'   => 'Numele ":name" este rezervat și nu poate fi folosit într-o formulă.',
            'too-long'            => 'Formula nu poate depăși :max caractere.',
            'empty'               => 'Un atribut calculat are nevoie de o formulă.',
            'circular'            => 'Formula creează o dependență circulară: :cycle',
            'referenced-by'       => 'Acest atribut nu poate fi șters deoarece este folosit de formula pentru: :attributes',
        ],
    ],

];
