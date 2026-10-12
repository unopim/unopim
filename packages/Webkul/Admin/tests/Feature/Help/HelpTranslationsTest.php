<?php

it('en_US has all help translation keys', function () {
    app()->setLocale('en_US');

    expect(trans('admin::app.help.index.title'))->toBe('Help & Resources');
    expect(trans('admin::app.help.index.services'))->toBe('Services');
    expect(trans('admin::app.help.index.resources'))->toBe('Resources & Documentation');
    expect(trans('admin::app.help.cards.cloud-hosting.title'))->toBe('Cloud Hosting');
    expect(trans('admin::app.help.cards.api-docs.title'))->toBe('API Docs');
    expect(trans('admin::app.help.cta.button'))->toBe('Contact us');
    expect(trans('admin::app.components.layouts.sidebar.help'))->toBe('Help');
    expect(trans('admin::app.acl.help'))->toBe('Help');

    foreach ([
        'admin::app.help.index.subtitle',
        'admin::app.help.cards.support.description',
        'admin::app.help.cards.services.description',
        'admin::app.help.cards.extensions.description',
        'admin::app.help.cards.user-guide.description',
        'admin::app.help.cta.title',
        'admin::app.help.cta.sub',
    ] as $key) {
        expect(trans($key))->not->toBe($key);
    }
});
