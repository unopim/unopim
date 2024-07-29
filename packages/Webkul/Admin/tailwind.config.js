/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.js"],

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
