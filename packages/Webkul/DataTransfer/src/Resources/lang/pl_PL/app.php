<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produkty',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Klucz URL: \'%s\' został już wygenerowany dla przedmiotu o SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Nieprawidłowa wartość dla kolumny rodziny atrybutów (rodzina atrybutów nie istnieje?)',
                    'invalid-type'                             => 'Typ produktu jest nieprawidłowy lub nieobsługiwany',
                    'sku-not-found'                            => 'Produkt o podanym SKU nie został znaleziony',
                    'super-attribute-not-found'                => 'Atrybut konfigurowalny z kodem: \'%s\' nie znaleziony lub nie należy do rodziny atrybutów: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Atrybuty konfigurowalne są wymagane do tworzenia modelu produktu',
                    'configurable-attributes-wrong-type'       => 'Tylko atrybuty typu, które nie są oparte na lokalizacji lub kanale, mogą być konfigurowalnymi atrybutami dla produktu konfigurowalnego',
                    'variant-configurable-attribute-not-found' => 'Wariant konfigurowalnego atrybutu: :code jest wymagany do stworzenia',
                    'not-unique-variant-product'               => 'Produkt z tymi samymi atrybutami konfigurowalnymi już istnieje.',
                    'channel-not-exist'                        => 'Ten kanał nie istnieje.',
                    'locale-not-in-channel'                    => 'Ta lokalizacja nie jest wybrana w kanale.',
                    'locale-not-exist'                         => 'Ta lokalizacja nie istnieje',
                    'not-unique-value'                         => 'Wartość :code musi być unikalna.',
                    'incorrect-family-for-variant'             => 'Rodzina musi być taka sama jak rodzina główna',
                    'parent-not-exist'                         => 'Rodzic nie istnieje.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategorie',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Nie można usunąć głównej kategorii powiązanej z kanałem',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanały',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanał o kodzie :code nie został znaleziony do usunięcia.',
                    'locale-not-found'         => 'Jeden lub więcej języków nie istnieje.',
                    'root-category-not-found'  => 'Kategoria główna nie istnieje.',
                    'currency-not-found'       => 'Jedna lub więcej walut nie istnieje.',
                    'invalid-locale'           => 'Język nie istnieje.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Produkty',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Klucz URL: \'%s\' został już wygenerowany dla przedmiotu o SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Nieprawidłowa wartość dla kolumny rodziny atrybutów (rodzina atrybutów nie istnieje?)',
                    'invalid-type'              => 'Typ produktu jest nieprawidłowy lub nieobsługiwany',
                    'sku-not-found'             => 'Produkt o podanym SKU nie został znaleziony',
                    'super-attribute-not-found' => 'Atrybut główny z kodem: \'%s\' nie znaleziony lub nie należy do rodziny atrybutów: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorie',
        ],
        'channels' => [
            'title' => 'Kanały',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolumny numer "%s" mają puste nagłówki.',
            'column-name-invalid'  => 'Nieprawidłowe nazwy kolumn: "%s".',
            'column-not-found'     => 'Brak wymaganych kolumn: %s.',
            'column-numbers'       => 'Liczba kolumn nie odpowiada liczbie wierszy w nagłówku.',
            'invalid-attribute'    => 'Nagłówek zawiera nieprawidłowe atrybuty: "%s".',
            'system'               => 'Wystąpił nieoczekiwany błąd systemowy.',
            'wrong-quotes'         => 'Zastosowano krzywe cudzysłowy zamiast prostych cudzysłowów.',
            'file-empty'           => 'Plik jest pusty lub nie zawiera wiersza nagłówkowego. Proszę przesłać prawidłowy plik z danymi.',
        ],
    ],
    'job' => [
        'started'   => 'Rozpoczęcie pracy',
        'completed' => 'Zakończenie pracy',
    ],
];
