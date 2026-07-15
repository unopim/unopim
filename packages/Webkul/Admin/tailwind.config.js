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
                }
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
