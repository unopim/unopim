<?php

return [
    [
        'key'   => 'pricing',
        'name'  => 'pricing::app.components.layouts.sidebar.pricing',
        'route' => 'admin.pricing.costs.index',
        'sort'  => 8,
        'icon'  => 'icon-pricing',
    ], [
        'key'   => 'pricing.costs',
        'name'  => 'pricing::app.components.layouts.sidebar.costs',
        'route' => 'admin.pricing.costs.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'pricing.channel-costs',
        'name'  => 'pricing::app.components.layouts.sidebar.channel-costs',
        'route' => 'admin.pricing.channel-costs.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'pricing.recommendations',
        'name'  => 'pricing::app.components.layouts.sidebar.recommendations',
        'route' => 'admin.pricing.recommendations.show',
        'sort'  => 3,
        'icon'  => '',
    ], [
        'key'   => 'pricing.margins',
        'name'  => 'pricing::app.components.layouts.sidebar.margins',
        'route' => 'admin.pricing.margins.index',
        'sort'  => 4,
        'icon'  => '',
    ], [
        'key'   => 'pricing.strategies',
        'name'  => 'pricing::app.components.layouts.sidebar.strategies',
        'route' => 'admin.pricing.strategies.index',
        'sort'  => 5,
        'icon'  => '',
    ],
];
