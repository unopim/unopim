---
name: unopim-plugin-dev
description: "Plugin and package development for UnoPim. Activates when creating new packages, extending UnoPim, adding custom importers/exporters, configuring menus/ACL, or building admin features; or when the user mentions plugin, package, module, extension, importer, exporter, menu, ACL, or service provider."
license: MIT
metadata:
  author: unopim
---

# UnoPim Plugin Development

Complete guide for creating, configuring, and deploying UnoPim plugins (packages).

## When to Use This Skill

Invoke this skill when:

- Creating a new plugin/package for UnoPim
- Adding custom importers or exporters
- Extending admin menus, ACL, or system configuration
- Building custom DataGrids
- Creating custom models with Concord proxy pattern

## Instructions

1. **Plugin structure**: See [plugin-structure.md](plugin-structure.md) for directory layout and boilerplate
2. **Service providers**: See [service-providers.md](service-providers.md) for registration and bootstrapping
3. **Config integration**: See [config-integration.md](config-integration.md) for menu, ACL, and system config
4. **Custom importers/exporters**: See [custom-data-transfer.md](custom-data-transfer.md) for import/export profiles

## Quick Start

```bash
# 1. Create directory structure
mkdir -p packages/Webkul/Example/src/{Config,Contracts,Database/Migrations,Http/Controllers,Models,Providers,Repositories,Resources/views,Routes}

# 2. Add PSR-4 autoload to composer.json
# "Webkul\\Example\\": "packages/Webkul/Example/src"

# 3. Register in config/app.php providers array
# Webkul\Example\Providers\ExampleServiceProvider::class,

# 4. Dump autoload
composer dump-autoload

# 5. Run migrations
php artisan migrate
```

## Key Principles

- Always use the Concord proxy pattern for models
- Extend `Webkul\Core\Eloquent\Repository` for data access
- Register configs via `mergeConfigFrom()` in `register()`
- Load routes, migrations, translations, views in `boot()`
- Follow existing package structure conventions
- Add ACL entries for all new admin routes
