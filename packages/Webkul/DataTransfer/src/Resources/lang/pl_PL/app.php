<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Produkty',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'Klucz URL: \'%s\' został już wygenerowany dla przedmiotu o SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Nieprawidłowa wartość dla kolumny rodziny atrybutów (rodzina atrybutów nie istnieje?)',
                    'invalid-type' => 'Typ produktu jest nieprawidłowy lub nieobsługiwany',
                    'sku-not-found' => 'Produkt o podanym SKU nie został znaleziony',
                    'super-attribute-not-found' => 'Atrybut konfigurowalny z kodem: \'%s\' nie znaleziony lub nie należy do rodziny atrybutów: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Atrybuty konfigurowalne są wymagane do tworzenia modelu produktu',
                    'configurable-attributes-wrong-type' => 'Tylko atrybuty typu, które nie są oparte na lokalizacji lub kanale, mogą być konfigurowalnymi atrybutami dla produktu konfigurowalnego',
                    'variant-configurable-attribute-not-found' => 'Wariant konfigurowalnego atrybutu: :code jest wymagany do stworzenia',
                    'not-unique-variant-product' => 'Produkt z tymi samymi atrybutami konfigurowalnymi już istnieje.',
                    'channel-not-exist' => 'Ten kanał nie istnieje.',
                    'locale-not-in-channel' => 'Ta lokalizacja nie jest wybrana w kanale.',
                    'locale-not-exist' => 'Ta lokalizacja nie istnieje',
                    'not-unique-value' => 'Wartość :code musi być unikalna.',
                    'incorrect-family-for-variant' => 'Rodzina musi być taka sama jak rodzina główna',
                    'parent-not-exist' => 'Rodzic nie istnieje.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorie',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Nie można usunąć głównej kategorii powiązanej z kanałem',
                ],
            ],
        ],
        'attributes' => [
            'title' => 'Attributes',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute cannot be deleted.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute group code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute group code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute group cannot be deleted.',
                ],
            ],
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute family code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute family code not found for deletion.',
                    'invalid-attribute-group' => 'Attribute group ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
                    'invalid-channel' => 'Channel ":code" does not exist.',
                ],
            ],
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute option code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute option code not found for deletion.',
                    'locale-not-exist' => 'Locale ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title' => 'Produkty',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'Klucz URL: \'%s\' został już wygenerowany dla przedmiotu o SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Nieprawidłowa wartość dla kolumny rodziny atrybutów (rodzina atrybutów nie istnieje?)',
                    'invalid-type' => 'Typ produktu jest nieprawidłowy lub nieobsługiwany',
                    'sku-not-found' => 'Produkt o podanym SKU nie został znaleziony',
                    'super-attribute-not-found' => 'Atrybut główny z kodem: \'%s\' nie znaleziony lub nie należy do rodziny atrybutów: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorie',
        ],
        'attributes' => [
            'title' => 'Attributes',
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolumny numer "%s" mają puste nagłówki.',
            'column-name-invalid' => 'Nieprawidłowe nazwy kolumn: "%s".',
            'column-not-found' => 'Brak wymaganych kolumn: %s.',
            'column-numbers' => 'Liczba kolumn nie odpowiada liczbie wierszy w nagłówku.',
            'invalid-attribute' => 'Nagłówek zawiera nieprawidłowe atrybuty: "%s".',
            'system' => 'Wystąpił nieoczekiwany błąd systemowy.',
            'wrong-quotes' => 'Zastosowano krzywe cudzysłowy zamiast prostych cudzysłowów.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'Rozpoczęcie pracy',
        'completed' => 'Zakończenie pracy',
    ],
];
