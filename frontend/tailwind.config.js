/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'Menlo', 'Monaco', 'monospace'],
      },
      colors: {
        // Surcharges fidèles à la charte graphique du projet
        brand: {
          DEFAULT: '#4F46E5',
          hover: '#4338CA',
          muted: '#EEF2FF',
        },
      },
    },
  },
  plugins: [],
}
