<?php

return [
    'sections' => [
        [
            'title' => 'admin::app.help.index.services',
            'items' => [
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97A6 6 0 0 0 6.2 9.1 4 4 0 0 0 6.5 19z"></path></svg>',
                    'title'       => 'admin::app.help.cards.cloud-hosting.title',
                    'description' => 'admin::app.help.cards.cloud-hosting.description',
                    'url'         => 'https://unopim.com/cloud-hosting/',
                    'host'        => 'unopim.com/cloud-hosting',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><circle cx="12" cy="12" r="3.2"></circle><path d="m5.6 5.6 3.1 3.1 M15.3 15.3l3.1 3.1 M15.3 8.7l3.1-3.1 M5.6 18.4l3.1-3.1"></path></svg>',
                    'title'       => 'admin::app.help.cards.support.title',
                    'description' => 'admin::app.help.cards.support.description',
                    'url'         => 'https://unopim.com/support-maintenance/',
                    'host'        => 'unopim.com/support',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a1.4 1.4 0 0 0 2 2l6-6a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2z"></path></svg>',
                    'title'       => 'admin::app.help.cards.services.title',
                    'description' => 'admin::app.help.cards.services.description',
                    'url'         => 'https://unopim.com/services/',
                    'host'        => 'unopim.com/services',
                    'external'    => true,
                ],
            ],
        ],
        [
            'title' => 'admin::app.help.index.resources',
            'items' => [
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4a2 2 0 1 1 4 0v1h3a1 1 0 0 1 1 1v3h1a2 2 0 1 1 0 4h-1v3a1 1 0 0 1-1 1h-3v-1a2 2 0 1 0-4 0v1H6a1 1 0 0 1-1-1v-3H4a2 2 0 1 1 0-4h1V6a1 1 0 0 1 1-1h3z"></path></svg>',
                    'title'       => 'admin::app.help.cards.extensions.title',
                    'description' => 'admin::app.help.cards.extensions.description',
                    'url'         => 'https://unopim.com/extensions/',
                    'host'        => 'unopim.com/extensions',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2z M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>',
                    'title'       => 'admin::app.help.cards.user-guide.title',
                    'description' => 'admin::app.help.cards.user-guide.description',
                    'url'         => 'https://docs.unopim.com/',
                    'host'        => 'docs.unopim.com',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m8 8-4 4 4 4 M16 8l4 4-4 4 M14 5l-4 14"></path></svg>',
                    'title'       => 'admin::app.help.cards.api-docs.title',
                    'description' => 'admin::app.help.cards.api-docs.description',
                    'url'         => 'https://docs.unopim.com/api/',
                    'host'        => 'docs.unopim.com/api',
                    'external'    => true,
                ],
            ],
        ],
    ],

    'cta' => [
        'icon'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.2A8.4 8.4 0 0 1 4 11.5 8.5 8.5 0 0 1 21 11.5Z"></path></svg>',
        'title' => 'admin::app.help.cta.title',
        'sub'   => 'admin::app.help.cta.sub',
        'url'   => 'https://unopim.com/contacts/',
        'label' => 'admin::app.help.cta.button',
    ],
];
