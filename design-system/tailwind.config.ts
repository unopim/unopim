// tailwind.config.ts
// Generated from UnoPim admin design system reverse-engineering
import type { Config } from "tailwindcss";

const config: Config = {
  darkMode: "class", // Toggle via html.dark class (cookie: dark_mode=1)
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    // ─── Breakpoints ────────────────────────────────────────────────────────────
    screens: {
      sm:    "525px",   // large mobile / small tablet
      md:    "768px",   // tablet portrait
      lg:    "1024px",  // laptop / tablet landscape
      xl:    "1240px",  // desktop
      "2xl": "1920px",  // ultrawide
    },
    container: {
      center: true,
      padding: "16px",
      screens: { "2xl": "1920px" },
    },
    extend: {
      // ─── Color Palette ───────────────────────────────────────────────────────
      colors: {
        // Primary brand — Violet
        primary: {
          DEFAULT:    "#7c3aed", // violet-600 — buttons, active states, focus rings
          hover:      "#8b5cf6", // violet-500 — button hover
          border:     "#6d28d9", // violet-700 — button borders, checked controls
          light:      "#ede9fe", // violet-100 — active nav bg, button hover fill
          subtle:     "#f5f3ff", // violet-50  — ghost hover bg, icon hover (light)
          muted:      "#a78bfa", // violet-400 — avatar/initials background
          foreground: "#f8fafc", // slate-50   — text on primary bg
        },

        // Dark-mode surface palette — "Cherry" (custom to UnoPim)
        cherry: {
          600: "#353061", // elevated surface (dark)
          700: "#28273F", // sidebar, header bg (dark)
          800: "#1F1C30", // page background (dark)
          900: "#26283D", // card/input background (dark)
        },

        // Status / semantic colours
        status: {
          pending:    "#EAB308", // yellow-500  — pending
          processing: "#0891B2", // cyan-600    — processing
          completed:  "#16a34a", // green-600   — completed
          active:     "#16a34a", // green-600   — active
          closed:     "#2563eb", // blue-600    — closed / info
          canceled:   "#EF4444", // red-500     — canceled
          fraud:      "#EF4444", // red-500     — fraud
          info:       "#94a3b8", // slate-400   — generic info
        },

        // Pill severity
        pill: {
          low:    { bg: "#dc2626", border: "#b91c1c" }, // red-600 / red-700
          medium: { bg: "#EAB308", border: "#ca8a04" }, // yellow-500 / yellow-600
          high:   { bg: "#16a34a", border: "#15803d" }, // green-600 / green-700
        },
      },

      // ─── Typography ─────────────────────────────────────────────────────────
      fontFamily: {
        sans:  ["Inter", "sans-serif"],   // body text
        icon:  ["icomoon", "sans-serif"], // custom icon font (prefix: icon-)
      },

      // ─── Shadows ─────────────────────────────────────────────────────────────
      boxShadow: {
        // Card / panel elevation (subtle, used on white surface)
        card: [
          "0px 0px 0px 0px rgba(0,0,0,0.03)",
          "0px 1px 1px 0px rgba(0,0,0,0.03)",
          "0px 3px 3px 0px rgba(0,0,0,0.03)",
          "0px 6px 4px 0px rgba(0,0,0,0.02)",
          "0px 11px 4px 0px rgba(0,0,0,0.00)",
          "0px 17px 5px 0px rgba(0,0,0,0.00)",
        ].join(", "),

        // Dropdown / popover (stronger directional shadow)
        dropdown: [
          "0px 8px 10px 0px rgba(0,0,0,0.20)",
          "0px 6px 30px 0px rgba(0,0,0,0.12)",
          "0px 16px 24px 0px rgba(0,0,0,0.14)",
        ].join(", "),

        // Sidebar drawer shadow
        sidebar: "0px 8px 10px 0px rgba(0,0,0,0.20)",
      },

      // ─── Border Radius ───────────────────────────────────────────────────────
      borderRadius: {
        DEFAULT: "0.375rem", // rounded-md = 6px — used on all controls
      },

      // ─── Z-index Scale ───────────────────────────────────────────────────────
      zIndex: {
        header:  "10001", // sticky header
        modal:   "10002",
        tooltip: "10003",
      },
    },
  },
  plugins: [],
};

export default config;
