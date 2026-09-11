import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import containerQueries from '@tailwindcss/container-queries';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                "surface": "#f8f9ff",
                "on-surface": "#0b1c30",
                "on-surface-variant": "#45464d",
                "surface-container-lowest": "#ffffff",
                "surface-container-low": "#eff4ff",
                "surface-container": "#e5eeff",
                "surface-container-high": "#dce9ff",
                "outline-variant": "#e2e8f0",
                "primary": "#0f172a",
                "secondary": "#006c49",
                "secondary-container": "#6cf8bb",
                "secondary-fixed": "#6ffbbe",
                "on-secondary-fixed": "#002113",
                "on-tertiary-container": "#6366f1",
                "tertiary-fixed": "#e0e7ff",
                "tertiary-container": "#07006c",
                "error": "#e11d48",
                "error-container": "#ffe4e6",
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Plus Jakarta Sans', 'sans-serif'],
            },
            borderRadius: {
                "2xl": "1.25rem",
                "3xl": "1.75rem",
                "4xl": "2.25rem"
            },
            boxShadow: {
                'soft': '0 4px 25px -4px rgba(15, 23, 42, 0.05), 0 2px 10px -2px rgba(15, 23, 42, 0.03)',
                'card': '0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 4px 12px -2px rgba(0, 0, 0, 0.02)',
                'glow': '0 0 40px -10px rgba(99, 102, 241, 0.25)',
                'float': '0 20px 40px -15px rgba(15, 23, 42, 0.12)'
            }
        },
    },

    plugins: [forms, containerQueries],
};

