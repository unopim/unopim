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
            },

            fontFamily: {
                inter: ['Inter'],
            }
        },
    },

    plugins: [],
}

