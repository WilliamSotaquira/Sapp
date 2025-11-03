/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                'custom': {
                    'primary': '#E41E2A',
                    'primary-dark': '#AD1C22',
                    'white': '#FFFFFF',
                    'black': '#231F20',
                    'gray-dark': '#6D6E70',
                    'gray-light': '#F5F5F5',
                    'gray-medium': '#E5E5E5',
                    'blue-dark': '#0A3069',
                    'yellow': '#F2B705',
                }
            },
            fontFamily: {
                sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
}
