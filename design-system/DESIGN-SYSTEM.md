# UnoPim Design System
> Reverse-engineered from `packages/Webkul/Admin` — ready to drop into any Next.js + Tailwind + Shadcn project.

---

## Files

| File | Purpose |
|------|---------|
| `tailwind.config.ts` | Drop-in Tailwind config with all tokens |
| `globals.css` | Font imports, CSS variables, component utility classes |
| `components/button.tsx` | Button (primary / secondary / ghost / danger) |
| `components/input.tsx` | Input, Label, Textarea, ControlGroup |
| `components/badge.tsx` | Status badges + priority pills |
| `components/card.tsx` | Card with header/content/footer |
| `components/layout.tsx` | AppShell, Header, Sidebar, SidebarItem |
| `components/modal.tsx` | Modal with sizes sm/md/lg/full |
| `components/switch.tsx` | Boolean toggle |
| `components/table.tsx` | Table, row, cell, action button |
| `components/tabs.tsx` | Tab navigation |
| `components/accordion.tsx` | Collapsible accordion |
| `components/drawer.tsx` | Slide-in drawer / panel |
| `components/shimmer.tsx` | Skeleton loading states |

---

## Color Tokens

### Primary (Violet)
| Token | Hex | Usage |
|-------|-----|-------|
| violet-600 | `#7c3aed` | Primary buttons, active states, focus rings |
| violet-700 | `#6d28d9` | Button borders, checked controls (switch, checkbox) |
| violet-500 | `#8b5cf6` | Button hover |
| violet-400 | `#a78bfa` | Avatar/initials background |
| violet-100 | `#ede9fe` | Active nav bg, secondary button hover fill |
| violet-50  | `#f5f3ff` | Ghost hover bg, icon hover (light), page bg tint |

### Dark Mode Surfaces ("Cherry")
| Token | Hex | Usage |
|-------|-----|-------|
| cherry-900 | `#26283D` | Card / input backgrounds |
| cherry-800 | `#1F1C30` | Page background |
| cherry-700 | `#28273F` | Sidebar, header backgrounds |
| cherry-600 | `#353061` | Elevated surfaces |

### Status Colors
| Status | Hex | Tailwind |
|--------|-----|---------|
| Pending | `#EAB308` | yellow-500 |
| Processing | `#0891B2` | cyan-600 |
| Completed / Active | `#16a34a` | green-600 |
| Closed / Info | `#2563eb` | blue-600 |
| Canceled / Fraud | `#EF4444` | red-500 |
| Generic info | `#94a3b8` | slate-400 |

### Text
| Context | Hex | Tailwind |
|---------|-----|---------|
| Body (light) | `#4b5563` | gray-600 |
| Body (dark) | `#f8fafc` | slate-50 |
| Input (dark) | `#d1d5db` | gray-300 |
| Label (light) | `#1f2937` | gray-800 |
| Muted | `#6b7280` | gray-500 |

### Borders
| Context | Hex | Tailwind |
|---------|-----|---------|
| Input (light) | `#9ca3af` | gray-400 |
| Input hover/focus (light) | `#9ca3af` | gray-400 |
| Input (dark) | `#4b5563` | gray-600 |
| Input hover (dark) | `#cbd5e1` | slate-300 |
| Card (light) | `#e5e7eb` | gray-200 |
| Card/row (dark) | `#26283D` | cherry-900 |

---

## Typography

| Role | Font | Weights | Class |
|------|------|---------|-------|
| Body / UI | Inter | 400, 500, 600, 700 | `font-sans` |
| Icons | icomoon (custom) | — | `font-icon` |

**Base size:** `text-sm` (14px) for all UI text
**Labels:** `text-xs font-medium`
**Icon size in header/actions:** `text-2xl`

### Google Fonts import URL
```
https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap
```

---

## Spacing Conventions

| Usage | Classes |
|-------|---------|
| Button padding | `px-3 py-1.5` |
| Icon button | `p-1.5` |
| Input padding | `px-3 py-2.5` |
| Card / section padding | `px-4 py-4` |
| Modal header/footer | `px-4 py-3` / `px-4 py-2.5` |
| Sidebar item | `px-3 py-2` |
| Form label bottom margin | `mb-1.5` |
| Gap in button row | `gap-x-1` |
| Gap in nav items | `gap-2` |

---

## Shadows

### Card shadow (subtle, 6-layer)
```css
box-shadow:
  0px 0px 0px 0px rgba(0,0,0,0.03),
  0px 1px 1px 0px rgba(0,0,0,0.03),
  0px 3px 3px 0px rgba(0,0,0,0.03),
  0px 6px 4px 0px rgba(0,0,0,0.02),
  0px 11px 4px 0px rgba(0,0,0,0.00),
  0px 17px 5px 0px rgba(0,0,0,0.00);
```
Tailwind class: `shadow-card`

### Dropdown / modal shadow (stronger)
```css
box-shadow:
  0px 8px 10px 0px rgba(0,0,0,0.20),
  0px 6px 30px 0px rgba(0,0,0,0.12),
  0px 16px 24px 0px rgba(0,0,0,0.14);
```
Tailwind class: `shadow-dropdown`

### Sidebar shadow
```css
box-shadow: 0px 8px 10px 0px rgba(0,0,0,0.20);
```
Tailwind class: `shadow-sidebar`

---

## Breakpoints

| Key | px | Use case |
|-----|----|---------|
| `sm` | 525px | Large mobile / small tablet |
| `md` | 768px | Tablet portrait |
| `lg` | 1024px | Laptop / tablet landscape |
| `xl` | 1240px | Desktop |
| `2xl` | 1920px | Ultrawide |

---

## Icon System

**Font:** `icomoon` (custom, shipped as `unopim-admin.woff`)
**Class prefix:** `icon-`
**Usage:** `<i className="icon-edit text-2xl text-gray-600 dark:text-slate-50" />`

### Available Icons (65+)
```
icon-product        icon-edit           icon-delete         icon-view
icon-add-video      icon-export         icon-import         icon-copy
icon-information    icon-filter         icon-search         icon-setting
icon-down / up      icon-left / right   icon-chevron-*      icon-menu
icon-pause          icon-done           icon-cancel         icon-drag
icon-star           icon-play           icon-image          icon-file
icon-processing     icon-dot            icon-collapse       icon-folder
icon-checkbox-normal/check/partial      icon-radio-normal/selected
icon-channel        icon-language       icon-calendar       icon-catalog
icon-view-close     icon-configuration  icon-dark / light   icon-notification
icon-dashboard      icon-data-transfer  icon-attribute      icon-magic-ai
icon-down-stat      icon-up-stat        icon-at             icon-manage-column
icon-folder-block
```

---

## Dark Mode

**Implementation:** `class` strategy — add `dark` to `<html>`
**Persistence:** Cookie `dark_mode` (`0` = light, `1` = dark)

```tsx
// Toggle dark mode
document.documentElement.classList.toggle("dark");
document.cookie = "dark_mode=1; path=/";
```

---

## Z-index Scale

| Layer | Value | Element |
|-------|-------|---------|
| Sidebar | 1000 | Fixed sidebar |
| Header | 10001 | Sticky header |
| Modal/Drawer backdrop | 10001 | Background overlay |
| Modal/Drawer panel | 10002 | The panel itself |
| Tooltip | 10003 | Tooltips on top of modals |

---

## Animations

### Shimmer (loading skeleton)
```css
@keyframes shimmer {
  0%   { background-position: -1000px 0; }
  100% { background-position: 1000px 0; }
}
animation: shimmer 2.2s infinite linear;
background: linear-gradient(to right, #f6f7f8 4%, #edeef1 25%, #f6f7f8 36%);
```

### Modal transitions
- Enter: `ease-out 300ms`
- Leave: `ease-in 200ms`

### Dropdown transitions
- Enter: `ease-out 100ms` + scale from 95%
- Leave: `ease-in 75ms`

### Sidebar
- `transition-all duration-300` on width change

---

## Usage in Next.js

### 1. Install deps
```bash
npm install tailwindcss-animate class-variance-authority clsx tailwind-merge
```

### 2. Copy files
```
design-system/tailwind.config.ts  →  tailwind.config.ts
design-system/globals.css          →  app/globals.css
design-system/components/*         →  components/ui/
```

### 3. Add fonts to `app/layout.tsx`
```tsx
import { Inter } from "next/font/google";

const inter = Inter({ subsets: ["latin"], variable: "--font-inter" });

export default function RootLayout({ children }) {
  return (
    <html lang="en" className={inter.variable}>
      <body className="font-sans">{children}</body>
    </html>
  );
}
```

### 4. Copy icon font
```
packages/Webkul/Admin/src/Resources/assets/fonts/unopim-admin.woff
→  public/fonts/unopim-admin.woff
```
The `globals.css` already declares the `@font-face` pointing to `/fonts/unopim-admin.woff`.

### 5. Add `cn` utility
```ts
// lib/utils.ts
import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
```
