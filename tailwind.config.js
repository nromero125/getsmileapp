import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['BlinkMacSystemFont', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                display: ['BlinkMacSystemFont', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
            },
            colors: {
                navy: {
                    50:  '#E8ECF5',
                    100: '#C5CEEA',
                    200: '#9EAFDC',
                    300: '#7790CE',
                    400: '#5A78C3',
                    500: '#3D60B8',
                    600: '#2D4D9A',
                    700: '#1F3880',
                    800: '#152969',
                    900: '#0F1F3D',
                    950: '#080F1E',
                },
                teal: {
                    50:  '#E0FAF7',
                    100: '#B3F2EB',
                    200: '#7FE8DC',
                    300: '#4DDECB',
                    400: '#26D4BC',
                    500: '#00BFA6',
                    600: '#009E8A',
                    700: '#007E6E',
                    800: '#005E52',
                    900: '#003F37',
                },
                cream: {
                    50: '#FAFAF7',
                    100: '#F5F5EF',
                    200: '#EEEEE4',
                },
            },
            boxShadow: {
                'card':    '0 1px 3px 0 rgba(15,31,61,0.06), 0 1px 2px -1px rgba(15,31,61,0.04)',
                'card-md': '0 4px 6px -1px rgba(15,31,61,0.08), 0 2px 4px -2px rgba(15,31,61,0.05)',
                'card-lg': '0 10px 15px -3px rgba(15,31,61,0.10), 0 4px 6px -4px rgba(15,31,61,0.06)',
                'card-hover': '0 20px 25px -5px rgba(15,31,61,0.12), 0 8px 10px -6px rgba(15,31,61,0.06)',
            },
            animation: {
                'fade-in':       'fadeIn 0.3s ease-out',
                'slide-up':      'slideUp 0.4s ease-out',
                'slide-in-left': 'slideInLeft 0.3s ease-out',
                'pulse-slow':    'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'skeleton':      'skeleton 1.5s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%':   { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInLeft: {
                    '0%':   { opacity: '0', transform: 'translateX(-16px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                skeleton: {
                    '0%, 100%': { opacity: '1' },
                    '50%':      { opacity: '0.4' },
                },
            },
        },
    },

    plugins: [forms],
};
