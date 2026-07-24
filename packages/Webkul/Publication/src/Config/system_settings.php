<?php

/*
|--------------------------------------------------------------------------
| Publication System Settings Hub Entry
|--------------------------------------------------------------------------
|
| Merged into the `system_settings` namespace consumed by the System
| Settings hub (`Webkul\Admin\SystemSettings`). `config_group` points at the
| `general.publication.settings` field group registered in
| `publication_settings.php`, so the row edits that existing `core_config`
| group rather than relocating saved data under a new key. Gated by the
| section-scoped `configuration.system_settings.publication` permission
| (see `acl.php`), enforced per-row in `SystemSettingsController`.
|
*/

return [
    [
        'key'          => 'system.publication',
        'name'         => 'publication::app.configuration.publication.title',
        'info'         => 'publication::app.configuration.publication.info',
        'icon'         => 'icon-setting',
        'config_group' => 'general.publication.settings',
        'acl'          => 'configuration.system_settings.publication',
        'sort'         => 8,
    ],
];
