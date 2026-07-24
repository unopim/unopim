<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publikacja',
            'info'     => 'Warstwa publicznego udostępniania dla opublikowanej treści w danym języku.',
            'settings' => [
                'title'                            => 'Ustawienia publikacji',
                'enabled'                          => 'Włączone',
                'base-url'                         => 'Adres podstawowy URL',
                'cache-ttl'                        => 'TTL pamięci podręcznej (sekundy)',
                'rate-limit'                       => 'Limit szybkości (żądania/minutę)',
                'indexable'                        => 'Zezwól na indeksowanie przez wyszukiwarki',
                'enabled-hint'                     => 'Główny przełącznik publicznej warstwy udostępniania. Gdy jest wyłączony, każdy publiczny adres URL paszportu zwraca błąd 404, a menu paszportów jest ukryte.',
                'base-url-hint'                    => 'Publiczny adres, pod którym udostępniane są paszporty, używany do tworzenia kodów QR i linków do udostępniania. Pozostaw puste, aby użyć własnej domeny tej witryny.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Jak długo wyrenderowany publiczny paszport jest przechowywany w pamięci podręcznej, zanim zostanie odbudowany. Wyższe wartości zmniejszają obciążenie; niższe szybciej odzwierciedlają zmiany.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Maksymalna liczba publicznych żądań paszportu dozwolona na minutę od jednego odwiedzającego, zanim zostanie on ograniczony.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Pozwól wyszukiwarkom indeksować publiczne strony paszportów. Wyłącz, aby paszporty pozostały dostępne przez link, ale ukryte w wynikach wyszukiwania.',
                'gs1-passport-channel'             => 'Kanał paszportu GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'Kanał, do którego prowadzi zeskanowany kod kreskowy GS1 (/01/{gtin}), gdy jeden produkt jest opublikowany w kilku kanałach. Pozostaw puste, aby użyć pierwszego włączonego kanału.',
                'gs1-passport-channel-placeholder' => 'Pierwszy włączony kanał (automatycznie)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Szkic',
            'published' => 'Opublikowano',
            'withdrawn' => 'Wycofano',
            'redacted'  => 'Utajnione',
        ],
        'product-delete-blocked' => 'Tego produktu nie można usunąć, dopóki ma opublikowane paszporty. Najpierw je wycofaj.',
        'channel-delete-blocked' => 'Tego kanału nie można usunąć, dopóki ma opublikowane paszporty. Najpierw je wycofaj.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Nie znaleziono paszportu.',
            'notice'  => 'Ten paszport produktu jest niedostępny. Możliwe, że nie został jeszcze opublikowany lub link jest nieprawidłowy.',
        ],
        '429' => [
            'heading' => 'Zbyt wiele żądań. Spróbuj ponownie za chwilę.',
            'notice'  => 'Wysłano zbyt wiele żądań. Odczekaj chwilę i spróbuj ponownie.',
        ],
        'withdrawn' => [
            'heading' => 'Ten paszport nie jest już dostępny.',
            'notice'  => 'Ten rekord jest przechowywany dla przejrzystości, ale nie jest już aktywnie utrzymywany.',
        ],
    ],
];
