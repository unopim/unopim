<?php

return [

    'attribute' => [
        'calculated' => 'Obliczany',
    ],

    'formula' => [
        'title'            => 'Formuła',
        'label'            => 'Formuła',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Wartość jest wyliczana z tej formuły przy każdym wyświetleniu produktu, eksporcie i wywołaniu API. Nigdy nie jest zapisywana i nie można jej zaimportować.',
        'reference-hint'   => 'Odwołuj się do innych atrybutów przez ich kod.',
        'available'        => 'Dostępne funkcje',
        'examples'         => 'Przykłady',
        'example-concat'   => 'Połącz dwa atrybuty, pomijając puste',
        'example-category' => 'Ścieżka nazw kategorii połączona znakiem #',
        'example-fallback' => 'Użyj drugiego atrybutu, gdy pierwszy jest pusty',
        'read-only'        => 'Obliczany',
        'read-only-hint'   => 'Ta wartość jest obliczana z innych atrybutów i nie można jej edytować.',

        'variables' => [
            'title'      => 'Dostępne zmienne',
            'sku'        => 'SKU produktu',
            'type'       => 'typ produktu',
            'status'     => 'status produktu',
            'family'     => 'kod rodziny atrybutów',
            'parent'     => 'SKU produktu nadrzędnego, dla wariantów',
            'categories' => 'kody przypisanych kategorii',
            'channel'    => 'kanał, dla którego następuje renderowanie',
            'locale'     => 'język, dla którego następuje renderowanie',
        ],

        'errors' => [
            'syntax'              => 'Nie udało się przetworzyć formuły: :detail',
            'unknown-variable'    => 'Nieznany atrybut lub zmienna „:name” w formule.',
            'disallowed-function' => 'Funkcja „:name” nie jest dozwolona w formule.',
            'disallowed-operator' => 'Operator „:name” nie jest dozwolony w formule.',
            'reserved-variable'   => 'Nazwa „:name” jest zarezerwowana i nie może być użyta w formule.',
            'too-long'            => 'Formuła nie może być dłuższa niż :max znaków.',
            'empty'               => 'Atrybut obliczany wymaga formuły.',
            'circular'            => 'Formuła tworzy zależność cykliczną: :cycle',
            'referenced-by'       => 'Tego atrybutu nie można usunąć, ponieważ używa go formuła: :attributes',
        ],
    ],

];
