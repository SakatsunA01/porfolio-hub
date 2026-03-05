/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        bg: {
          primary: 'rgb(var(--bg-primary-rgb) / <alpha-value>)',
          secondary: 'rgb(var(--bg-secondary-rgb) / <alpha-value>)',
        },
        text: {
          primary: 'rgb(var(--text-primary-rgb) / <alpha-value>)',
          secondary: 'rgb(var(--text-secondary-rgb) / <alpha-value>)',
        },
        accent: {
          olive: 'rgb(var(--accent-olive-rgb) / <alpha-value>)',
          clay: 'rgb(var(--accent-clay-rgb) / <alpha-value>)',
        },
        state: {
          error: '#9C5C4C',
        },
        'axis-primary': 'rgb(var(--bg-primary-rgb) / <alpha-value>)',
        'axis-secondary': 'rgb(var(--text-primary-rgb) / <alpha-value>)',
        'axis-tertiary': 'rgb(var(--text-secondary-rgb) / <alpha-value>)',
        'axis-accent': 'rgb(var(--accent-olive-rgb) / <alpha-value>)',
        'axis-clay': 'rgb(var(--accent-clay-rgb) / <alpha-value>)',
        'axis-neutral': 'rgb(var(--bg-secondary-rgb) / <alpha-value>)',
      },
      fontFamily: {
        serif: ['Playfair Display', 'serif'],
        sans: ['Inter', 'sans-serif'],
        display: ['Playfair Display', 'serif'],
      },
      spacing: {
        section: '96px',
        'section-sm': '64px',
      },
      maxWidth: {
        container: '1200px',
      },
    },
  },
  plugins: [
    require('@tailwindcss/aspect-ratio'),
  ],
}
