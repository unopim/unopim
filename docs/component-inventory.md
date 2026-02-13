# UnoPim - Component Inventory

## Overview

UnoPim's UI is built with **95+ reusable Blade components** (using Laravel's `<x-admin::*>` syntax), enhanced with **Vue.js 3** for interactivity. All components support dark mode and are located in `packages/Webkul/Admin/src/Resources/views/components/`.

---

## Component Categories

### Layout Components

| Component | Path | Description |
|-----------|------|-------------|
| `<x-admin::layouts>` | `layouts/index.blade.php` | Main authenticated layout (sidebar, header, content) |
| `<x-admin::layouts.anonymous>` | `layouts/anonymous.blade.php` | Guest/unauthenticated layout |
| `<x-admin::layouts.tabs>` | `layouts/tabs.blade.php` | Third-level menu tabs |
| `<x-admin::layouts.header>` | `layouts/header/` | Top navigation bar with notifications, search |
| `<x-admin::layouts.sidebar>` | `layouts/sidebar/` | Collapsible side navigation |
| `<x-admin::layouts.with-history>` | `layouts/with-history/` | Layout with version history panel |

### DataGrid (Enterprise Data Table)
**Files:** 7 templates | **907+ lines of Vue.js logic**

| Component | Description |
|-----------|-------------|
| `<x-admin::datagrid>` | Main datagrid container with server-side pagination, sorting, filtering |
| Toolbar | Search, filter toggles, mass action dropdown, export |
| Table | Responsive data table with sortable columns |
| Filters | Advanced filtering (date range, price range, text, select, boolean) |
| Manage Columns | Column visibility and ordering |
| Export | CSV/Excel export |

**Key Features:** LocalStorage state persistence, mass actions with confirmation, quick filters, real-time search.

### Form Controls (18 Input Types)

| Type | Blade Usage | Description |
|------|-------------|-------------|
| text | `type="text"` | Standard text input |
| email | `type="email"` | Email with validation |
| password | `type="password"` | Masked password field |
| number | `type="number"` | Numeric input |
| price | `type="price"` | Currency input with symbol |
| textarea | `type="textarea"` | Multi-line text |
| date | `type="date"` | Date picker (Flatpickr) |
| datetime | `type="datetime"` | DateTime picker (Flatpickr) |
| file | `type="file"` | Single file upload |
| image | `type="image"` | Image upload with preview |
| color | `type="color"` | Color picker |
| select | `type="select"` | Dropdown (vue-multiselect) |
| multiselect | `type="multiselect"` | Multi-select dropdown |
| checkbox | `type="checkbox"` | Boolean checkbox |
| radio | `type="radio"` | Radio button |
| switch | `type="switch"` | Toggle switch |
| hidden | `type="hidden"` | Hidden field |
| tinymce | attribute `tinymce` | Rich text editor (TinyMCE) |

**Usage Pattern:**
```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label class="required">
        Field Name
    </x-admin::form.control-group.label>
    <x-admin::form.control-group.control
        type="text"
        name="field_name"
        rules="required"
        :value="old('field_name')"
    />
    <x-admin::form.control-group.error control-name="field_name" />
</x-admin::form.control-group>
```

### Bulk Edit Grid (9 Templates)
| Component | Description |
|-----------|-------------|
| Grid | Main bulk edit container |
| Header | Column headers |
| Row/Cell | Data rows and individual cells |
| Editor | Inline cell editor |
| Type editors | text, textarea, boolean, date, select, multiselect, image, gallery |

### Media Components (5)
| Component | Description |
|-----------|-------------|
| `<x-admin::media.images>` | Image gallery uploader (drag & drop, multi-file) |
| `<x-admin::media.videos>` | Video uploader |
| `<x-admin::media.gallery>` | Media gallery viewer |
| `<x-admin::media.file>` | Single file uploader |
| `<x-admin::media.files>` | Multiple file manager |

### Modal & Drawer
| Component | Description |
|-----------|-------------|
| `<x-admin::modal>` | Modal dialog (header, content, footer slots) |
| `<x-admin::drawer>` | Side panel (filters, actions) |
| Confirm modal | Confirmation dialog (globally registered) |

### Navigation
| Component | Description |
|-----------|-------------|
| `<x-admin::dropdown>` | Dropdown menu with items |
| `<x-admin::tabs>` | Tabbed interface (route-based activation) |
| `<x-admin::accordion>` | Collapsible accordion sections |

### Tree View (6 Templates)
| Component | Description |
|-----------|-------------|
| `<x-admin::tree>` | Hierarchical data display (categories) |
| Category tree | Category-specific tree variant |
| Checkbox/Radio | Selection modes |

### Table (6 Templates)
| Component | Description |
|-----------|-------------|
| `<x-admin::table>` | HTML table with thead/tbody, sortable columns |

### Shimmer/Loading States (11)
Skeleton screens for: datagrid, tabs, tree, tinymce, product edit, accordion, image, dashboard, header, families, range-slider.

### Flash Messages
| Component | Description |
|-----------|-------------|
| `<x-admin::flash-group>` | Toast notification system (success, error, warning, info) |

### Utility Components
| Component | Description |
|-----------|-------------|
| `<x-admin::flat-picker>` | Date/DateTime pickers (2 types) |
| `<x-admin::tinymce>` | Rich text editor wrapper |
| `<x-admin::select>` | Custom select component |
| `<x-admin::seo>` | SEO metadata fields |
| History components | Version tracking display (4 files) |
| Graph components | Charts and graphs |
| Data transfer components | Import/export UI (2 files) |

---

## JavaScript Architecture

### Vue.js 3 Application

**Entry:** `packages/Webkul/Admin/src/Resources/assets/js/app.js`

### Plugins (9)
| Plugin | Purpose |
|--------|---------|
| admin.js | Admin helper utilities |
| axios.js | HTTP client with interceptors |
| createElement.js | Dynamic element creation |
| emitter.js | Global event bus |
| flatpickr.js | Date/time picker with locale support |
| vee-validate.js | Form validation (33 locales, 390 lines) |
| draggable.js | SortableJS drag & drop |
| multiselect.js | Vue-multiselect with dark mode |
| tribute.js | @mentions autocomplete |

### Directives (4)
| Directive | Purpose |
|-----------|---------|
| v-slugify | Auto-generate URL slugs |
| v-slugify-target | Target field for slug generation |
| v-debounce | Debounce input events |
| v-code | Code editor enhancement |

---

## Styling System

### Tailwind CSS Configuration
- **Primary:** Violet palette (`violet-50` to `violet-700`)
- **Dark Mode:** Cherry palette (`cherry-700` to `cherry-900`)
- **Dark mode:** Toggle via cookie, `dark:` variant prefix

### Custom Icon Font
**Font:** `unopim-admin` (WOFF format, 50+ glyphs)

Key icons: product, catalog, attribute, dashboard, settings, configuration, data-transfer, import, export, magic-ai, notification, filter, edit, delete, view, copy, chevron-*, calendar, search.

### Button Classes
| Class | Description |
|-------|-------------|
| `.primary-button` | Violet filled |
| `.secondary-button` | Violet outlined |
| `.transparent-button` | Ghost button |
| `.danger-button` | Red destructive |

### Status Labels
| Class | Color | Use |
|-------|-------|-----|
| `.label-pending` | Yellow | Pending state |
| `.label-processing` | Cyan | In progress |
| `.label-completed` | Green | Success |
| `.label-canceled` | Red | Failed/canceled |
| `.label-info` | Slate | Informational |

---

## Template Count by Area

| Area | Templates |
|------|----------|
| Components | ~95 |
| Catalog views | ~35 |
| Settings views | ~25 |
| Configuration views | ~5 |
| Dashboard views | ~3 |
| Account views | ~2 |
| Email templates | ~5 |
| **Total (Admin)** | **~166** |
| Installer | 6 |
| Other packages | ~7 |
| **Grand Total** | **~179** |
