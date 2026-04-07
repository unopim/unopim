---
name: unopim-datagrid
description: >
  Implement Unopim DataGrid classes for listing records in admin panels with
  search, filter, sort, actions (edit/delete), and mass actions. Covers
  DataGrid subdirectory structure (DataGrids/Section/), prepareQueryBuilder
  with DB::table (prefix added by Laravel), addColumn with correct closure patterns,
  addAction with function callbacks for url, addMassAction, bouncer()
  permission checks, and Blade integration. Use this skill when creating a
  DataGrid for credentials, mappings, export history, or any Unopim module
  listing page.
version: "2.0.0"
tags: [unopim, datagrid, laravel, admin, listing, connector, integration]
---

# Unopim DataGrid

## Overview

All DataGrid patterns are derived from the WooCommerce connector reference:
`DataGrids/Credential/CredentialDataGrid.php`.

**Critical rules:**
- DataGrid lives in a **subdirectory**: `DataGrids/{Section}/{Name}DataGrid.php`
- Methods use **PHPDoc `@return`** only — no PHP return type hints on methods
- `closure` callbacks in columns use PHP arrow functions `fn ($row) =>`
- `url` callbacks in actions use **regular function**: `function ($row) { return ...; }`
- Table names without prefix in DB::table() — Laravel adds DB_PREFIX
- Use `bouncer()->hasPermission()` for action permission checks
- Status badges: `class="label-active"` (true) and `class="label-info text-gray-600 dark:text-gray-300"` (false)

**Admin UI rule (critical):**
- If the listing page includes form/filter controls in Blade, use UnoPim form components.
- Do not generate raw `<select>`, `<input>`, `<textarea>`, or `<label>` controls when `x-admin::form.*` components exist.
- Keep labels/placeholders/messages in translations.

---

## 1. Class Structure

```php
<?php
// src/DataGrids/Credential/CredentialDataGrid.php
//                 ^^^^^^^^^ — always a subdirectory named after the entity

namespace Webkul\{ModuleName}\DataGrids\Credential;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        // Use DB::table() without prefix — never Eloquent model here
        $queryBuilder = DB::table('{module}_credentials')
            ->select(
                'id',
                'label',
                'apiUrl',
                'consumerKey',
                'status',
                'created_at',
            );

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('{module-name}::app.credentials.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'apiUrl',
            'label'      => trans('{module-name}::app.credentials.datagrid.api-url'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'consumerKey',
            'label'      => trans('{module-name}::app.credentials.datagrid.consumer-key'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        // Boolean column: use arrow function `fn ($row) =>` for closure
        // Status badges: label-active (true) vs label-info (false)
        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('{module-name}::app.credentials.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->status
                ? '<span class="label-active">' . trans('admin::app.common.yes') . '</span>'
                : '<span class="label-info text-gray-600 dark:text-gray-300">' . trans('admin::app.common.no') . '</span>',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        // Use bouncer()->hasPermission() for ACL checks
        if (bouncer()->hasPermission('{module-slug}.credentials.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                // url uses regular function — NOT arrow function
                'url'    => function ($row) {
                    return route('{module-slug}.credentials.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('{module-slug}.credentials.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('{module-slug}.credentials.destroy', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('{module-slug}.credentials.mass-delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'url'     => route('{module-slug}.credentials.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }

        if (bouncer()->hasPermission('{module-slug}.credentials.mass-edit')) {
            $this->addMassAction([
                'title'  => trans('admin::app.catalog.products.index.datagrid.update-status'),
                'url'    => route('{module-slug}.credentials.mass_update'),
                'method' => 'POST',
                'options' => [
                    [
                        'label' => trans('admin::app.catalog.products.index.datagrid.active'),
                        'value' => true,
                    ],
                    [
                        'label' => trans('admin::app.catalog.products.index.datagrid.disable'),
                        'value' => false,
                    ],
                ],
            ]);
        }
    }
}
```

---

## 2. Callback Patterns: closure vs url

| Location | Function style | Why |
|---|---|---|
| `addColumn` `closure` key | Arrow function `fn ($row) =>` | Short rendering expression |
| `addAction` `url` key | `function ($row) { return ...; }` | Must `return` the URL string |
| `addMassAction` `url` key | Plain string `route(...)` | Static URL, no row context |

### Column closure (arrow function OK):
```php
'closure' => fn ($row) => $row->status ? '<span class="label-active">Yes</span>' : '<span class="label-info ...">No</span>',
```

### Action URL (regular function required):
```php
'url' => function ($row) {
    return route('{module-slug}.credentials.edit', $row->id);
},
```

### Mass action URL (static — no function):
```php
'url' => route('{module-slug}.credentials.mass_delete'),
```

---

## 3. Controller Integration

```php
public function index()
{
    if (request()->ajax()) {
        return app(CredentialDataGrid::class)->toJson();
    }

    return view('{module-name}::credentials.index');
}
```

---

## 4. Blade View

```blade
{{-- resources/views/credentials/index.blade.php --}}
@extends('admin::layouts.master')

@section('page_title')
    {{ __( '{module-name}::app.credentials.index.title' ) }}
@stop

@section('content-wrapper')
    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-4">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ __(' {module-name}::app.credentials.index.title') }}
            </p>

            @if (bouncer()->hasPermission('{module-slug}.credentials.create'))
                <a
                    href="{{ route('{module-slug}.credentials.create') }}"
                    class="primary-button"
                >
                    {{ __(' {module-name}::app.credentials.index.create-btn') }}
                </a>
            @endif
        </div>

        {{-- DataGrid component renders the table --}}
        <x-admin::datagrid :src="route('{module-slug}.credentials.index')" />
    </div>
@stop
```

---

## 5. MappingDataGrid Example

DataGrids for other entities follow the same pattern in their own subdirectory:

```php
<?php
// src/DataGrids/Mapping/MappingDataGrid.php

namespace Webkul\{ModuleName}\DataGrids\Mapping;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MappingDataGrid extends DataGrid
{
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('{module}_mappings as m')
            ->leftJoin('{module}_credentials as c', 'm.credential_id', '=', 'c.id')
            ->select(
                'm.id',
                'm.sku',
                'm.external_id',
                'c.label as credential_label',
                'm.created_at',
            );
    }

    /**
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('{module-name}::app.mappings.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'external_id',
            'label'      => trans('{module-name}::app.mappings.datagrid.external-id'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'credential_label',
            'label'      => trans('{module-name}::app.mappings.datagrid.credential'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);
    }

    /**
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('{module-slug}.mappings.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('{module-slug}.mappings.destroy', $row->id);
                },
            ]);
        }
    }
}
```

---

## 6. Checklist

- [ ] DataGrid file location: `src/DataGrids/{Section}/{Name}DataGrid.php` (subdirectory)
- [ ] Extends `Webkul\DataGrid\DataGrid`
- [ ] Methods have PHPDoc `@return` only — no PHP return type hints on methods
- [ ] `DB::table('{module}_...')` — table names without hardcoded prefix
- [ ] Column `closure` uses arrow function `fn ($row) =>`
- [ ] Action `url` uses regular `function ($row) { return route(...); }`
- [ ] Mass action `url` is plain `route(...)` string (no function)
- [ ] Boolean columns use `label-active` (true) and `label-info text-gray-600 dark:text-gray-300` (false)
- [ ] Permission checks use `bouncer()->hasPermission()`
- [ ] Controller calls `app(DataGridClass::class)->toJson()` for AJAX
- [ ] Blade uses `<x-admin::datagrid :src="route(...)" />`
