<?php

return [

    'attribute' => [
        'calculated' => 'Berechnet',
    ],

    'formula' => [
        'title'            => 'Formel',
        'label'            => 'Formel',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Der Wert wird bei jeder Anzeige, jedem Export und jedem API-Aufruf aus dieser Formel ermittelt. Er wird nicht gespeichert und kann nicht importiert werden.',
        'reference-hint'   => 'Andere Attribute über ihren Code ansprechen.',
        'available'        => 'Verfügbare Funktionen',
        'examples'         => 'Beispiele',
        'example-concat'   => 'Zwei Attribute verbinden und leere dabei überspringen',
        'example-category' => 'Der Kategoriepfad als Bezeichnungen, verbunden mit #',
        'example-fallback' => 'Auf ein zweites Attribut ausweichen, wenn das erste leer ist',
        'read-only'        => 'Berechnet',
        'read-only-hint'   => 'Dieser Wert wird aus anderen Attributen berechnet und kann nicht bearbeitet werden.',

        'variables' => [
            'title'      => 'Verfügbare Variablen',
            'sku'        => 'die Artikelnummer des Produkts',
            'type'       => 'der Produkttyp',
            'status'     => 'der Produktstatus',
            'family'     => 'der Code der Attributfamilie',
            'parent'     => 'die Artikelnummer des übergeordneten Produkts, bei Varianten',
            'categories' => 'die zugewiesenen Kategoriecodes',
            'channel'    => 'der Kanal, für den gerendert wird',
            'locale'     => 'die Sprache, für die gerendert wird',
        ],

        'errors' => [
            'syntax'              => 'Die Formel konnte nicht gelesen werden: :detail',
            'unknown-variable'    => 'Unbekanntes Attribut oder unbekannte Variable „:name“ in der Formel.',
            'disallowed-function' => 'Die Funktion „:name“ ist in einer Formel nicht erlaubt.',
            'disallowed-operator' => 'Der Operator „:name“ ist in einer Formel nicht erlaubt.',
            'reserved-variable'   => 'Der Name „:name“ ist reserviert und darf in einer Formel nicht verwendet werden.',
            'too-long'            => 'Die Formel darf höchstens :max Zeichen lang sein.',
            'empty'               => 'Ein berechnetes Attribut braucht eine Formel.',
            'circular'            => 'Die Formel erzeugt eine zirkuläre Abhängigkeit: :cycle',
            'referenced-by'       => 'Dieses Attribut kann nicht gelöscht werden, weil es in der Formel folgender Attribute verwendet wird: :attributes',
        ],
    ],

];
