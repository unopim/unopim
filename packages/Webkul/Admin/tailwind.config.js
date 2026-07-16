/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.css", "./src/Resources/**/*.js"],

    theme: {
        container: {
            center: true,

            screens: {
                "2xl": "1920px",
            },

            padding: {
                DEFAULT: "16px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1920px",
        },

        extend: {
            colors: {
                cherry: {
                    600: '#353061',
                    700: '#28273F',
                    800: '#1F1C30',
                    900: '#26283D',
                },
                sky: {
                    500: '#0C8CE9',
                },
                unopim: {
                    'primary-50': '#F5F3FF',
                    'primary-100': '#EDE9FE',
                    'primary-200': '#DDD6FE',
                    'primary-400': '#A78BFA',
                    'primary-500': '#8B5CF6',
                    'primary-600': '#7C3AED',
                    'primary-700': '#6D28D9',
                    'primary-900': '#4C1D95',
                    primary: '#6D28D9',
                    'primary-base': '#7C3AED',
                    'primary-hover': '#8B5CF6',
                    'primary-soft': '#F5F3FF',
                    'primary-page': '#FAF9FF',
                    'primary-muted': '#EDE9FE',
                    'primary-border': '#DDD6FE',
                    avatar: '#A78BFA',
                    accent: '#35C2B4',
                },

                /*
                 * Semantic design tokens. Values come from CSS custom properties
                 * (see assets/css/app.css :root / .dark) in `R G B` form so the
                 * `<alpha-value>` placeholder still lets `/opacity` modifiers work
                 * (e.g. `bg-success/10`). Prefer these over raw palette shades for
                 * intent-carrying UI (status, primary action, charts) — they are
                 * dark-mode aware and overridable by third parties via one CSS var.
                 */
                primary: {
                    DEFAULT: 'rgb(var(--c-primary-600) / <alpha-value>)',
                    50: 'rgb(var(--c-primary-50) / <alpha-value>)',
                    100: 'rgb(var(--c-primary-100) / <alpha-value>)',
                    200: 'rgb(var(--c-primary-200) / <alpha-value>)',
                    300: 'rgb(var(--c-primary-300) / <alpha-value>)',
                    400: 'rgb(var(--c-primary-400) / <alpha-value>)',
                    500: 'rgb(var(--c-primary-500) / <alpha-value>)',
                    600: 'rgb(var(--c-primary-600) / <alpha-value>)',
                    700: 'rgb(var(--c-primary-700) / <alpha-value>)',
                    800: 'rgb(var(--c-primary-800) / <alpha-value>)',
                    900: 'rgb(var(--c-primary-900) / <alpha-value>)',
                },
                'primary-hover': 'rgb(var(--c-primary-700) / <alpha-value>)',
                success: 'rgb(var(--c-success) / <alpha-value>)',
                warning: 'rgb(var(--c-warning) / <alpha-value>)',
                danger: 'rgb(var(--c-danger) / <alpha-value>)',
                info: 'rgb(var(--c-info) / <alpha-value>)',
            },

            fontFamily: {
                inter: ['Inter'],
                icon: ['icomoon']
            }
        },
    },

    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        }
    ]
};
