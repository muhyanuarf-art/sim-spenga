/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            colors: {
                brand: {
                    50: '#eef7ff', 100: '#d9ecff', 200: '#bcdfff', 300: '#8ecbff',
                    400: '#59acff', 500: '#3388fd', 600: '#1c68f2', 700: '#1553de',
                    800: '#1844b3', 900: '#193c8c',
                },
            },
            boxShadow: { soft: '0 2px 10px 0 rgba(20, 30, 60, 0.06)' },
        },
    },
    plugins: [],
};
