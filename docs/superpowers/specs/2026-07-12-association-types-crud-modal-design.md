# Association Types — CRUD UX Refresh (Modal Create + Edit Config + History)

**Date:** 2026-07-12
**Branch:** `feature/configurable-associations`
**Status:** Approved design — sub-project 1 detailed; 2 & 3 outlined (own specs later)

## Goal

Make association-type management fast and safe for a product manager:

- **Create** is a lightweight **modal** on the index page asking for the **code only**.
- After create the user lands on the **edit page** to configure labels, per-link fields, options, status, position.
- Edit page gains **history** (audit trail).
- The product-edit **links UI** becomes easier and component-based.
- **Security** and **performance** are first-class in every sub-project.

## Scope decomposition

Delivered as three sequenced sub-projects, each its own spec → plan → build. Build order **1 → 2 → 3**.

| # | Sub-project | Summary |
|---|-------------|---------|
| 1 | Create-modal + edit redirect | Code-only modal on index; `store()` returns JSON redirect to edit; validation split; full-page create removed. **(detailed below)** |
| 2 | History on edit page | `HistoryTrait` + presenter on `AssociationType` **and** `AssociationTypeField`/options (full depth); `with-history` layout on edit. |
| 3 | Product-links UX + shared components | Reusable `<v-product-associations>` component; easier link picker (search-as-you-type, thumbnails, dedupe); reuse `link-fields`. |

Security + performance are woven into each sub-project, not a separate phase.

---

## Sub-project 1 — Create-modal + edit redirect (detailed)

### Decisions (locked)

- Modal collects **`code` only**. No label inputs (labels are per-locale, configured on edit).
- New type is **active immediately** (`status = 1`).
- `position` **auto-assigned** = `max(position) + 1`.
- Default-locale `name` is **seeded to `code`** on create so the grid label is never blank; user edits it later.

### Flow

```
Index page ──[+ Create]──▶ modal (code only) ──ajax POST──▶ store()
                                                              │ is_user_defined = 1
                                                              │ status = 1
                                                              │ position = max+1 (when absent)
                                                              │ seed default-locale name = code
                                                              ▼
                                              JSON { redirect_url: edit/{id}, message }
                                                              │
                                              JS redirect ──▶ edit page (labels, fields, config)
```

### Changes

**1. `catalog/associations/types/index.blade.php`**
- Add a `+ Create` button that toggles a `<x-admin::modal>`.
- Modal hosts an ajax `<x-admin::form>` with a single `code` control (`v-code`) + hidden `status=1`.
- On success, JS redirects to `response.data.redirect_url`.
- **Repurpose** `catalog/associations/types/create.blade.php`: replace its old full-page body with the code-only **modal partial**, `@include`d by `index.blade.php` (component-first, reusable).

**2. `AssociationTypeController@store`**
- Return `JsonResponse{ redirect_url, message }` (ajax-modal contract, mirrors Product create) instead of `redirect()->route('...index')`.
- Force `is_user_defined = 1`, `status = 1`.
- `position = max(position) + 1` when the request omits it (single `max()` query).
- Seed default-locale `name = code` (via the locale payload passed to the repository `create`).
- Keep event dispatches (`catalog.association_type.create.before/after`).

**3. Remove full-page create**
- Delete `AssociationTypeController@create`.
- Remove GET `admin.catalog.association_types.create` route.
- `catalog/associations/types/create.blade.php` is **repurposed** to the modal partial (see change 1), not deleted.
- **ACL:** `packages/Webkul/Admin/src/Config/acl.php` has two `catalog.association_types.create` entries — one pointing at the removed GET `create` route and one at `store`. Drop the GET-`create` one; keep the `store`-mapped entry so the permission key survives.

**4. `AssociationTypeRequest`**
- **Create path** (`! $this->route('id')`): `code` required + `Code` + `AssociationNotSupportedFields` + `unique`. Locale label rules **not** applied. `fields.*` rules skipped (modal has no fields).
- **Update path**: unchanged — per-locale `name` required, `code`/`is_user_defined` immutable, field/option rules apply.
- Concretely: move the `foreach activeLocales => {locale}.name required` block so it only runs on the **update** path.

**5. Edit page**
- Structurally unchanged in this sub-project (labels + field-builder already present).
- Default-locale label prefills from the seeded `name` (= code) and is freely editable.

### Security / performance

- `bouncer()` create-permission gate honored (ACL key `catalog.association_types.create` retained).
- `code`: `Code` rule + `unique` + existing unique/index on `association_types.code` block dupes and injection.
- Auto-position uses one `max()` query — no N+1.
- CSRF handled by `<x-admin::form>`.
- Mass-delete / mass-update already guard `is_user_defined` (unchanged).

### Tests (Pest — mandatory, written first per bug/feature TDD)

1. Store with `code` only → **200 JSON**, `redirect_url` targets the edit route, row persisted with `status=1`, non-null auto `position`, `is_user_defined=1`, default-locale `name == code`.
2. Store missing `code` → **422**.
3. Store duplicate `code` → **422**.
4. **Regression:** store no longer requires locale labels (old rule relaxed).
5. GET `create` route removed → **404** (or route-not-defined assertion).
6. Update still requires per-locale labels (unchanged behavior).

### Definition of done

- Pest suite green (`vendor/bin/pest`).
- `vendor/bin/pint` run + `--test` clean.
- New translation keys added in `en_US` first, propagated to **all 33 locales** (natural translations, params preserved); `php artisan unopim:translations:check` passes.
- Playwright: create-modal → edit-redirect happy path (UI flow touched).

---

## Sub-project 2 — History (outline, own spec later)

- Add `HistoryContract`, `PresentableHistoryInterface`, `HistoryTrait` to `AssociationType` (`$historyTags`, `$auditExclude`, static `getPresenters()` → `AssociationTypeHistoryPresenter`).
- Full depth: same treatment on `AssociationTypeField` (+ options) so field/option add/remove/edit is audited.
- Edit view wraps in `<x-admin::layouts.with-history>`.
- Presenter maps code/label/status/position/field attributes to translated history rows.

## Sub-project 3 — Product-links UX + shared components (outline, own spec later)

- Extract a reusable `<v-product-associations>` Vue component driving all active types from config.
- Easier picker: search-as-you-type, product thumbnails, dedupe already-linked, clear per-link field inline (reuse `components/associations/link-fields.blade.php`).
- Keep the string-serialization contract for checkbox/multiselect link fields (documented in `link-fields.blade.php`).
