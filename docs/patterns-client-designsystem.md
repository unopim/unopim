# UnoPim - CLIENT Layer & Design System Patterns & Skills

> Reference documentation for the Client architectural layer and Design System.
> Generated from exhaustive codebase scan - 2026-02-08

---

## Table of Contents

1. [Vue.js 3 Application Architecture](#1-vuejs-3-application-architecture)
2. [Blade Component System](#2-blade-component-system)
3. [Core UI Components](#3-core-ui-components)
4. [Tailwind CSS Design System](#4-tailwind-css-design-system)
5. [Icon System](#5-icon-system)
6. [VeeValidate Form Validation](#6-veevalidate-form-validation)
7. [DataGrid Filter System](#7-datagrid-filter-system)
8. [Dark Mode Implementation](#8-dark-mode-implementation)
9. [Page Template Patterns](#9-page-template-patterns)
10. [Advanced Components](#10-advanced-components)
11. [JavaScript Plugins](#11-javascript-plugins)
12. [Navigation & Sidebar](#12-navigation--sidebar)
13. [CSS Design Tokens](#13-css-design-tokens)
14. [Responsive Design](#14-responsive-design)
15. [Internationalization (i18n)](#15-internationalization-i18n)
16. [API Communication](#16-api-communication)
17. [Event System](#17-event-system)
18. [Form Control Groups](#18-form-control-groups)
19. [Performance Optimizations](#19-performance-optimizations)
20. [Summary of File Locations](#20-summary-of-file-locations)

---

## 1. Vue.js 3 Application Architecture

**Main Entry Point:**
- File: `packages/Webkul/Admin/src/Resources/assets/js/app.js`

**Architecture Pattern:**
- Uses Vue 3 ESM bundler (`vue/dist/vue.esm-bundler`)
- Creates a global app instance: `window.app`
- Mounts to DOM element with id `#app` on window load event
- Global error handling via form validation with `onInvalidSubmit` hook

**Global Plugins Registration:**

| # | Plugin | Purpose | Access Via |
|---|--------|---------|------------|
| 1 | **Admin** | Price formatting utilities | `$admin.formatPrice()` |
| 2 | **Axios** | HTTP client with CSRF token support | `$axios` |
| 3 | **CreateElement** | Vue h() function and resolveComponent | `$h` and `$resolveComponent` |
| 4 | **Emitter** | Global event bus (mitt-based) | `$emitter` |
| 5 | **Flatpickr** | Date picker plugin | `window.Flatpickr` |
| 6 | **VeeValidate** | Form validation with multi-language support | VForm, VField, VErrorMessage |
| 7 | **Draggable** | vuedraggable component registration | `<draggable>` component |
| 8 | **Multiselect** | vue-multiselect component registration | `<multiselect>` component |
| 9 | **Tribute** | @ mention support | `$tribute.init(config)` |

**Global Directives:**

| Directive | Description |
|-----------|-------------|
| `v-slugify` | Auto-converts input to slug format (lowercase, spaces to dashes) |
| `v-slugify-target` | Converts input to slug and updates target element with underscores |
| `v-debounce` | Debounces input events (default 500ms, configurable) |
| `v-code` | Code editor integration |

---

## 2. Blade Component System

### Main Layout

- **File:** `packages/Webkul/Admin/src/Resources/views/components/layouts/index.blade.php`
- **Props:** `$title` (page title)
- **Structure:**

```blade
<x-admin::layouts>
    <x-slot:title>Page Title</x-slot>
    <!-- Page content -->
</x-admin::layouts>
```

### Layout Features

- Dark mode toggle via cookie (`dark_mode`) - applied to `<html>` element class
- Responsive grid layout with sidebar and main content area
- Header with logo, dark mode switcher, notifications, user profile dropdown
- Sidebar with collapsible navigation (cookie: `sidebar_collapsed`)
- Dynamic third-level menu tabs
- Flash message component (`<x-admin::flash-group />`)
- Confirm modal component (`<x-admin::modal.confirm />`)

---

## 3. Core UI Components

### Form Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/form/index.blade.php`
- **Props:** `method` (POST/GET/PUT/PATCH/DELETE), inherits all attributes
- **Slots:** Default slot for form content
- **Features:**
  - Supports custom form handling via `as="div"` attribute
  - Traditional forms with CSRF token and method spoofing
  - Form-level error handling with `v-slot="{ meta, errors, setValues }"`
  - Invalid submit event: `@invalid-submit="onInvalidSubmit"`
  - VeeValidate integration

**Usage Example:**

```blade
<x-admin::form method="POST" as="div" v-slot="{ meta, errors, handleSubmit }">
    <form @submit="handleSubmit($event, create)">
        <!-- Form fields -->
    </form>
</x-admin::form>
```

### DataGrid Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/datagrid/index.blade.php`
- **Props:** `src` (API endpoint), `isMultiRow` (boolean)
- **Slots:** `header`, `body`
- **Features:**
  - Dynamic data loading from backend API
  - Pagination with configurable per-page options
  - Multi-column sorting (single column at a time)
  - Advanced filtering with filter drawer
  - Mass actions with selection modes (none, partial, all)
  - Column management and visibility toggle
  - Search functionality
  - Local storage persistence of filter/sort/pagination state

**Key Methods:**

| Method | Description |
|--------|-------------|
| `get(extraParams)` | Fetch data from backend |
| `changePage(directionOrPageNumber)` | Navigate pages |
| `changePerPageOption(option)` | Change items per page |
| `sortPage(column)` | Toggle sort order |
| `filterPage($event, column, additional)` | Apply filters |
| `performMassAction(action, option)` | Execute mass actions |

**Data Structure:**

```javascript
available: {
    id: String,
    columns: Array<{index, label, type, sortable, filterable}>,
    actions: Array<{url, method, icon}>,
    massActions: Array<{title, icon, method, options}>,
    records: Array<Object>,
    meta: {total, current_page, last_page, from, to, per_page}
}

applied: {
    pagination: {page, perPage},
    sort: {column, order: 'asc'|'desc'},
    filters: {columns: [{index, value}]},
    massActions: {meta: {mode, action}, indices, value}
}
```

### Modal Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/modal/index.blade.php`
- **Props:** `isActive` (boolean), `type` ('small'|'medium'|'large'|'full'), `clip` (boolean)
- **Slots:** `toggle`, `header`, `content`, `footer`
- **Features:**
  - Smooth transitions and animations
  - Responsive sizing with predefined breakpoints
  - Overflow detection and scrolling support
  - Modal size can be changed via event: `$emitter.emit('modal-size-change', size)`

**Size Map:**

| Size | Max Width |
|------|-----------|
| small | `max-w-[400px]` |
| medium | `max-w-[568px]` (default) |
| large | `max-w-[900px]` |
| full | `max-w-[calc(100vw-100px)]` |

**Methods:**

| Method | Description |
|--------|-------------|
| `toggle()` | Toggle open/close |
| `open()` | Force open |
| `close()` | Force close |

### Confirm Modal Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/modal/confirm.blade.php`
- **Events:**
  - `open-confirm-modal` - Standard confirmation
  - `open-delete-modal` - Danger confirmation with delete styling

**Usage:**

```javascript
this.$emitter.emit('open-confirm-modal', {
    title: 'Confirm Action',
    message: 'Are you sure?',
    options: {
        btnDisagree: 'Cancel',
        btnAgree: 'Confirm',
        btnAgreeClass: 'primary-button',
        btnDisagreeClass: 'transparent-button'
    },
    agree: () => { /* Handle agreement */ },
    disagree: () => { /* Handle disagreement */ }
});
```

### Dropdown Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/dropdown/index.blade.php`
- **Props:** `position` ('bottom-left'|'bottom-right'|'top-left'|'top-right')
- **Slots:** `toggle`, `content`, `menu`
- **Features:**
  - Positioned relative to toggle element
  - Click-outside handling for closing
  - Smooth transitions
  - RTL support

**Usage:**

```blade
<x-admin::dropdown position="bottom-right">
    <x-slot:toggle>
        <button>Menu</button>
    </x-slot:toggle>
    <x-slot:menu>
        <x-admin::dropdown.menu.item>Option 1</x-admin::dropdown.menu.item>
    </x-slot:menu>
</x-admin::dropdown>
```

### Flash Message Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/flash-group/index.blade.php`
- **Types:** `success`, `error`, `warning`, `info`
- **Features:**
  - Global message queue
  - Auto-dismiss after 5 seconds
  - RTL-aware positioning (right for LTR, left for RTL)
  - Styled toast notifications with icons

**Flash Type Styles:**

| Type | Container Color | Message Color | Icon Color |
|------|----------------|---------------|------------|
| success | `#059669` (green) | `#FFFFFF` | `#059669` |
| error | `#EF4444` (red) | `#FFFFFF` | `#EF4444` |
| warning | `#FACC15` (yellow) | `#1F2937` | `#FACC15` |
| info | `#0284C7` (blue) | `#FFFFFF` | `#0284C7` |

**Flash Type Icons:**

| Type | Icon Class |
|------|------------|
| success | `icon-done` |
| error | `icon-cancel` |
| warning | `icon-information` |
| info | `icon-processing` |

**Usage:**

```javascript
this.$emitter.emit('add-flash', {
    type: 'success',
    message: 'Operation completed!'
});
```

---

## 4. Tailwind CSS Design System

**Configuration File:** `packages/Webkul/Admin/tailwind.config.js`

### Custom Colors

```javascript
cherry: {
    600: '#353061',
    700: '#28273F',
    800: '#1F1C30',    // Dark theme background
    900: '#26283D'
},
sky: {
    500: '#0C8CE9'     // Primary accent
}
```

### Typography

| Font | Purpose | Weights |
|------|---------|---------|
| **Inter** | Primary Font | 400, 500, 600, 700, 800 |
| **DM Serif Display** | Display Font | - |
| **icomoon** | Icon Font | `unopim-admin.woff` |

### Dark Mode

- **Implementation:** Tailwind `class` strategy
- **Root class:** `dark` applied to `<html>` element
- **Toggled via cookie:** `dark_mode` (0 or 1)
- **Dark-specific colors:** use `dark:` prefix

### Screen Breakpoints

| Breakpoint | Width |
|------------|-------|
| sm | 525px |
| md | 768px |
| lg | 1024px |
| xl | 1240px |
| 2xl | 1920px |

### Button Classes

```css
.primary-button       /* Violet bg, hover effects */
.secondary-button     /* White bg, violet border */
.transparent-button   /* No bg, violet text */
.danger-button        /* Red bg */
```

### Status / Label Classes

```css
/* Status Labels */
.label-pending                        /* Yellow badge */
.label-processing                     /* Cyan badge */
.label-completed / .label-active      /* Green badge */
.label-closed                         /* Blue badge */
.label-canceled / .label-fraud        /* Red badge */
.label-info                           /* Slate badge */

/* Status Text */
.status-enable                        /* Green text */
.status-disable                       /* Red text */

/* Priority Pills */
.pill-low                             /* Red pill */
.pill-medium                          /* Yellow pill */
.pill-high                            /* Green pill */
```

### Visual Effects

```css
.box-shadow          /* Elevation shadow with border */
.shimmer             /* Skeleton loading animation */
.draggable-ghost     /* 50% opacity, light indigo bg */
```

### Scrollbar Styling

- Custom journal scrollbar with border and rounded corners

---

## 5. Icon System

**Font File:** `packages/Webkul/Admin/src/Resources/assets/fonts/unopim-admin.woff`

### Usage

```html
<span class="icon-product"></span>
```

### Color States

| State | Color |
|-------|-------|
| Light mode | Gray-600 |
| Dark mode | Slate-50 |
| Active state | Violet-700 |

### Available Icon Classes

```
icon-product           icon-add-video
icon-information       icon-down
icon-up                icon-left
icon-right             icon-pause
icon-done              icon-checkbox-partial
icon-star              icon-play
icon-image             icon-cancel
icon-processing        icon-dot
icon-down-stat         icon-up-stat
icon-collapse          icon-checkbox-check
icon-channel           icon-language
icon-radio-normal      icon-radio-selected
icon-view-close        icon-calendar
icon-catalog           icon-checkbox-normal
icon-chevron-down      icon-chevron-left
icon-chevron-right     icon-chevron-up
icon-configuration     icon-copy
icon-dark              icon-notification
icon-dashboard         icon-data-transfer
icon-delete            icon-edit
icon-export            icon-view
icon-file              icon-attribute
icon-filter            icon-folder-block
icon-folder            icon-import
icon-light             icon-magic-ai
icon-menu              icon-search
icon-setting           icon-drag
icon-at                icon-manage-column
icon-toast-done
```

---

## 6. VeeValidate Form Validation

**Setup File:** `packages/Webkul/Admin/src/Resources/assets/js/plugins/vee-validate.js`

### Global Components

| Component | Purpose |
|-----------|---------|
| `VForm` | Form wrapper component |
| `VField` | Input field wrapper |
| `VErrorMessage` | Error message display |

### Standard Validators

All standard VeeValidate rules are available (required, email, min, max, etc.)

### Custom Rules

| Rule | Description |
|------|-------------|
| `phone` | Phone number validation (optional + format) |
| `address` | Address with multi-character support |
| `decimal` | Decimal number with configurable precision |
| `required_if` | Conditional required validation |

### Multi-Language Support

- 27+ locales supported
- Custom phone number error messages in each language
- Dynamic locale switching based on `<html lang>` attribute

### Configuration

```javascript
validateOnBlur: true,
validateOnInput: true,
validateOnChange: true
```

---

## 7. DataGrid Filter System

### Filter Types

| Type | Description |
|------|-------------|
| **Boolean** | Dropdown selection |
| **Dropdown** | Basic or advanced options |
| **Date Range** | From/To date pickers |
| **DateTime Range** | From/To datetime pickers |
| **Price** | Currency dropdown + amount input |
| **Text** | Text search |
| **Search** | Global search across all columns |

### Filter Tags

- Applied filters shown as removable violet chips
- "Clear all" link resets column filters

---

## 8. Dark Mode Implementation

### Toggle Component

- **File:** `packages/Webkul/Admin/src/Resources/views/components/layouts/header/index.blade.php`
- **Component:** `<v-dark>`

**Icon Classes:**

| Mode | Icon |
|------|------|
| Light mode | `icon-dark` (moon icon) |
| Dark mode | `icon-light` (sun icon) |

### Mechanism

- Cookie-based persistence (`dark_mode`)
- Class applied to `<html>` root element
- Theme emitter event: `change-theme` for plugin notifications
- Logo switching based on theme

### Dark-Specific CSS

- Uses Tailwind `dark:` prefix throughout
- Cherry color scheme for dark backgrounds
- Automatic text color inversion

---

## 9. Page Template Patterns

### Products Index Page

- **File:** `packages/Webkul/Admin/src/Resources/views/catalog/products/index.blade.php`
- **Structure:**
  - Page title with icon
  - Toolbar: Export button, Create button
  - DataGrid with multi-row support
  - Create Product modal
  - Bulk edit modal

### Dashboard Page

- **File:** `packages/Webkul/Admin/src/Resources/views/dashboard/index.blade.php`
- **Structure:**
  - User greeting with date/time
  - Catalog details section
  - Catalog structure section
  - Optional completeness module

### Layout Wrapper

- All pages use: `<x-admin::layouts>` component
- Provides consistent header, sidebar, breadcrumbs

---

## 10. Advanced Components

### Accordion Component

- **File:** `components/accordion/index.blade.php`
- Collapsible section support

### Tree Component

- **Files:** `components/tree/*.blade.php`
- Supports checkbox and radio selection
- Hierarchical category/attribute display

### Tabs Component

- **File:** `components/tabs/index.blade.php`
- Multiple tab views with slot content

### History Component

- **Files:** `components/history/*.blade.php`
- Audit trail and version history display

### Media Components

| Component | Purpose |
|-----------|---------|
| `components/media/gallery.blade.php` | Image gallery |
| `components/media/images.blade.php` | Image upload/selection |
| `components/media/videos.blade.php` | Video media |
| `components/media/files.blade.php` | File uploads |

### Bulk Edit Grid

- **Files:** `components/bulkedit/*.blade.php`
- Inline editing with multiple field types
- Supported field types: Date, text, textarea, boolean, select, multiselect, gallery, image

---

## 11. JavaScript Plugins

### Tribute Plugin

- @ mention/autocomplete support
- Custom template and styling

**Configuration:**

```javascript
$tribute.init({
    values: [{key: 'name', value: 'value'}],
    trigger: '@',
    selectClass: 'highlighted-tribute-item',
    menuItemTemplate: (item) => `<div>${item.original.key}</div>`
})
```

### Flatpickr Plugin

- Date picker with theme support
- Automatically loads dark theme CSS when needed
- Responds to `change-theme` event

### Multiselect Plugin

- Vue-multiselect component wrapper
- Custom styling with violet highlights
- Dark mode support

### Draggable Plugin

- vuedraggable component registration
- Drag-and-drop support for lists

---

## 12. Navigation & Sidebar

### Sidebar Features

- **File:** `packages/Webkul/Admin/src/Resources/views/components/layouts/sidebar/index.blade.php`
- Collapsible sidebar with smooth transitions
- Menu items with active state highlighting (violet-700)
- Submenu support with popup on hover
- Icon + label for expanded state
- Icon-only for collapsed state
- Collapse toggle button at bottom
- Cookie-based persistence: `sidebar_collapsed`
- Collapsed class: `sidebar-collapsed` on container
- Width: 270px expanded, 70px collapsed

### Header Features

- Logo with dark mode support
- Hamburger menu for mobile
- Dark mode switcher
- Notification icon
- User profile dropdown with:
  - Version info
  - My Account link
  - Logout button

---

## 13. CSS Design Tokens

### Spacing

- **Base:** 4px (Tailwind default)
- **Common gaps:** 1.5 (6px), 2 (8px), 2.5 (10px), 4 (16px)

### Padding / Margin

| Context | Classes |
|---------|---------|
| Form controls | `px-2.5 py-1.5` |
| Modals / Cards | `px-4 py-3` |
| Containers | `px-4 py-2.5` |

### Border Radius

| Context | Class |
|---------|-------|
| Buttons / Cards | `rounded-md` |
| Modals | `rounded-lg` |
| Fully rounded | `rounded-full` |

### Z-Index Layers

| Layer | Z-Index |
|-------|---------|
| Sidebar | `z-[1000]` |
| Modals | `z-[10001]` to `z-[10003]` |
| Dropdowns | `z-10` |
| Tooltips | `z-[99999]` |

### Transitions

| Speed | Class |
|-------|-------|
| Standard | `transition-all duration-300` |
| Fast | `duration-200` |
| Slow | `duration-300` |

---

## 14. Responsive Design

### Mobile-First Approach

- Max breakpoint: `max-sm:flex-wrap` (stack on small screens)
- Hide on mobile: `max-lg:hidden`
- Grid auto-layout: `grid-template-columns: repeat(${gridsCount}, minmax(80px, 1fr))`
- RTL support: Uses `ltr:` and `rtl:` Tailwind prefixes

---

## 15. Internationalization (i18n)

### Translation System

- Laravel Blade `@lang()` helper
- Namespace pattern: `admin::app.section.subsection.key`
- Multi-language validation messages via VeeValidate localization
- Dynamic locale switching on page load

**Example Usage:**

```blade
@lang('admin::app.catalog.products.index.title')
@lang('admin::app.dashboard.index.user-name', ['user_name' => $name])
```

---

## 16. API Communication

### Axios Setup

- CSRF token auto-added via `X-Requested-With` header
- Global instance: `this.$axios`
- Base URL from meta tag: `<meta name="base-url">`
- Currency code from meta tag: `<meta name="currency-code">`

### DataGrid API Contract

```javascript
// Request
GET /api/endpoint
Params:
  - pagination: {page, per_page}
  - sort: {column, order}
  - filters: {[column]: [values]}

// Response
{
  id, columns, actions, mass_actions,
  search_placeholder, records, meta,
  manageableColumn, managedColumns
}
```

---

## 17. Event System

### Global Event Bus

- **Provider:** mitt-based emitter via `$emitter`

### Event Names

| Event | Purpose |
|-------|---------|
| `add-flash` | Show toast notification |
| `open-confirm-modal` | Confirmation dialog |
| `open-delete-modal` | Delete confirmation |
| `change-datagrid` | DataGrid state changed |
| `change-theme` | Theme switched |
| `modal-size-change` | Modal size changed |

---

## 18. Form Control Groups

### Pattern

```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label class="required">
        Field Label
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control>
        <!-- Input field -->
    </x-admin::form.control-group.control>

    <x-admin::form.control-group.error>
        <!-- Error message -->
    </x-admin::form.control-group.error>
</x-admin::form.control-group>
```

---

## 19. Performance Optimizations

### Local Storage

- DataGrid state persistence key: `datagrids`
- Stores: pagination, sorting, filtering, column visibility
- Survives page reloads

### Lazy Loading

- Shimmer components for skeleton screens
- Images with fallback placeholders
- Dynamic Vue component registration

### Asset Management

- Glob-based image/font preloading
- Conditional CSS loading for date picker themes
- Tree-shaking via ESM bundler

---

## 20. Summary of File Locations

### Core Architecture

```
packages/Webkul/Admin/src/Resources/assets/js/
  ├── app.js (entry point)
  ├── plugins/ (9 global plugins)
  │   ├── admin.js
  │   ├── axios.js
  │   ├── createElement.js
  │   ├── draggable.js
  │   ├── emitter.js
  │   ├── flatpickr.js
  │   ├── multiselect.js
  │   ├── tribute.js
  │   └── vee-validate.js
  └── directives/ (4 custom directives)
      ├── code.js
      ├── debounce.js
      ├── slugify.js
      └── slugify-target.js
```

### UI Components

```
packages/Webkul/Admin/src/Resources/views/components/
  ├── layouts/ (header, sidebar, tabs, main layout)
  ├── form/ (form wrapper + control groups)
  ├── datagrid/ (table, toolbar, filters, export)
  ├── modal/ (modal, confirm)
  ├── dropdown/ (dropdown menu)
  ├── flash-group/ (toast notifications)
  ├── media/ (images, videos, files, gallery)
  ├── tree/ (hierarchical menus)
  ├── tabs/ (tab panels)
  ├── accordion/ (collapsible sections)
  ├── bulkedit/ (inline editing grid)
  ├── shimmer/ (skeleton screens)
  └── [40+ additional components]
```

### Design System

```
packages/Webkul/Admin/tailwind.config.js (color scheme, fonts, screens)
packages/Webkul/Admin/src/Resources/assets/css/app.css (Tailwind + custom styles, icons)
packages/Webkul/Admin/src/Resources/assets/fonts/unopim-admin.woff (custom icon font)
```
