import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Modules/*/resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // The logo's amber ("primary" alone is 500); 700 and up are for text on white.
                primary: {
                    DEFAULT: '#f8a925',
                    50: '#fef8ec',
                    100: '#fdeccd',
                    200: '#fbd896',
                    300: '#f9c35f',
                    400: '#f8b23f',
                    500: '#f8a925',
                    600: '#d8881c',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
