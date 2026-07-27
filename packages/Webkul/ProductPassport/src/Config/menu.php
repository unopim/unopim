<?php

return [
    /**
     * Passports and Field Mapping share one surface with an in-page route-driven
     * tab bar (see `admin.partials.tabs`); mapping is deliberately NOT a child
     * menu item, so the global menu-driven tab strip never renders a duplicate
     * "Field Mapping" heading above the passports grid.
     */
    [
        'key'   => 'catalog.passport',
        'name'  => 'passport::app.components.layouts.sidebar.menu.passports.name',
        'route' => 'admin.catalog.passports.index',
        'sort'  => 7,
    ],
];
