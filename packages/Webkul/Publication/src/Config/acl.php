<?php

return [
    [
        /**
         * Routeless: the Publication system-settings row shares the generic
         * editor route, so per-section access is enforced in
         * SystemSettingsController against the hub row's `acl`.
         */
        'key'   => 'configuration.system_settings.publication',
        'name'  => 'publication::app.configuration.publication.title',
        'route' => null,
        'sort'  => 5,
    ],
];
