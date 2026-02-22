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
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        'brand-navy': '#1D2B53',
        'brand-purple': '#7C3AED',
        'brand-purple-soft': '#C4B5FD',
        canvas: '#F6F7FB',
        'surface-border': '#E4E7EE',
        'text-main': '#0B1220',
        'text-muted': '#52607A',
        'neutral-card': '#FFFFFF',
      },
      borderRadius: {
        control: '0.75rem',
        card: '1rem',
        xl: '1.25rem',
        '2xl': '1.5rem',
      },
      boxShadow: {
        soft: '0 10px 30px -18px rgba(6, 20, 27, 0.18), 0 2px 8px -6px rgba(6, 20, 27, 0.10)',
        'soft-lg': '0 18px 50px -22px rgba(6, 20, 27, 0.22), 0 6px 18px -12px rgba(6, 20, 27, 0.14)',
        'inset-soft': 'inset 0 1px 2px rgba(15, 23, 42, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.9)',
        'focus-glow': '0 0 0 4px rgba(196, 181, 253, 0.45)',
      },
    },
  },
  plugins: [forms],
};
