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
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb', // Primary
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
                // SVCI blue; public/images/svci-seal.jpg is recolored to the 700 shade.
                school: {
                    50: '#eef3fc',
                    100: '#d9e4f8',
                    200: '#b3c8f0',
                    700: '#1a45a8',
                    800: '#15378a',
                    900: '#0d255e',
                },
                accent: {
                    500: '#f97316', // CTA
                    600: '#ea580c',
                },
            },
            fontFamily: {
                sans: ['"Nunito Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Rubik', '"Nunito Sans"', 'sans-serif'],
                serif: ['"Source Serif 4"', ...defaultTheme.fontFamily.serif],
            },
        },
    },

    plugins: [forms],
};
