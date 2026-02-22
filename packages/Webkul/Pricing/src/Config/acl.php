<?php

return [
    [
        'key'   => 'pricing',
        'name'  => 'pricing::app.acl.pricing',
        'route' => 'admin.pricing.costs.index',
        'sort'  => 12,
    ], [
        'key'   => 'pricing.costs',
        'name'  => 'pricing::app.acl.costs',
        'route' => 'admin.pricing.costs.index',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.costs.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.costs.index',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.costs.create',
        'name'  => 'pricing::app.acl.create',
        'route' => 'admin.pricing.costs.create',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.costs.create',
        'name'  => 'pricing::app.acl.create',
        'route' => 'admin.pricing.costs.store',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.costs.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.costs.edit',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.costs.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.costs.update',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.costs.delete',
        'name'  => 'pricing::app.acl.delete',
        'route' => 'admin.pricing.costs.destroy',
        'sort'  => 4,
    ], [
        'key'   => 'pricing.channel-costs',
        'name'  => 'pricing::app.acl.channel-costs',
        'route' => 'admin.pricing.channel-costs.index',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.channel-costs.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.channel-costs.index',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.channel-costs.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.channel-costs.store',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.channel-costs.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.channel-costs.update',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.margins',
        'name'  => 'pricing::app.acl.margins',
        'route' => 'admin.pricing.margins.index',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.margins.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.margins.index',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.margins.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.margins.show',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.margins.approve',
        'name'  => 'pricing::app.acl.approve',
        'route' => 'admin.pricing.margins.approve',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.margins.reject',
        'name'  => 'pricing::app.acl.reject',
        'route' => 'admin.pricing.margins.reject',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.recommendations',
        'name'  => 'pricing::app.acl.recommendations',
        'route' => 'admin.pricing.recommendations.show',
        'sort'  => 4,
    ], [
        'key'   => 'pricing.recommendations.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.recommendations.show',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.recommendations.apply',
        'name'  => 'pricing::app.acl.apply',
        'route' => 'admin.pricing.recommendations.apply',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.break_even',
        'name'  => 'pricing::app.acl.break-even',
        'route' => 'admin.pricing.break-even.show',
        'sort'  => 6,
    ], [
        'key'   => 'pricing.break_even.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.break-even.show',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.strategies',
        'name'  => 'pricing::app.acl.strategies',
        'route' => 'admin.pricing.strategies.index',
        'sort'  => 5,
    ], [
        'key'   => 'pricing.strategies.view',
        'name'  => 'pricing::app.acl.view',
        'route' => 'admin.pricing.strategies.index',
        'sort'  => 1,
    ], [
        'key'   => 'pricing.strategies.create',
        'name'  => 'pricing::app.acl.create',
        'route' => 'admin.pricing.strategies.create',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.strategies.create',
        'name'  => 'pricing::app.acl.create',
        'route' => 'admin.pricing.strategies.store',
        'sort'  => 2,
    ], [
        'key'   => 'pricing.strategies.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.strategies.edit',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.strategies.edit',
        'name'  => 'pricing::app.acl.edit',
        'route' => 'admin.pricing.strategies.update',
        'sort'  => 3,
    ], [
        'key'   => 'pricing.strategies.delete',
        'name'  => 'pricing::app.acl.delete',
        'route' => 'admin.pricing.strategies.destroy',
        'sort'  => 4,
    ],
];
