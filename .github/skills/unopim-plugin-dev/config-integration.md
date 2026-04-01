# Config Integration — UnoPim Plugins

---

## Admin Menu (`Config/menu.php`)

```php
<?php

return [
    [
        'key'   => 'example',           // Unique key
        'name'  => 'example::app.menu.title',  // Translation key
        'route' => 'admin.example.index',
        'sort'  => 5,
        'icon'  => 'icon-example',
    ],
    [
        'key'   => 'example.items',     // Sub-menu (parent.child)
        'name'  => 'example::app.menu.items',
        'route' => 'admin.example.items.index',
        'sort'  => 1,
        'icon'  => '',
    ],
];
```

### Menu Keys

- Top-level: `'key' => 'example'`
- Sub-menu: `'key' => 'example.items'`
- Sub-sub: `'key' => 'example.items.detail'`

---

## ACL Permissions (`Config/acl.php`)

```php
<?php

return [
    [
        'key'   => 'example',
        'name'  => 'example::app.acl.example',
        'route' => 'admin.example.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'example.items',
        'name'  => 'example::app.acl.items',
        'route' => 'admin.example.items.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'example.items.create',
        'name'  => 'example::app.acl.create',
        'route' => 'admin.example.items.create',
        'sort'  => 1,
    ],
    [
        'key'   => 'example.items.edit',
        'name'  => 'example::app.acl.edit',
        'route' => 'admin.example.items.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'example.items.delete',
        'name'  => 'example::app.acl.delete',
        'route' => 'admin.example.items.delete',
        'sort'  => 3,
    ],
];
```

### ACL Keys

- Follow menu hierarchy: `example` → `example.items` → `example.items.create`
- Check in controllers: `bouncer()->hasPermission('example.items.create')`
- Check in middleware: `'middleware' => ['admin', 'acl:example.items']`

---

## System Configuration (`Config/system.php`)

```php
<?php

return [
    [
        'key'  => 'general.example',
        'name' => 'example::app.system.title',
        'info' => 'example::app.system.info',
        'sort' => 3,
    ],
    [
        'key'    => 'general.example.settings',
        'name'   => 'example::app.system.settings.title',
        'info'   => 'example::app.system.settings.info',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'example::app.system.settings.enabled',
                'type'  => 'boolean',
                'default' => '0',
            ],
            [
                'name'       => 'api_key',
                'title'      => 'example::app.system.settings.api-key',
                'type'       => 'text',
                'validation' => 'required_if:general.example.settings.enabled,1',
            ],
        ],
    ],
];
```

### Field Types

`text`, `textarea`, `boolean`, `select`, `multiselect`, `password`, `number`, `file`, `image`

### Reading Config Values

```php
core()->getConfigData('general.example.settings.enabled');
core()->getConfigData('general.example.settings.api_key');
```
