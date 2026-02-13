# UnoPim CLIENT Layer & Design System Skill

Use this skill when working with Vue.js components, Blade templates, Tailwind CSS, forms, DataGrid UI, modals, icons, or dark mode.

## Vue.js 3 Architecture

**Entry:** `packages/Webkul/Admin/src/Resources/assets/js/app.js`
- Creates `window.app` global instance
- Mounts to `#app` on window load

### Plugins (9)
| Plugin | Access | Purpose |
|--------|--------|---------|
| Admin | `$admin.formatPrice()` | Price formatting |
| Axios | `$axios` | HTTP with CSRF |
| CreateElement | `$h`, `$resolveComponent` | Dynamic elements |
| Emitter | `$emitter` | Global event bus |
| Flatpickr | `window.Flatpickr` | Date pickers |
| VeeValidate | `VForm`, `VField`, `VErrorMessage` | Form validation |
| Draggable | `<draggable>` | Drag & drop |
| Multiselect | `<multiselect>` | Multi-select |
| Tribute | `$tribute.init()` | @mentions |

### Directives
`v-slugify` (slug generation), `v-slugify-target` (slug to target), `v-debounce` (500ms default), `v-code` (code editor)

### Register a component globally
```js
// In app.js or plugin
app.component('v-my-component', {
    template: '...',
    props: [...],
    methods: {...}
});
```

## Blade Component Patterns

### Page Layout
```blade
<x-admin::layouts>
    <x-slot:title>Page Title</x-slot>
    <!-- content -->
</x-admin::layouts>
```

### Form Control Group (MANDATORY for all form fields)
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

### 18 Input Types
`text`, `email`, `password`, `number`, `price`, `textarea`, `date`, `datetime`, `file`, `image`, `color`, `select`, `multiselect`, `checkbox`, `radio`, `switch`, `hidden`, + `tinymce` (attribute)

### Form with VeeValidate
```blade
<x-admin::form method="POST" as="div" v-slot="{ meta, errors, handleSubmit }">
    <form @submit="handleSubmit($event, submitHandler)">
        <!-- form control groups -->
        <button type="submit" class="primary-button">Save</button>
    </form>
</x-admin::form>
```

### DataGrid
```blade
<x-admin::datagrid :src="route('admin.my.index')">
    <template #header="{ columns, ... }"><!-- custom header --></template>
    <template #body="{ records, ... }"><!-- custom body --></template>
</x-admin::datagrid>
```

### Modal (4 sizes)
```blade
<x-admin::modal ref="myModal">
    <x-slot:toggle><button>Open</button></x-slot>
    <x-slot:header><p>Title</p></x-slot>
    <x-slot:content><!-- content --></x-slot>
    <x-slot:footer><button class="primary-button">Save</button></x-slot>
</x-admin::modal>
```
Sizes: `small` (400px), `medium` (568px, default), `large` (900px), `full` (calc(100vw-100px))

### Confirm Modal
```js
this.$emitter.emit('open-confirm-modal', {
    agree: () => { /* proceed */ },
    disagree: () => { /* cancel */ }
});
this.$emitter.emit('open-delete-modal', { agree: () => { /* delete */ } });
```

### Flash Messages
```js
this.$emitter.emit('add-flash', { type: 'success', message: 'Done!' });
// Types: success, error, warning, info
```

## Design Tokens

### Colors
```
Primary: violet-50 to violet-700 (Tailwind defaults)
Dark mode: cherry-600 (#353061), cherry-700 (#28273F), cherry-800 (#1F1C30), cherry-900 (#26283D)
Accent: sky-500 (#0C8CE9)
```

### Typography
- **Primary:** Inter (weights: 400-800)
- **Display:** DM Serif Display
- **Icons:** icomoon font (`unopim-admin.woff`)

### Button Classes
```css
.primary-button       /* Violet filled */
.secondary-button     /* White bg, violet border */
.transparent-button   /* Ghost, violet text */
.danger-button        /* Red filled */
```

### Status Labels
```css
.label-pending    /* Yellow */    .label-processing  /* Cyan */
.label-completed  /* Green */     .label-canceled    /* Red */
.label-info       /* Slate */     .label-active      /* Green */
```

### Breakpoints
`sm: 525px`, `md: 768px`, `lg: 1024px`, `xl: 1240px`, `2xl: 1920px`

## Icon System

**Font:** `packages/Webkul/Admin/src/Resources/assets/fonts/unopim-admin.woff`
**Usage:** `<span class="icon-{name}"></span>`

Common icons: `icon-product`, `icon-catalog`, `icon-attribute`, `icon-dashboard`, `icon-settings`, `icon-configuration`, `icon-data-transfer`, `icon-import`, `icon-export`, `icon-magic-ai`, `icon-notification`, `icon-filter`, `icon-edit`, `icon-delete`, `icon-view`, `icon-copy`, `icon-search`, `icon-add-video`, `icon-image`, `icon-file`, `icon-drag`, `icon-manage-column`

## Dark Mode

- **Toggle:** Cookie `dark_mode` (0/1), class `dark` on `<html>`
- **Strategy:** Tailwind `class` mode (`darkMode: 'class'` in config)
- **Event:** `$emitter.emit('change-theme')` notifies plugins
- **Usage:** Always add `dark:` variants for colors: `text-gray-800 dark:text-white`

## VeeValidate

- 27+ locale support, auto-switches with `<html lang>`
- Config: `validateOnBlur: true, validateOnInput: true, validateOnChange: true`
- Custom rules: `phone`, `address`, `decimal`, `required_if`

## DataGrid Filters (7 types)
Boolean, Dropdown, Date Range, DateTime Range, Price, Text, Search

## Key Rules

- ALWAYS use `<x-admin::*>` Blade components - never raw HTML for standard UI elements
- ALWAYS use the form control-group pattern (label + control + error) for form fields
- ALWAYS support dark mode with `dark:` Tailwind variants on new components
- ALWAYS use `icon-{name}` classes from the unopim-admin font - never inline SVGs
- Button actions use predefined classes: `primary-button`, `secondary-button`, `transparent-button`, `danger-button`
- Flash messages via `$emitter.emit('add-flash', {type, message})`
- Confirm dialogs via `$emitter.emit('open-confirm-modal', {agree, disagree})`
- Forms MUST use VeeValidate integration via `<x-admin::form>` with `v-slot`
- Components location: `packages/Webkul/Admin/src/Resources/views/components/`
- Views location: `packages/Webkul/Admin/src/Resources/views/`
