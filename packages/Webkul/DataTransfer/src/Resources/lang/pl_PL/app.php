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
        'export-too-large' => 'Ten eksport jest zbyt duży, aby go uruchomić: szacunkowo :rows wierszy × :columns kolumn (~:estimated) przekracza dostępne miejsce (~:available). Zawęź eksport, wybierając mniej kanałów/lokalizacji (i atrybutów), i spróbuj ponownie.',
        'fields'           => [
            'file-format'         => 'Format pliku',
            'with-media'          => 'Z multimediami',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Włączony',
            'all'            => 'Wszystkie',
        ],
        'products' => [
            'title'              => 'Produkty',
            'invalid-locales'    => 'Nie wszystkie wybrane języki są dostępne dla wybranych kanałów.',
            'invalid-currencies' => 'Nie wszystkie wybrane waluty są dostępne dla wybranych kanałów.',
            'filters'            => [
                'channels'             => 'Kanały',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Waluty',
                'currencies-info'      => 'Atrybuty cen są eksportowane dla każdej wybranej waluty. Pozostaw puste, aby wyeksportować wszystkie waluty kanału.',
                'locales'              => 'Ustawienia regionalne',
                'locales-info'         => 'Atrybuty lokalizowalne są eksportowane raz dla każdego wybranego języka. Pozostaw puste, aby wyeksportować wszystkie języki kanału.',
                'attributes'           => 'Atrybuty',
                'attributes-info'      => 'Eksportowane są tylko wybrane atrybuty. Pozostaw puste, aby wyeksportować wszystkie atrybuty z rodziny.',
                'attribute-families'   => 'Rodziny atrybutów',
                'categories'           => 'Kategorie',
                'completeness'         => 'Kompletność',
                'completeness-options' => [
                    'none'         => 'Brak warunku kompletności',
                    'at-least-one' => 'Kompletny w co najmniej jednym wybranym języku',
                    'all'          => 'Kompletny we wszystkich wybranych językach',
                ],
                'time-condition' => 'Warunek czasowy',
                'time-options'   => [
                    'none'              => 'Brak warunku daty',
                    'last-n-days'       => 'Produkty zaktualizowane w ciągu ostatnich N dni',
                    'between-dates'     => 'Produkty zaktualizowane między dwiema datami',
                    'since-last-export' => 'Produkty zaktualizowane od ostatniego eksportu',
                ],
                'time-value'     => 'Liczba dni',
                'time-date'      => 'Data początkowa',
                'time-date-end'  => 'Data końcowa',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Włączony',
                    'disable' => 'Wyłączony',
                    'all'     => 'Wszystkie',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identyfikatory',
                'identifiers-info' => 'Wklej jeden SKU / identyfikator w wierszu, aby wyeksportować tylko te produkty. Pozostaw puste, aby wyeksportować wszystkie produkty.',
            ],
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
                'all'    => 'Wszystkie',
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
