<?php

return [

    'attribute' => [
        'calculated' => 'Kinakalkula',
    ],

    'formula' => [
        'title'            => 'Pormula',
        'label'            => 'Pormula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Ang halaga ay kinukuha mula sa pormulang ito tuwing ipinapakita, ini-export, o ibinabalik ng API ang produkto. Hindi ito kailanman iniimbak at hindi ito maaaring i-import.',
        'reference-hint'   => 'Tukuyin ang ibang mga attribute sa pamamagitan ng kanilang code.',
        'available'        => 'Mga available na function',
        'examples'         => 'Mga halimbawa',
        'example-concat'   => 'Pagsamahin ang dalawang attribute, laktawan ang mga walang laman',
        'example-category' => 'Ang landas ng label ng kategorya, pinagdugtong ng #',
        'example-fallback' => 'Gumamit ng pangalawang attribute kapag walang laman ang una',
        'read-only'        => 'Kinakalkula',
        'read-only-hint'   => 'Ang halagang ito ay kinakalkula mula sa ibang mga attribute at hindi maaaring i-edit.',

        'variables' => [
            'title'      => 'Mga available na variable',
            'sku'        => 'ang SKU ng produkto',
            'type'       => 'ang uri ng produkto',
            'status'     => 'ang katayuan ng produkto',
            'family'     => 'ang code ng attribute family',
            'parent'     => 'ang SKU ng pangunahing produkto, para sa mga variant',
            'categories' => 'ang mga code ng nakatalagang kategorya',
            'channel'    => 'ang channel na ipinapakita',
            'locale'     => 'ang wikang ipinapakita',
        ],

        'errors' => [
            'syntax'              => 'Hindi ma-parse ang pormula: :detail',
            'unknown-variable'    => 'Hindi kilalang attribute o variable na ":name" sa pormula.',
            'disallowed-function' => 'Hindi pinapayagan ang function na ":name" sa isang pormula.',
            'disallowed-operator' => 'Hindi pinapayagan ang operator na ":name" sa isang pormula.',
            'reserved-variable'   => 'Nakalaan ang pangalang ":name" at hindi ito magagamit sa isang pormula.',
            'too-long'            => 'Hindi maaaring humigit sa :max na karakter ang pormula.',
            'empty'               => 'Kailangan ng pormula ang isang kinakalkulang attribute.',
            'circular'            => 'Lumilikha ang pormula ng paikot na dependency: :cycle',
            'referenced-by'       => 'Hindi matatanggal ang attribute na ito dahil ginagamit ito ng pormula ng: :attributes',
        ],
    ],

];
