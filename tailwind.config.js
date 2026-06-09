import forms from '@tailwindcss/forms';

/*
 * RTL-ready (D-014 / T-8.2): use Tailwind LOGICAL utilities throughout
 * (ms-/me-, ps-/pe-, start/end, text-start/text-end). Do NOT use physical
 * left/right utilities in base layout. A `dir` attribute on <html> flips
 * layout for Arabic (Phase 3). No extra RTL plugin required.
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [forms],
};
