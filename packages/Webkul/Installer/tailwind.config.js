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
                'cloud-start': '#5B41D6',
                'cloud-end': '#8367F0',
            },

            fontFamily: {
                inter: ['Inter'],
            }
        },
    },

    plugins: [],
}

