<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publikacja',
            'info'     => 'Warstwa publicznego udostępniania dla opublikowanej treści w danym języku.',
            'settings' => [
                'title'      => 'Ustawienia publikacji',
                'enabled'    => 'Włączone',
                'base-url'   => 'Adres podstawowy URL',
                'cache-ttl'  => 'TTL pamięci podręcznej (sekundy)',
                'rate-limit' => 'Limit szybkości (żądania/minutę)',
                'indexable'  => 'Zezwól na indeksowanie przez wyszukiwarki',
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
    ],

    'public' => [
        '404' => [
            'heading' => 'Nie znaleziono paszportu.',
        ],
        '429' => [
            'heading' => 'Zbyt wiele żądań. Spróbuj ponownie za chwilę.',
        ],
        'withdrawn' => [
            'heading' => 'Ten paszport nie jest już dostępny.',
        ],
    ],
];
