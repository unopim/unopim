<?php

return [

    'attribute' => [
        'calculated' => 'Izračunato',
    ],

    'formula' => [
        'title'            => 'Formula',
        'label'            => 'Formula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Vrijednost se izvodi iz ove formule svaki put kada se proizvod prikaže, izveze ili vrati putem API-ja. Nikada se ne pohranjuje i ne može se uvesti.',
        'reference-hint'   => 'Na druge atribute referirajte se njihovom šifrom.',
        'available'        => 'Dostupne funkcije',
        'examples'         => 'Primjeri',
        'example-concat'   => 'Spoji dva atributa preskačući prazne',
        'example-category' => 'Putanja naziva kategorija spojena znakom #',
        'example-fallback' => 'Koristi drugi atribut kada je prvi prazan',
        'read-only'        => 'Izračunato',
        'read-only-hint'   => 'Ova se vrijednost izračunava iz drugih atributa i ne može se uređivati.',

        'variables' => [
            'title'      => 'Dostupne varijable',
            'sku'        => 'šifra proizvoda',
            'type'       => 'vrsta proizvoda',
            'status'     => 'status proizvoda',
            'family'     => 'šifra obitelji atributa',
            'parent'     => 'šifra nadređenog proizvoda, za varijante',
            'categories' => 'šifre dodijeljenih kategorija',
            'channel'    => 'kanal za koji se prikazuje',
            'locale'     => 'jezik za koji se prikazuje',
        ],

        'errors' => [
            'syntax'              => 'Formulu nije bilo moguće raščlaniti: :detail',
            'unknown-variable'    => 'Nepoznat atribut ili varijabla „:name” u formuli.',
            'disallowed-function' => 'Funkcija „:name” nije dopuštena u formuli.',
            'disallowed-operator' => 'Operator „:name” nije dopušten u formuli.',
            'reserved-variable'   => 'Naziv „:name” je rezerviran i ne može se koristiti u formuli.',
            'too-long'            => 'Formula ne smije biti dulja od :max znakova.',
            'empty'               => 'Izračunati atribut treba formulu.',
            'circular'            => 'Formula stvara kružnu ovisnost: :cycle',
            'referenced-by'       => 'Ovaj atribut nije moguće izbrisati jer ga koristi formula atributa: :attributes',
        ],
    ],

];
