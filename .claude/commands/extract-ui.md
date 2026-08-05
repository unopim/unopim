# Extract UnoPim UI → Shadcn + Tailwind CSS (Next.js)

Analyze the UnoPim codebase design system and output a complete, copy-paste-ready UI token file plus Shadcn/Tailwind component equivalents for use in a Next.js app.

## Instructions

When this skill is invoked, do ALL of the following in order:

---

### STEP 1 — Output `tailwind.config.ts` (Drop-in for Next.js)

Output the full Tailwind config that replicates the UnoPim design tokens:

```typescript
// tailwind.config.ts
import type { Config } from "tailwindcss";

const config: Config = {
  darkMode: "class",
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    container: {
      center: true,
      padding: "16px",
      screens: { "2xl": "1920px" },
    },
    screens: {
      sm:  "525px",
      md:  "768px",
      lg:  "1024px",
      xl:  "1240px",
      "2xl": "1920px",
    },
    extend: {
      colors: {
        // Primary brand — Violet
        primary: {
          DEFAULT:    "#7c3aed", // violet-600
          hover:      "#6d28d9", // violet-700
          light:      "#ede9fe", // violet-100
          foreground: "#f9fafb", // gray-50
        },
        // Dark mode surface palette — "Cherry"
        cherry: {
          600: "#353061",
          700: "#28273F",
          800: "#1F1C30",
          900: "#26283D",
        },
        // Accent
        sky: {
          500: "#0C8CE9",
        },
        // Semantic status colours
        status: {
          pending:    "#FACC15", // yellow-400
          processing: "#0891B2", // cyan-600
          completed:  "#16a34a", // green-600
          active:     "#16a34a", // green-600
          closed:     "#2563eb", // blue-600
          canceled:   "#EF4444", // red-500
          fraud:      "#EF4444", // red-500
        },
      },
      fontFamily: {
        sans: ["Inter", "sans-serif"],
        display: ["Poppins", "sans-serif"],
        serif: ["DM Serif Display", "serif"],
      },
      boxShadow: {
        card: [
          "0px 0px 0px 0px rgba(0,0,0,0.03)",
          "0px 1px 1px 0px rgba(0,0,0,0.03)",
          "0px 3px 3px 0px rgba(0,0,0,0.03)",
          "0px 6px 4px 0px rgba(0,0,0,0.02)",
          "0px 11px 4px 0px rgba(0,0,0,0.00)",
          "0px 17px 5px 0px rgba(0,0,0,0.00)",
        ].join(", "),
      },
    },
  },
  plugins: [],
};

export default config;
```

---

### STEP 2 — Output `globals.css` (Font imports + CSS variables)

```css
/* app/globals.css */
@import url("https://fonts.googleapis.com/css2?family=Inter&family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap");

@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    /* Shadcn CSS variable overrides — mapped to UnoPim palette */
    --background:    0 0% 100%;
    --foreground:    222 47% 11%;

    --card:          0 0% 100%;
    --card-foreground: 222 47% 11%;

    --popover:       0 0% 100%;
    --popover-foreground: 222 47% 11%;

    --primary:       263 70% 58%;    /* violet-600 #7c3aed */
    --primary-foreground: 210 40% 98%;

    --secondary:     210 40% 96%;
    --secondary-foreground: 222 47% 11%;

    --muted:         210 40% 96%;
    --muted-foreground: 215 16% 47%;

    --accent:        210 40% 96%;
    --accent-foreground: 222 47% 11%;

    --destructive:   0 84% 60%;      /* red-500 */
    --destructive-foreground: 210 40% 98%;

    --border:        214 32% 91%;
    --input:         214 32% 91%;
    --ring:          263 70% 58%;    /* violet-600 */

    --radius:        0.375rem;       /* rounded-md = 6px */
  }

  .dark {
    --background:    240 15% 15%;    /* cherry-800 #1F1C30 */
    --foreground:    210 40% 98%;    /* slate-50 */

    --card:          240 14% 18%;    /* cherry-900 #26283D */
    --card-foreground: 210 40% 98%;

    --popover:       240 14% 18%;
    --popover-foreground: 210 40% 98%;

    --primary:       263 70% 58%;
    --primary-foreground: 210 40% 98%;

    --secondary:     240 12% 22%;    /* cherry-700 #28273F */
    --secondary-foreground: 210 40% 98%;

    --muted:         240 12% 22%;
    --muted-foreground: 215 20% 65%;

    --accent:        240 12% 22%;
    --accent-foreground: 210 40% 98%;

    --destructive:   0 62% 30%;
    --destructive-foreground: 210 40% 98%;

    --border:        240 12% 25%;    /* dark border */
    --input:         240 12% 25%;
    --ring:          263 70% 58%;
  }
}

@layer utilities {
  /* UnoPim card shadow utility */
  .box-shadow {
    box-shadow:
      0px 0px 0px 0px rgba(0,0,0,0.03),
      0px 1px 1px 0px rgba(0,0,0,0.03),
      0px 3px 3px 0px rgba(0,0,0,0.03),
      0px 6px 4px 0px rgba(0,0,0,0.02),
      0px 11px 4px 0px rgba(0,0,0,0.00),
      0px 17px 5px 0px rgba(0,0,0,0.00);
  }
}
```

---

### STEP 3 — Output Shadcn component overrides

Output these ready-to-use Shadcn component style overrides. Each maps directly to the UnoPim equivalent.

#### Button (`components/ui/button.tsx`)
```tsx
// Shadcn Button with UnoPim variants
import { cva } from "class-variance-authority";

export const buttonVariants = cva(
  // Base — matches UnoPim shared button base
  "inline-flex items-center gap-1 rounded-md text-sm font-semibold transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        // .primary-button
        default:
          "bg-violet-600 border border-violet-700 text-gray-50 hover:bg-violet-500 hover:border-violet-500",
        // .secondary-button
        secondary:
          "bg-white border-2 border-violet-600 text-violet-600 hover:text-violet-500 hover:border-violet-500 hover:bg-violet-100 dark:bg-cherry-900 dark:border-violet-500 dark:text-violet-400",
        // .transparent-button
        ghost:
          "border-2 border-transparent text-violet-600 hover:text-violet-500 hover:bg-violet-50 dark:text-slate-50 dark:hover:bg-cherry-900",
        // .danger-button
        destructive:
          "bg-red-600 text-gray-50 hover:opacity-90",
        // Link style
        link:
          "text-violet-600 underline-offset-4 hover:underline",
      },
      size: {
        default: "px-3 py-1.5",
        sm:      "px-2 py-1 text-xs",
        lg:      "px-4 py-2 text-base",
        icon:    "p-1.5",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);
```

#### Input (`components/ui/input.tsx`)
```tsx
// Matches UnoPim form input styling
export const inputClass =
  "w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm text-gray-600 " +
  "transition hover:border-gray-400 focus:border-gray-400 focus:outline-none " +
  "dark:bg-cherry-900 dark:text-gray-300 dark:border-gray-600 " +
  "aria-[invalid=true]:!border-red-600";
```

#### Badge / Status Labels
```tsx
// Matches UnoPim label-* classes
export const statusVariants = {
  pending:    "bg-yellow-100  text-yellow-800  rounded px-2 py-0.5 text-xs font-semibold",
  processing: "bg-cyan-100    text-cyan-800    rounded px-2 py-0.5 text-xs font-semibold",
  completed:  "bg-green-100   text-green-800   rounded px-2 py-0.5 text-xs font-semibold",
  active:     "bg-green-100   text-green-800   rounded px-2 py-0.5 text-xs font-semibold",
  closed:     "bg-blue-100    text-blue-800    rounded px-2 py-0.5 text-xs font-semibold",
  canceled:   "bg-red-100     text-red-800     rounded px-2 py-0.5 text-xs font-semibold",
  fraud:      "bg-red-100     text-red-800     rounded px-2 py-0.5 text-xs font-semibold",
  info:       "bg-blue-50     text-blue-700    rounded px-2 py-0.5 text-xs font-semibold",
};
```

#### Card
```tsx
// Matches UnoPim bg-white dark:bg-cherry-900 box-shadow border
export const cardClass =
  "bg-white dark:bg-cherry-900 rounded border border-gray-200 dark:border-gray-800 box-shadow p-4";
```

#### Sidebar Layout
```tsx
// Matches UnoPim sidebar: 270px, collapses to 70px
// w-[270px] → data-[collapsed=true]:w-[70px]
export const sidebarClass = {
  shell:   "fixed h-full bg-white dark:bg-cherry-700 transition-all duration-300",
  expanded: "w-[270px]",
  collapsed: "w-[70px]",
  item:    "flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium cursor-pointer " +
           "text-gray-600 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-cherry-800 " +
           "data-[active=true]:bg-violet-100 data-[active=true]:text-violet-700",
};
```

#### Header / Navbar
```tsx
// Matches UnoPim sticky header
export const headerClass =
  "sticky top-0 z-[10001] flex h-14 items-center justify-between border-b " +
  "bg-white dark:bg-cherry-700 dark:border-gray-700 px-4";

// Icon button in header
export const headerIconBtnClass =
  "rounded-md p-1.5 text-2xl text-gray-600 dark:text-slate-200 " +
  "hover:bg-violet-50 dark:hover:bg-cherry-800 transition-colors";
```

#### Switch / Toggle
```tsx
// Matches UnoPim switch styling
export const switchClass = {
  root:  "relative inline-flex h-5 w-9 cursor-pointer rounded-full bg-gray-200 transition-colors " +
         "data-[checked=true]:bg-violet-700",
  thumb: "pointer-events-none block h-4 w-4 translate-x-0.5 rounded-full bg-white shadow " +
         "transition-transform data-[checked=true]:translate-x-[18px]",
};
```

---

### STEP 4 — Output font setup for `next/font`

```tsx
// app/layout.tsx — Next.js font setup
import { Inter, Poppins, DM_Serif_Display } from "next/font/google";

export const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
  display: "swap",
});

export const poppins = Poppins({
  subsets: ["latin"],
  weight: ["400", "500", "600", "700", "800"],
  variable: "--font-poppins",
  display: "swap",
});

export const dmSerifDisplay = DM_Serif_Display({
  subsets: ["latin"],
  weight: "400",
  variable: "--font-dm-serif",
  display: "swap",
});

// Usage in layout
export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${inter.variable} ${poppins.variable} ${dmSerifDisplay.variable}`}>
      <body className="font-sans bg-background text-foreground">{children}</body>
    </html>
  );
}
```

---

### STEP 5 — Output breakpoint reference table

| Tailwind key | px | Use case |
|-------------|-----|---------|
| `sm`        | 525px  | Large mobile / small tablet |
| `md`        | 768px  | Tablet portrait |
| `lg`        | 1024px | Laptop / tablet landscape |
| `xl`        | 1240px | Desktop |
| `2xl`       | 1920px | Wide / ultrawide monitor |

---

### STEP 6 — Output color reference cheat-sheet

```
PRIMARY
  violet-600  #7c3aed   → primary buttons, active states, focus rings
  violet-700  #6d28d9   → button borders, checked checkboxes/radios/switches
  violet-500  #8b5cf6   → hover state
  violet-100  #ede9fe   → active nav item background, button hover bg
  violet-50   #f5f3ff   → ghost button hover, icon button hover (light)

DARK MODE SURFACES
  cherry-900  #26283D   → card/input backgrounds
  cherry-800  #1F1C30   → page background
  cherry-700  #28273F   → sidebar, header backgrounds
  cherry-600  #353061   → elevated surfaces

TEXT
  gray-600    #4b5563   → body text (light)
  slate-50    #f8fafc   → body text (dark)
  gray-300    #d1d5db   → input text (dark)

BORDERS
  gray-300    #d1d5db   → default input border (light)
  gray-400    #9ca3af   → hover/focus border (light)
  gray-800    #1f2937   → card border (dark)
  gray-600    #4b5563   → input border (dark)

STATUS
  yellow-400  #FACC15   → pending
  cyan-600    #0891B2   → processing
  green-600   #16a34a   → completed / active
  blue-600    #2563eb   → closed / info
  red-500     #EF4444   → canceled / fraud / danger

ACCENT
  sky-500     #0C8CE9   → secondary accent / links
```
