/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './app/**/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                dura: {
                    50: '#eff8ff',
                    100: '#dbeeff',
                    200: '#bfddff',
                    300: '#93c7ff',
                    400: '#5aa9ff',
                    500: '#278af5',
                    600: '#0b78e3',
                    700: '#0968c7',
                    800: '#0756a8',
                    900: '#0b4785',
                    950: '#082c52',
                },
            },

            backgroundImage: {
                'break-pattern': "url('/img/home/bg3.jpg')",
            },

            borderRadius: {
                dura: '14px',
                'dura-lg': '20px',
            },

            boxShadow: {
                'dura-sm':
                    '0 1px 2px rgba(15, 23, 42, 0.06)',

                dura:
                    '0 8px 24px rgba(15, 23, 42, 0.08)',

                'dura-hover':
                    '0 14px 32px rgba(15, 23, 42, 0.12)',
            },

            transitionTimingFunction: {
                dura: 'cubic-bezier(.2, .8, .2, 1)',
            },

            fontFamily: {
                sans: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    '"Segoe UI"',
                    'sans-serif',
                ],
            },
        },
    },

    plugins: [],
};