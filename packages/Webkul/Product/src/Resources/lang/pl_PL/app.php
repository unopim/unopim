<?php

return [
    'acl' => [
        'variant-structures' => 'Struktury wariantów',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopia-:value',
        'copy-of'                       => 'Kopia :value',
        'variant-already-exist-message' => 'Variant z tymi samymi opcjami atrybutów już istnieje.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produktów typu :type nie można skopiować',
    ],

    'type'     => [
        'configurable'  => 'Konfigurowalny',
        'simple'        => 'Prosty',
        'variant-group' => 'Grupa wariantów',
        'variant_group' => 'Grupa wariantów',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Wykryto cykliczne odwołanie w szablonie: :path.',
            'currency-not-allowed'    => 'Tylko atrybuty ceny akceptują walutę, ale ":attribute" nie jest atrybutem ceny.',
            'currency-not-enabled'    => 'Waluta ":currency" użyta w szablonie nie jest włączona dla żadnego kanału.',
            'currency-not-in-channel' => 'Waluta ":currency" nie jest włączona dla kanału ":channel".',
            'filter-failed'           => 'Nie udało się zastosować filtra ":filter" do wartości ":attribute".',
            'filter-not-supported'    => 'Filtra ":filter" nie można zastosować do atrybutu ":attribute".',
            'format-date-position'    => 'Filtr format_date musi być pierwszym filtrem w ":expression".',
            'inside-html-tag'         => 'Wyrażeń szablonu nie można umieszczać wewnątrz znaczników ani atrybutów HTML, takich jak linki i obrazy.',
            'invalid-filter-argument' => 'Filtr ":filter" w ":expression" ma brakujący lub nieprawidłowy argument.',
            'invalid-reference'       => 'Szablon zapisany w ":attribute" jest nieprawidłowy i nie można go było wyrenderować.',
            'invalid-syntax'          => 'Wyrażenie szablonu ":expression" jest nieprawidłowe. Użyj kodu atrybutu, a po nim opcjonalnych filtrów, na przykład: name | upper.',
            'max-depth'               => 'Odwołania szablonu są zagnieżdżone na więcej niż :max poziomów w ":attribute".',
            'missing-value'           => 'Atrybut ":attribute" nie ma wartości dla tego kanału i języka.',
            'not-in-family'           => 'Atrybut ":attribute" użyty w szablonie nie należy do rodziny produktu.',
            'output-too-large'        => 'Wyrenderowany szablon przekracza :max bajtów i został obcięty.',
            'self-reference'          => 'Szablon nie może odwoływać się do własnego atrybutu ":attribute".',
            'single-brace'            => 'Znaleziono ":text" w pojedynczych nawiasach klamrowych. Wyrażenia szablonu wymagają podwójnych nawiasów klamrowych.',
            'source-too-long'         => 'Szablon może mieć maksymalnie :max bajtów.',
            'too-complex'             => 'Szablon jest zbyt złożony, aby go sprawdzić. Skróć go lub uprość.',
            'too-many-expressions'    => 'Szablon może zawierać maksymalnie :max wyrażeń.',
            'too-many-filters'        => 'Wyrażenie szablonu ":expression" może używać maksymalnie :max filtrów.',
            'unclosed-expression'     => 'Wyrażenie szablonu zostało otwarte, ale nigdy nie zostało zamknięte.',
            'unknown-attribute'       => 'Atrybut ":attribute" użyty w szablonie nie istnieje.',
            'unknown-currency'        => 'Waluta ":currency" użyta w szablonie nie istnieje.',
            'unknown-filter'          => 'Filtr ":filter" nie jest obsługiwany. Dostępne filtry: :filters.',
            'unsupported-attribute'   => 'Atrybutu ":attribute" nie można użyć w szablonie, ponieważ jego typ nie jest obsługiwany.',
        ],
    ],
];
