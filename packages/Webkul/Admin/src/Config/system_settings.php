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

return [];
