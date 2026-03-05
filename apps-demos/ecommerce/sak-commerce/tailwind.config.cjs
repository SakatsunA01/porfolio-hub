/** @type {import('tailwindcss').Config} */
const withOpacity = (cssVar) => ({ opacityValue }) => {
  if (opacityValue !== undefined) {
    return `rgb(var(${cssVar}) / ${opacityValue})`
  }
  return `rgb(var(${cssVar}) / 1)`
}

module.exports = {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        bg: {
          primary: withOpacity('--bg-primary-rgb'),
          secondary: withOpacity('--bg-secondary-rgb'),
        },
        text: {
          primary: withOpacity('--text-primary-rgb'),
          secondary: withOpacity('--text-secondary-rgb'),
        },
        accent: {
          olive: withOpacity('--accent-olive-rgb'),
          clay: withOpacity('--accent-clay-rgb'),
        },
        state: {
          error: '#9C5C4C',
        },
        'axis-primary': withOpacity('--bg-primary-rgb'),
        'axis-secondary': withOpacity('--text-primary-rgb'),
        'axis-tertiary': withOpacity('--text-secondary-rgb'),
        'axis-accent': withOpacity('--accent-olive-rgb'),
        'axis-clay': withOpacity('--accent-clay-rgb'),
        'axis-neutral': withOpacity('--bg-secondary-rgb'),
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
