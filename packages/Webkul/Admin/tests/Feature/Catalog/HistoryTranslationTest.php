<?php

it('should have a translated value for the history view action tooltip', function () {
    $translated = trans('admin::app.catalog.history.view');

    $this->assertNotEquals('admin::app.catalog.history.view', $translated, 'The history view translation key should be defined, not return a raw key');
    $this->assertNotEmpty($translated);
});

it('should have the history view translation in all active locales', function () {
    $locales = ['en_US', 'fr_FR', 'de_DE', 'ja_JP', 'hi_IN', 'zh_CN', 'ar_AE', 'es_ES'];

    foreach ($locales as $locale) {
        app()->setLocale($locale);

        $translated = trans('admin::app.catalog.history.view');

        $this->assertNotEquals('admin::app.catalog.history.view', $translated, "Translation key 'admin::app.catalog.history.view' is missing for locale {$locale}");
    }

    // Reset locale
    app()->setLocale('en_US');
});
