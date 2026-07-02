<?php

return [
    // ── Measurements (families, units & attribute config) ────
    [
        'key'   => 'api.catalog.measurements',
        'name'  => 'admin::app.acl.measurements',
        'route' => 'admin.api.measurement.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.measurements.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.measurement.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.measurements.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.measurement.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.measurements.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.api.measurement.delete',
        'sort'  => 3,
    ],

    // Units — map to the same measurement permissions
    [
        'key'   => 'api.catalog.measurements.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.measurement-units.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.measurements.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.measurement-units.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.measurements.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.api.measurement-units.delete',
        'sort'  => 3,
    ],

    // Attribute measurement config — map to the same measurement permissions
    [
        'key'   => 'api.catalog.measurements.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.attribute-measurement.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.measurements.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.attribute-measurement.update',
        'sort'  => 2,
    ],

    // Legacy misspelled alias — kept for backward compatibility
    [
        'key'   => 'api.catalog.measurements.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.attribute-measurment.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.measurements.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.attribute-measurment.update',
        'sort'  => 2,
    ],
];
