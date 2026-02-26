---
name: unopim-datagrid
description: "DataGrid development for UnoPim admin panel. Activates when creating or modifying listing pages, adding columns, actions, filters, or mass actions; or when the user mentions datagrid, listing, table, columns, filters, sorting, or grid."
license: MIT
metadata:
  author: unopim
---

# UnoPim DataGrid Development

DataGrids power all listing pages in the UnoPim admin panel. All DataGrids extend `Webkul\DataGrid\DataGrid`.

## When to Use This Skill

Invoke this skill when:

- Creating a new listing page
- Adding columns, actions, or mass actions to an existing DataGrid
- Customizing DataGrid filtering or sorting
- Extending DataGrid with custom renderers

## Creating a DataGrid

```php
<?php

namespace Webkul\Example\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ExampleDataGrid extends DataGrid
{
    /**
     * Prepare the query builder.
     */
    public function prepareQueryBuilder()
    {
        return DB::table('examples')
            ->select('id', 'code', 'name', 'status', 'created_at');
    }

    /**
     * Prepare columns for display.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.common.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('example::app.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('example::app.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('example::app.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->status
                ? '<span class="label-active">' . trans('admin::app.common.active') . '</span>'
                : '<span class="label-inactive">' . trans('admin::app.common.inactive') . '</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('admin::app.common.created-at'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions (per-row buttons).
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'title'  => trans('admin::app.common.edit'),
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.example.edit', $row->id),
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => trans('admin::app.common.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.example.delete', $row->id),
        ]);
    }

    /**
     * Prepare mass actions (bulk operations).
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'title'  => trans('admin::app.common.delete'),
            'method' => 'POST',
            'url'    => route('admin.example.mass_delete'),
        ]);
    }
}
```

## Column Types

| Type | Usage |
|---|---|
| `string` | Text values, searchable |
| `integer` | Numeric values |
| `boolean` | True/false display |
| `date` | Date values |
| `datetime` | Date-time values |
| `price` | Currency values |

## Column Properties

| Property | Type | Description |
|---|---|---|
| `index` | string | DB column name (required) |
| `label` | string | Display label (required) |
| `type` | string | Column type (required) |
| `searchable` | bool | Include in text search |
| `filterable` | bool | Show in filter panel |
| `sortable` | bool | Allow sorting |
| `closure` | Closure | Custom renderer |
| `options` | array | Filter dropdown options |

## Blade Usage

```blade
<x-admin::datagrid :src="route('admin.example.index')" />
```

## Existing DataGrids

| DataGrid | Package |
|---|---|
| `AttributeDataGrid` | Admin (Catalog) |
| `AttributeFamilyDataGrid` | Admin (Catalog) |
| `CategoryDataGrid` | Admin (Catalog) |
| `ProductDataGrid` | Admin (Catalog) |
| `ChannelDataGrid` | Admin (Settings) |
| `CurrencyDataGrid` | Admin (Settings) |
| `LocalesDataGrid` | Admin (Settings) |
| `RolesDataGrid` | Admin (Settings) |
| `UserDataGrid` | Admin (Settings) |
| `ImportDataGrid` | Admin (DataTransfer) |
| `ExportDataGrid` | Admin (DataTransfer) |
