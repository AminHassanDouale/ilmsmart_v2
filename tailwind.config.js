import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/robsontenorio/mary/src/View/Components/**/*.php',
    ],

    safelist: [
        { pattern: /badge-(primary|secondary|success|error|warning|info|neutral)/ },
        { pattern: /text-(primary|secondary|success|error|warning|info)/ },
        { pattern: /bg-(primary|secondary|success|error|warning|info)/ },
        { pattern: /bg-(primary|secondary|success|error|warning|info)\/\d+/ },
        { pattern: /border-(primary|secondary|success|error|warning|info)/ },
        { pattern: /ring-(primary|secondary|success|error|warning|info)/ },
        'grade-a', 'grade-b', 'grade-c', 'grade-d',
        'text-error',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Cairo', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        require('daisyui'),
    ],

    daisyui: {
        themes: [
            {
                light: {
                    primary:           '#6366f1',
                    'primary-focus':   '#4f46e5',
                    'primary-content': '#ffffff',
                    secondary:         '#a855f7',
                    'secondary-focus': '#9333ea',
                    'secondary-content': '#ffffff',
                    accent:            '#ec4899',
                    'accent-focus':    '#db2777',
                    'accent-content':  '#ffffff',
                    neutral:           '#1e293b',
                    'neutral-focus':   '#0f172a',
                    'neutral-content': '#ffffff',
                    'base-100':        '#ffffff',
                    'base-200':        '#f1f5f9',
                    'base-300':        '#e2e8f0',
                    'base-content':    '#1e293b',
                    info:              '#0ea5e9',
                    success:           '#22c55e',
                    warning:           '#f59e0b',
                    error:             '#ef4444',
                },
            },
            {
                dark: {
                    primary:           '#818cf8',
                    'primary-focus':   '#6366f1',
                    'primary-content': '#ffffff',
                    secondary:         '#c084fc',
                    'secondary-focus': '#a855f7',
                    'secondary-content': '#ffffff',
                    accent:            '#f472b6',
                    'accent-focus':    '#ec4899',
                    'accent-content':  '#ffffff',
                    neutral:           '#334155',
                    'neutral-focus':   '#1e293b',
                    'neutral-content': '#f1f5f9',
                    'base-100':        '#0f172a',
                    'base-200':        '#1e293b',
                    'base-300':        '#334155',
                    'base-content':    '#e2e8f0',
                    info:              '#38bdf8',
                    success:           '#4ade80',
                    warning:           '#fbbf24',
                    error:             '#f87171',
                },
            },
        ],
        darkTheme: 'dark',
        base:    true,
        styled:  true,
        utils:   true,
        logs:    false,
    },
};
