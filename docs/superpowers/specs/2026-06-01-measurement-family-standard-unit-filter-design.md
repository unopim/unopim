# Multi-word filter for the Standard Unit column

**Date:** 2026-06-01
**Package:** `Webkul/Measurement`
**File:** `packages/Webkul/Measurement/src/DataGrids/MeasurementFamilyDataGrid.php`

## Problem

In the Measurement Families datagrid, filtering the **Standard Unit** column works for
single-word terms but fails for multi-word terms:

- `"Cubic"` → filter works.
- `"Cubic meter"` → filter returns no results.

## Root cause

The Standard Unit column's *display* and *filter* operate on two different things:

- **Displayed value** is a human label resolved by a PHP closure, e.g. the code
  `CUBIC_METER` is rendered as `"Cubic meter"`.
- **Filtered value** is the raw DB column `measurement_families.standard_unit`, which
  stores the underscore-separated, uppercase **code** (`CUBIC_METER`).

The base `DataGrid` STRING filter runs `orWhere(column, 'LIKE', '%value%')`, so:

| Typed term     | SQL                       | Stored value  | Result                                   |
|----------------|---------------------------|---------------|------------------------------------------|
| `Cubic`        | `LIKE '%Cubic%'`          | `CUBIC_METER` | matches (substring, case-insensitive)    |
| `Cubic meter`  | `LIKE '%Cubic meter%'`    | `CUBIC_METER` | no match — space cannot match underscore |

It is not about word count: the multi-word label the user sees (`Cubic meter`) never
matches the underscore-separated code that is actually stored (`CUBIC_METER`).

## Approach (chosen)

Normalize the typed `standard_unit` filter term(s) into the stored code form before the
parent filter logic runs. The displayed label and stored code are mirror forms
(`"Cubic meter"` ⇄ `CUBIC_METER`), so transforming the input lets the existing `LIKE`
match the code.

Override `processRequestedFilters()` in `MeasurementFamilyDataGrid`:

```php
public function processRequestedFilters(array $requestedFilters)
{
    if (isset($requestedFilters['standard_unit'])) {
        $requestedFilters['standard_unit'] = array_map(
            fn ($value) => strtoupper(str_replace(' ', '_', $value)),
            $requestedFilters['standard_unit']
        );
    }

    return parent::processRequestedFilters($requestedFilters);
}
```

Behaviour:

- `"Cubic meter"` → `CUBIC_METER` → matches `CUBIC_METER`.
- `"Cubic"` → `CUBIC` → still matches `CUBIC_METER` (single-word preserved).
- `"Meter per second"` → `METER_PER_SECOND` → matches.

Normalizing to the canonical uppercase code form also makes the filter work under
PostgreSQL's case-sensitive `LIKE`.

## Scope and boundaries

- Only the `standard_unit` filter values are transformed. The `labels`, `code`, and `id`
  filters are passed through unchanged to the parent.
- No database schema, migration, or query-builder changes.
- No change to column display, sorting, or other datagrids.

## Trade-offs

- Relies on the convention that a unit's `standard_unit` code is the uppercase /
  underscore form of its label — true for all seeded families. A hand-created family
  whose code diverges from its label would not match when filtered by label. This is
  acceptable for the current data model. (A more accurate but heavier alternative —
  filtering against the locale-aware label stored in the `units` JSON with driver-specific
  JSON SQL — was considered and rejected as overkill for this fix.)

## Testing

Extend the feature tests in
`packages/Webkul/Measurement/tests/Feature/MeasurementFamilyTest.php`:

- Filtering the families datagrid by `standard_unit = "Cubic meter"` returns the Volume
  family (standard unit `CUBIC_METER`).
- Filtering by `standard_unit = "Cubic"` still returns the Volume family (single-word
  behaviour preserved).
- Filtering by a non-matching multi-word term returns no families.
