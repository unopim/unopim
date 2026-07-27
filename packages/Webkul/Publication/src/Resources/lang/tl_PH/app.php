<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Paglalathala',
            'info'     => 'Pampublikong antas ng paghahatid para sa nailathalang nilalaman, ayon sa bawat wika.',
            'settings' => [
                'title'                            => 'Mga Setting ng Paglalathala',
                'enabled'                          => 'Pinagana',
                'base-url'                         => 'Batayang URL',
                'cache-ttl'                        => 'TTL ng Cache (segundo)',
                'rate-limit'                       => 'Limitasyon sa Bilis (mga kahilingan/minuto)',
                'indexable'                        => 'Payagan ang pag-index ng mga search engine',
                'enabled-hint'                     => 'Pangunahing switch para sa pampublikong serving tier. Kapag naka-off, ang bawat pampublikong URL ng passport ay nagbabalik ng 404 at nakatago ang menu ng passport.',
                'base-url-hint'                    => 'Pampublikong address kung saan ihinahain ang mga passport, ginagamit upang bumuo ng mga QR code at mga link na maibabahagi. Iwanang blangko upang gamitin ang sariling domain ng site na ito.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Gaano katagal naka-cache ang isang na-render na pampublikong passport bago ito muling itayo. Mas mataas na halaga ay nagpapababa ng load; mas mababa ay mas mabilis na naipapakita ang mga pagbabago.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Pinakamataas na bilang ng pampublikong kahilingan sa passport bawat minuto mula sa iisang bisita bago sila limitahan.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Hayaan ang mga search engine na i-index ang mga pahina ng pampublikong passport. I-off upang manatiling maabot ang mga passport sa pamamagitan ng link ngunit nakatago sa mga resulta ng paghahanap.',
                'gs1-passport-channel'             => 'Channel ng pasaporte ng GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'Ang channel kung saan magre-resolve ang isang na-scan na GS1 barcode (/01/{gtin}) kapag ang isang produkto ay nailathala sa ilang channel. Iwanang blangko upang gamitin ang unang naka-enable na channel.',
                'gs1-passport-channel-placeholder' => 'Unang naka-enable na channel (awtomatiko)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Draft',
            'published' => 'Nailathala',
            'withdrawn' => 'Binawi',
            'redacted'  => 'Na-redact',
        ],
        'product-delete-blocked' => 'Hindi maaaring tanggalin ang produktong ito habang mayroon itong nailathalang mga pasaporte. Bawiin muna ang mga ito.',
        'channel-delete-blocked' => 'Hindi maaaring tanggalin ang channel na ito habang mayroon itong nailathalang mga pasaporte. Bawiin muna ang mga ito.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Hindi natagpuan ang pasaporte.',
            'notice'  => 'Hindi available ang pasaporte ng produktong ito. Maaaring hindi pa ito nai-publish, o mali ang link.',
        ],
        '429' => [
            'heading' => 'Masyadong maraming kahilingan. Pakisubukang muli sandali.',
            'notice'  => 'Masyado kang maraming ginawang kahilingan. Maghintay sandali at subukang muli.',
        ],
        'withdrawn' => [
            'heading' => 'Hindi na available ang pasaporteng ito.',
            'notice'  => 'Itinatago ang talaang ito para sa transparency ngunit hindi na ito aktibong pinananatili.',
        ],
    ],
];
