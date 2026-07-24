<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publication',
            'info'     => 'Public serving tier for published, per-locale content.',
            'settings' => [
                'title'                            => 'Publication Settings',
                'enabled'                          => 'Enabled',
                'enabled-hint'                     => 'Master switch for the public serving tier. When off, every public passport URL returns 404 and the passport menu is hidden.',
                'base-url'                         => 'Base URL',
                'base-url-hint'                    => 'Public address where passports are served, used to build QR codes and shareable links. Leave blank to use this site’s own domain.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'Cache TTL (seconds)',
                'cache-ttl-hint'                   => 'How long a rendered public passport is cached before it is rebuilt. Higher values reduce load; lower values reflect edits sooner.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Rate Limit (requests/minute)',
                'rate-limit-hint'                  => 'Maximum public passport requests allowed each minute from a single visitor before they are throttled.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Allow search engine indexing',
                'indexable-hint'                   => 'Let search engines index public passport pages. Turn off to keep passports reachable by link but hidden from search results.',
                'gs1-passport-channel'             => 'GS1 Digital Link passport channel',
                'gs1-passport-channel-hint'        => 'The channel a scanned GS1 barcode (/01/{gtin}) resolves to when one product is published on several channels. Leave blank to use the first enabled channel.',
                'gs1-passport-channel-placeholder' => 'First enabled channel (automatic)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Draft',
            'published' => 'Published',
            'withdrawn' => 'Withdrawn',
            'redacted'  => 'Redacted',
        ],
        'product-delete-blocked' => 'This product cannot be deleted while it has published passports. Withdraw them first.',
        'channel-delete-blocked' => 'This channel cannot be deleted while it has published passports. Withdraw them first.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passport not found.',
            'notice'  => 'This product passport is not available. It may not be published yet, or the link may be incorrect.',
        ],
        '429' => [
            'heading' => 'Too many requests. Please try again shortly.',
            'notice'  => 'You have made too many requests. Please wait a moment and try again.',
        ],
        'withdrawn' => [
            'heading' => 'This passport is no longer available.',
            'notice'  => 'This record is retained for transparency but is no longer actively maintained.',
        ],
    ],
];
