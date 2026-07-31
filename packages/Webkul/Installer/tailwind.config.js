/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.js"],

    theme: {
        container: {
            center: true,

            screens: {
                'xl': '1366px',
            },

            padding: {
                DEFAULT: '16px',
            },
        },

        screens: {
            sm: '525px',
            md: '768px',
            lg: '1024px',
            xl: '1366px',
        },

        extend: {
            colors: {
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
                warning: 'rgb(var(--c-warning) / <alpha-value>)',
                'cloud-start': 'var(--c-cloud-start)',
                'cloud-end': 'var(--c-cloud-end)',
            },

            boxShadow: {
                'cloud-cta': 'var(--s-cloud-cta)',
                'step-card': 'var(--s-step-card)',
            },

            fontFamily: {
                inter: ['Inter'],
            }
        },
    },

    plugins: [],
}

