<?php

return [
    'acl' => [
        'variant-structures' => 'Mga Istruktura ng Variant',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopya-ng-:value',
        'copy-of'                       => 'Kopya ng :value',
        'variant-already-exist-message' => 'May ganitong opsyon ng attribute ang variant.',
    ],
    'response' => [
        'product-can-not-be-copied' => 'Ang mga produkto ng uri ng :type ay hindi maaaring kopyahin',
    ],
    'type' => [
        'configurable'  => 'Nakokontrol',
        'simple'        => 'Simple',
        'variant-group' => 'Grupo ng Variant',
        'variant_group' => 'Grupo ng Variant',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'May natukoy na paikot na reference ng template: :path.',
            'currency-not-allowed'    => 'Ang mga katangian ng presyo lamang ang tumatanggap ng pera, ngunit ang ":attribute" ay hindi katangian ng presyo.',
            'currency-not-enabled'    => 'Ang pera na ":currency" na ginamit sa template ay hindi naka-enable para sa anumang channel.',
            'currency-not-in-channel' => 'Ang pera na ":currency" ay hindi naka-enable para sa channel na ":channel".',
            'filter-failed'           => 'Hindi mailapat ang filter na ":filter" sa halaga ng ":attribute".',
            'filter-not-supported'    => 'Hindi maaaring ilapat ang filter na ":filter" sa katangiang ":attribute".',
            'format-date-position'    => 'Ang filter na format_date ay dapat ang unang filter sa ":expression".',
            'inside-html-tag'         => 'Hindi maaaring ilagay ang mga expression ng template sa loob ng mga HTML tag o attribute tulad ng mga link at larawan.',
            'invalid-filter-argument' => 'Ang filter na ":filter" sa ":expression" ay may nawawala o hindi wastong argumento.',
            'invalid-reference'       => 'Ang template na naka-imbak sa ":attribute" ay hindi wasto at hindi ma-render.',
            'invalid-syntax'          => 'Hindi wasto ang expression ng template na ":expression". Gumamit ng code ng katangian na sinusundan ng mga opsyonal na filter, halimbawa: name | upper.',
            'max-depth'               => 'Ang mga reference ng template ay naka-nest nang higit sa :max antas ang lalim sa ":attribute".',
            'missing-value'           => 'Walang halaga ang katangiang ":attribute" para sa channel at wikang ito.',
            'not-in-family'           => 'Ang katangiang ":attribute" na ginamit sa template ay hindi kabilang sa pamilya ng produkto.',
            'output-too-large'        => 'Ang na-render na template ay mas malaki sa :max byte kaya ito ay pinutol.',
            'self-reference'          => 'Hindi maaaring i-reference ng template ang sarili nitong katangian na ":attribute".',
            'single-brace'            => 'Natagpuan ang ":text" sa iisang curly brace. Kailangan ng mga expression ng template ang dobleng curly brace.',
            'source-too-long'         => 'Ang isang template ay maaaring hindi hihigit sa :max byte ang haba.',
            'too-complex'             => 'Masyadong kumplikado ang template para masuri. Paikliin o pasimplehin ito.',
            'too-many-expressions'    => 'Ang isang template ay maaaring maglaman ng hindi hihigit sa :max na expression.',
            'too-many-filters'        => 'Ang expression ng template na ":expression" ay maaaring gumamit ng hindi hihigit sa :max na filter.',
            'unclosed-expression'     => 'May binuksang expression ng template ngunit hindi ito isinara.',
            'unknown-attribute'       => 'Hindi umiiral ang katangiang ":attribute" na ginamit sa template.',
            'unknown-currency'        => 'Hindi umiiral ang pera na ":currency" na ginamit sa template.',
            'unknown-filter'          => 'Hindi suportado ang filter na ":filter". Mga available na filter: :filters.',
            'unsupported-attribute'   => 'Hindi maaaring gamitin ang katangiang ":attribute" sa template dahil hindi suportado ang uri nito.',
        ],
    ],
];
