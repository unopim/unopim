# UnoPim Patterns - Master Reference

Quick-reference skill for all 6 architectural layers. Use layer-specific skills (`/unopim-data`, `/unopim-infra`, `/unopim-domain`, `/unopim-app`, `/unopim-middleware`, `/unopim-client`) for detailed patterns.

## Layer Overview

| Layer | Skill | Key Patterns |
|-------|-------|-------------|
| DATA/EXTERNAL | `/unopim-data` | GrammarQueryManager, Eloquent models, Repository pattern, product values JSON, TranslatableModel, Elasticsearch, MagicAI |
| INFRASTRUCTURE | `/unopim-infra` | Concord modules, ServiceProviders, DataGrid, Event system, HistoryTrait, Theme/Vite |
| DOMAIN | `/unopim-domain` | Product types (Simple/Configurable), Attribute system (12 types), Category nested set, User/RBAC, DataTransfer, MagicAI |
| APPLICATION | `/unopim-app` | Admin/API controllers, routes, ACL (80+ permissions), menus, Form Requests, events |
| MIDDLEWARE | `/unopim-middleware` | Bouncer auth, ScopeMiddleware, SecureHeaders, Passport OAuth2, locale/channel validation |
| CLIENT | `/unopim-client` | Vue.js 3, Blade components (95+), Tailwind (Violet/Cherry), icons, VeeValidate, dark mode |

## Universal Conventions

| Area | Convention |
|------|-----------|
| Models | Extend `Model` or `TranslatableModel`, implement Contract interface |
| Data Access | Repository pattern (`Webkul\Core\Eloquent\Repository`) |
| Business Logic | Service classes, type instances (Strategy pattern) |
| Controllers | Admin: `Webkul\Admin\Http\Controllers\Controller` base |
| API Controllers | `Webkul\AdminApi\Http\Controllers\API\ApiController` base |
| Routes (Admin) | `['middleware' => ['admin'], 'prefix' => config('app.admin_url')]` |
| Routes (API) | `prefix: 'v1/rest'`, middleware: `auth:api, api.scope, accept.json, request.locale` |
| Route Names | Admin: `admin.{module}.{resource}.{action}`, API: `admin.api.{resource}.{action}` |
| ACL Keys | `{module}.{resource}.{action}` (dot notation) |
| Events | `{domain}.{entity}.{action}.{before\|after}` |
| Blade | `<x-admin::component-name>` syntax |
| Vue | `<v-component-name>` globally registered |
| Translations | `@lang('admin::app.{path}')` or `trans('admin::app.{path}')` |
| History | `HistoryAuditable` interface + `HistoryTrait` |
| DB Queries | `GrammarQueryManager::getGrammar()` for cross-DB compatibility |

## Product Values JSON Quick Reference

```json
{
  "common": { "sku": "...", "status": true },
  "locale_specific": { "en_US": { "name": "..." } },
  "channel_specific": { "default": { "price": 29.99 } },
  "channel_locale_specific": { "default": { "en_US": { "meta_title": "..." } } },
  "categories": [1, 2],
  "associations": { "related_products": [], "up_sells": [], "cross_sells": [] }
}
```

## Key File Locations

| What | Where |
|------|-------|
| Packages | `packages/Webkul/` |
| Models | `packages/Webkul/{Package}/src/Models/` |
| Contracts | `packages/Webkul/{Package}/src/Contracts/` |
| Repositories | `packages/Webkul/{Package}/src/Repositories/` |
| Admin Controllers | `packages/Webkul/Admin/src/Http/Controllers/` |
| API Controllers | `packages/Webkul/AdminApi/src/Http/Controllers/API/` |
| Admin Routes | `packages/Webkul/Admin/src/Routes/` |
| API Routes | `packages/Webkul/AdminApi/src/Routes/V1/` |
| ACL Config | `packages/Webkul/Admin/src/Config/acl.php` |
| API ACL Config | `packages/Webkul/AdminApi/src/Config/api-acl.php` |
| Blade Views | `packages/Webkul/Admin/src/Resources/views/` |
| Blade Components | `packages/Webkul/Admin/src/Resources/views/components/` |
| JS Entry | `packages/Webkul/Admin/src/Resources/assets/js/app.js` |
| Tailwind Config | `packages/Webkul/Admin/tailwind.config.js` |
| Migrations | `packages/Webkul/{Package}/src/Database/Migrations/` |
| Tests | `tests/` + `packages/Webkul/{Package}/tests/` |

## Critical Rules Summary

1. ALWAYS use `GrammarQueryManager` for raw SQL - never MySQL-specific queries
2. ALWAYS implement Contract interfaces on models
3. ALWAYS use Repository pattern for data access
4. ALWAYS dispatch before/after events around CRUD operations
5. ALWAYS use `<x-admin::*>` Blade components for UI
6. ALWAYS support dark mode with `dark:` Tailwind variants
7. NEVER manipulate product values JSON directly - use Attribute model methods
8. NEVER manipulate category `_lft`/`_rgt` - use NodeTrait methods
9. NEVER bypass Bouncer/ScopeMiddleware authentication
10. NEVER write new files to the project root - use package directories
