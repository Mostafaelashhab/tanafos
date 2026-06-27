import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Cairo', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Tanafos brand — soft violet (Figma "task app" look).
                brand: {
                    50: '#f5f4fe', 100: '#ece8fd', 200: '#dcd5fb', 300: '#c3b6f8',
                    400: '#a48ef3', 500: '#8a6fee', 600: '#6c5ce7', 700: '#5a47cf',
                    800: '#4a3aa8', 900: '#3e3286',
                },
                accent: {
                    50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74',
                    400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c',
                },
            },
        },
    },

    plugins: [forms],
};
