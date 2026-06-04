<?php

return [
    [
        'key'     => 'catalog.measurements',
        'name'    => 'Measurements',
        'route'   => 'admin.measurement.families.index',
        'sort'    => 7,
        'icon'    => '',
    ],

    // Measurement Families
    [
        'key'   => 'catalog.measurements.families',
        'name'  => 'Measurement Families',
        'route' => 'admin.measurement.families.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.create',
        'name'  => 'Create',
        'route' => 'admin.measurement.families.create',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.create',
        'name'  => 'Create',
        'route' => 'admin.measurement.families.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.families.edit',
        'name'  => 'Edit',
        'route' => 'admin.measurement.families.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.families.edit',
        'name'  => 'Edit',
        'route' => 'admin.measurement.families.update',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.families.delete',
        'name'  => 'Delete',
        'route' => 'admin.measurement.families.delete',
        'sort'  => 3,
    ],
    [
        'key'   => 'catalog.measurements.families.mass_delete',
        'name'  => 'Mass Delete',
        'route' => 'admin.measurement.families.mass_delete',
        'sort'  => 4,
    ],

    // Measurement Units
    [
        'key'   => 'catalog.measurements.units',
        'name'  => 'Measurement Units',
        'route' => 'admin.measurement.families.units',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.create',
        'name'  => 'Create',
        'route' => 'admin.measurement.families.units.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.measurements.units.edit',
        'name'  => 'Edit',
        'route' => 'admin.measurement.families.units.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.edit',
        'name'  => 'Edit',
        'route' => 'admin.measurement.families.units.update',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.measurements.units.delete',
        'name'  => 'Delete',
        'route' => 'admin.measurement.families.units.delete',
        'sort'  => 3,
    ],
];
