<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publication',
            'info'     => 'Public serving tier for published, per-locale content.',
            'settings' => [
                'title'      => 'Publication Settings',
                'enabled'    => 'Enabled',
                'base-url'   => 'Base URL',
                'cache-ttl'  => 'Cache TTL (seconds)',
                'rate-limit' => 'Rate Limit (requests/minute)',
                'indexable'  => 'Allow search engine indexing',
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
    ],

    'public' => [
        '404' => [
            'heading' => 'Passport not found.',
        ],
        '429' => [
            'heading' => 'Too many requests. Please try again shortly.',
        ],
        'withdrawn' => [
            'heading' => 'This passport is no longer available.',
        ],
    ],
];
