<?php

/*
|--------------------------------------------------------------------------
| System Settings Registry
|--------------------------------------------------------------------------
|
| Extensible, config-driven registry for the System Settings hub. Any package
| adds sections/rows by merging its own `system_settings` config — no core edit.
|
| Each entry is a flat, dot-keyed array (same shape as config('menu.admin')):
|   'key'   => dot path; the first segment is the section, e.g. 'system.email'
|   'name'  => translation key for the label
|   'info'  => translation key for the description (optional)
|   'icon'  => icon class (optional)
|   'sort'  => ordering within its level
|   'acl'   => permission key; the row is hidden when the admin lacks it (optional)
|   'route' => named route the row links to (mutually exclusive with 'fields')
|   'fields'=> inline field definitions persisted to DB core-config (see Task 4)
|
| Section entries (a bare `key` with no route/fields) group the rows beneath them.
| Default entries are registered in Task 5.
|
*/

return [
    [
        'key'  => 'system',
        'name' => 'admin::app.settings.system-settings.system.title',
        'info' => 'admin::app.settings.system-settings.system.info',
        'sort' => 1,
    ],

    [
        'key'   => 'system.appearance',
        'name'  => 'admin::app.settings.appearance.title',
        'info'  => 'admin::app.settings.appearance.info',
        'icon'  => 'icon-image',
        'route' => 'admin.settings.appearance.index',
        'acl'   => 'configuration.system_settings',
        'sort'  => 1,
    ],

    [
        'key'          => 'system.email',
        'name'         => 'admin::app.settings.system-settings.email.title',
        'info'         => 'admin::app.settings.system-settings.email.info',
        'icon'         => 'icon-at',
        // References the existing core-config group so saved codes stay put.
        'config_group' => 'emails.configure.email_settings',
        'acl'          => 'configuration.system_settings',
        'sort'         => 2,
    ],

    [
        'key'          => 'system.debug',
        'name'         => 'admin::app.settings.system-settings.debug.title',
        'info'         => 'admin::app.settings.system-settings.debug.info',
        'icon'         => 'icon-setting',
        'config_group' => 'general.debug.settings',
        'acl'          => 'configuration.system_settings',
        'sort'         => 3,
    ],

    [
        'key'          => 'system.microsoft_sso',
        'name'         => 'admin::app.settings.system-settings.microsoft-sso.title',
        'info'         => 'admin::app.settings.system-settings.microsoft-sso.info',
        'icon'         => 'icon-configuration',
        'config_group' => 'general.microsoft_sso.settings',
        'acl'          => 'configuration.system_settings',
        'sort'         => 4,
    ],
];
