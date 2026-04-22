import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#f0f5fa',
                    100: '#dbe6f1',
                    500: '#3b6ea3',
                    600: '#2c5984',
                    700: '#1e3a5f',
                    800: '#172d4a',
                    900: '#0f1e33',
                },
                accent: {
                    500: '#f59e0b',
                    600: '#d97706',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
            },
        },
    },

    plugins: [forms],
};
