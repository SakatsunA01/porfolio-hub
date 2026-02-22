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

      // Radios consistentes por rol
      borderRadius: {
        // controles: inputs, botones, selects
        control: '0.75rem', // 12px
        // cards grandes / paneles
        card: '1rem', // 16px
        xl: '1.25rem',
        '2xl': '1.5rem',
      },

      // Sistema de profundidad + inset
      boxShadow: {
        // Surface 1: card normal
        soft: '0 10px 30px -18px rgba(6, 20, 27, 0.18), 0 2px 8px -6px rgba(6, 20, 27, 0.10)',
        // Surface 2: panel importante / dropdown
        'soft-lg': '0 18px 50px -22px rgba(6, 20, 27, 0.22), 0 6px 18px -12px rgba(6, 20, 27, 0.14)',
        // Inputs "inset" (hueco)
        'inset-soft': 'inset 0 1px 2px rgba(15, 23, 42, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.9)',
        // Glow de foco (usalo con ring si querés)
        'focus-glow': '0 0 0 4px rgba(196, 181, 253, 0.45)',
      },
    },
  },

  plugins: [forms],
};
