---
name: unopim-connector-export-mapping
description: >
  Handle module attribute mapping and mapping history management in Unopim.
  Use this skill when creating module attribute mapping UI, mapping controller,
  mapping history, or restricting fields to module structure. Only Attribute
  Mapping and History tabs are allowed. Covers DB_PREFIX (auto-added by Laravel), Migration/
  singular folder, HistoryTrait on models, flat ACL, cURL HTTP client, and
  JsonResponse controller pattern.
version: "2.0.0"
tags: [unopim, module, mapping, export, attribute, history, connector]
---

# module Export Mapping Standard (Unopim)

You are a senior Unopim backend engineer.

This skill applies when:

- Creating module Attribute Mapping UI
- Creating module mapping controller
- Removing Custom Field Mapping tab
- Restricting mapping fields to module structure
- Implementing mapping history

---

## 1. Allowed Tabs (STRICT)

## Admin UI Rule (CRITICAL)

When generating or updating admin Blade forms, always use UnoPim Blade components.

- Use `x-admin::form.control-group` wrappers.
- Use `x-admin::form.control-group.label` for labels.
- Use `x-admin::form.control-group.control` for inputs/selects/textareas.
- Use `x-admin::form.control-group.error` for validation errors.
- Do not generate raw `<select>`, `<input>`, `<textarea>`, or `<label>` controls when a component equivalent exists.
- Keep all user-facing text in translation keys.

For select dropdowns, use `type="select"` with `:options="json_encode(...)"`, `track-by`, `label-by`, and Vue `@input` event binding.

module Export Mapping must contain ONLY:

- ✅ Attribute Mapping
- ✅ History

❌ Remove Custom Fields Mapping  
❌ Remove Other Mapping  
❌ Do NOT create extra tabs

UI must match:

```
Attribute Mapping | History
```

---

## 2. Route Structure (STRICT UNOPIM STANDARD)

Must follow `config('app.admin_url')` with `middleware => ['admin']`.
(The `web` middleware is already applied in the ServiceProvider's
`Route::middleware('web')->group(...)` — do NOT repeat it in routes.)

```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('module')->group(function () {
        Route::prefix('export-mapping')->group(function () {

            Route::controller(AttributeMappingController::class)
                ->prefix('attribute-mapping')
                ->group(function () {
                    Route::get('', 'index')
                        ->name('module.export_mappings.attribute_mapping.index');
                    Route::post('save', 'store')
                        ->name('module.export_mappings.attribute_mapping.store');
                });

            Route::controller(MappingHistoryController::class)
                ->group(function () {
                    Route::get('history', 'index')
                        ->name('module.export_mappings.history');
                });
        });
    });
});
```

---

## 3. module Attribute Mapping Fields (STRICT FIELD SET)

| module Field              | Required Unopim Attribute Type     |
| ------------------------- | ---------------------------------- |
| EAN                       | text                               |
| Product Code              | text OR number                     |
| Vendor (Brand)            | simple select OR text              |
| Name                      | text                               |
| Title                     | text                               |
| Description               | textarea                           |
| Short Description         | textarea                           |
| Summary Description       | textarea                           |
| Short Summary Description | textarea                           |
| Pictures                  | image (multiple selection allowed) |

❌ Do NOT allow unsupported attribute types  
❌ Do NOT allow dynamic field injection  
❌ Do NOT allow additional fields

---

## 4. Attribute Type Validation Rule (MANDATORY)

```php
$allowedTypes = [
    'ean'                       => ['text'],
    'product_code'              => ['text', 'number'],
    'vendor'                    => ['select', 'text'],
    'name'                      => ['text'],
    'title'                     => ['text'],
    'description'               => ['textarea'],
    'short_description'         => ['textarea'],
    'summary_description'       => ['textarea'],
    'short_summary_description' => ['textarea'],
    'pictures'                  => ['image'],
];
```

If mismatch → throw validation error with field name.

---

## 5. Pictures Attribute Rule

- Allow selecting image-type attributes only
- Allow multiple images (multi-select)
- Store as JSON array
- Validate attribute type = image on save

---

## 6. Database Structure

### Table: `module_attribute_mappings`

| Column                | Type       | Notes               |
| --------------------- | ---------- | ------------------- |
| id                    | bigInt PK  |                     |
| attribute_code        | string     | module field key    |
| unopim_attribute_id   | bigInt FK  |                     |
| default_value         | json null  | Optional defaults   |
| created_at / updated_at | timestamps |                  |

For pictures: store multiple attribute IDs as JSON array.

### Table: `module_mapping_histories`

| Column      | Type       | Notes                    |
| ----------- | ---------- | ------------------------ |
| id          | bigInt PK  |                          |
| action_type | string     | create / update          |
| user_id     | bigInt FK  |                          |
| payload     | json       | Full snapshot of mapping |
| created_at  | timestamp  |                          |

---

## 7. Store Flow

1. Validate attribute type compatibility
2. Validate all required fields are mapped (EAN, Product Code, Name, Description)
3. Store mapping (upsert)
4. Save history snapshot

---

## 8. Required Mandatory Fields

Must be mapped before saving:

- EAN
- Product Code
- Name
- Description

If any missing → block save with validation error.

---

## 9. Controller Requirements

Generate two controllers:

- `AttributeMappingController` — index + store
- `MappingHistoryController` — index only

Rules:
- No DB logic in controllers
- Use repository injection
- Validate types before storing
- Log history on every save

---

## 10. UI Rules

Table columns: `module Field | Unopim Field | Default Value`

- Default value column is optional per field
- Pictures field must render multi-select dropdown (image attributes only)
- Use Unopim admin blade components

---

## 11. Security & Validation Rules

- Only admin users via `middleware(['admin'])` (NOT `['web', 'admin']`)
- Validate attribute exists in Unopim
- Validate attribute type against allowed set
- Prevent duplicate mappings per attribute_code
- Prevent invalid attribute IDs
- Log errors with `Log::error()`

---

## 12. Output Requirements

When generating module Export Mapping module, MUST generate:

- Migration: `module_attribute_mappings`
- Migration: `module_mapping_histories`
- Model + Contract interface for each table
- Repository for each model
- `AttributeMappingController`
- `MappingHistoryController`
- Validation logic (type-aware)
- Routes file (strict Unopim structure)
- Blade view (exactly 2 tabs)
- ACL entry in `acl.php`
- Menu entry in `menu.php`

---

## FINAL RULE

module export mapping must be:

- Minimal (2 tabs only)
- Strictly validated with type-checking
- Attribute-type restricted
- History-tracked on every change
- Unopim route compliant
- Clean and repository-driven
