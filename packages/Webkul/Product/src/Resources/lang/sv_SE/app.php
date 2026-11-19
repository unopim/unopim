<?php

return [
    'acl' => [
        'variant-structures' => 'Variantstrukturer',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopia-av-:value',
        'copy-of'                       => 'Kopia av :value',
        'variant-already-exist-message' => 'Variant med samma attributalternativ redan existerar.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produkter av typ :type kan inte kopieras',
    ],

    'type'     => [
        'configurable'  => 'Konfigurerbar',
        'simple'        => 'Enkel',
        'variant-group' => 'Variantgrupp',
        'variant_group' => 'Variantgrupp',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Cirkulär mallreferens upptäcktes: :path.',
            'currency-not-allowed'    => 'Endast prisattribut accepterar en valuta, men ":attribute" är inte ett prisattribut.',
            'currency-not-enabled'    => 'Valutan ":currency" som används i mallen är inte aktiverad för någon kanal.',
            'currency-not-in-channel' => 'Valutan ":currency" är inte aktiverad för kanalen ":channel".',
            'filter-failed'           => 'Filtret ":filter" kunde inte tillämpas på värdet för ":attribute".',
            'filter-not-supported'    => 'Filtret ":filter" kan inte tillämpas på attributet ":attribute".',
            'format-date-position'    => 'Filtret format_date måste vara det första filtret i ":expression".',
            'inside-html-tag'         => 'Malluttryck kan inte placeras inuti HTML-taggar eller -attribut som länkar och bilder.',
            'invalid-filter-argument' => 'Filtret ":filter" i ":expression" har ett saknat eller ogiltigt argument.',
            'invalid-reference'       => 'Mallen som lagras i ":attribute" är ogiltig och kunde inte renderas.',
            'invalid-syntax'          => 'Malluttrycket ":expression" är ogiltigt. Använd en attributkod följd av valfria filter, till exempel: name | upper.',
            'max-depth'               => 'Mallreferenser är kapslade mer än :max nivåer djupt vid ":attribute".',
            'missing-value'           => 'Attributet ":attribute" har inget värde för den här kanalen och det här språket.',
            'not-in-family'           => 'Attributet ":attribute" som används i mallen tillhör inte produktfamiljen.',
            'output-too-large'        => 'Den renderade mallen är större än :max byte och har kortats av.',
            'self-reference'          => 'Mallen kan inte referera till sitt eget attribut ":attribute".',
            'single-brace'            => 'Hittade ":text" inom enkla klammerparenteser. Malluttryck kräver dubbla klammerparenteser.',
            'source-too-long'         => 'En mall kan vara högst :max byte lång.',
            'too-complex'             => 'Mallen är för komplex för att kontrolleras. Korta ner eller förenkla den.',
            'too-many-expressions'    => 'En mall kan innehålla högst :max uttryck.',
            'too-many-filters'        => 'Malluttrycket ":expression" kan använda högst :max filter.',
            'unclosed-expression'     => 'Ett malluttryck öppnades men stängdes aldrig.',
            'unknown-attribute'       => 'Attributet ":attribute" som används i mallen finns inte.',
            'unknown-currency'        => 'Valutan ":currency" som används i mallen finns inte.',
            'unknown-filter'          => 'Filtret ":filter" stöds inte. Tillgängliga filter: :filters.',
            'unsupported-attribute'   => 'Attributet ":attribute" kan inte användas i en mall eftersom dess typ inte stöds.',
        ],
    ],
];
