<?php

use Webkul\Webhook\DataGrids\LogsDataGrid;

it('webhook logs grid placeholder references the sku/user search key, not code', function () {
    $grid = new LogsDataGrid;

    $reflection = new ReflectionClass($grid);
    $property = $reflection->getProperty('searchPlaceholder');
    $property->setAccessible(true);

    $placeholderKey = $property->getValue($grid);

    expect($placeholderKey)
        ->toBe('admin::app.components.datagrid.toolbar.search_by.sku_or_user');
});

it('the sku_or_user translation key resolves to an English string that mentions SKU or user', function () {
    $resolved = __('admin::app.components.datagrid.toolbar.search_by.sku_or_user', [], 'en_US');

    expect($resolved)
        ->not->toBe('admin::app.components.datagrid.toolbar.search_by.sku_or_user')
        ->and(strtolower($resolved))
        ->toContain('sku')
        ->toContain('user');
});
