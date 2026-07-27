<?php

return [
    [
        'key'     => 'catalog.measurements',
        'name'    => 'measurement::app.acl.measurements',
        'route'   => 'admin.measurement.families.index',
        'sort'    => 7,
        'icon'    => '',
    ],

    [
        'key'   => 'catalog.measurements.families',
        'name'  => 'measurement::app.acl.families',
        'route' => 'admin.measurement.families.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.measurement.families.create',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.measurement.families.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.measurement.families.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.families.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.measurement.families.update',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.families.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.measurement.families.delete',
        'sort'  => 3,
    ],
    [
        'key'   => 'catalog.measurements.families.mass_delete',
        'name'  => 'admin::app.acl.mass-delete',
        'route' => 'admin.measurement.families.mass_delete',
        'sort'  => 4,
    ],

    [
        'key'   => 'catalog.measurements.units',
        'name'  => 'measurement::app.acl.units',
        'route' => 'admin.measurement.families.units',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.measurement.families.units.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.units.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.measurement.families.units.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.measurement.families.units.update',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.measurement.families.units.delete',
        'sort'  => 3,
    ],
];
