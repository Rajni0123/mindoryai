/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.tsx",
    "./resources/**/*.ts",
  ],
  theme: {
    extend: {
      colors: {
        // BlinkStudy Brand — purple (app) + teal (legacy)
        brand: {
          DEFAULT: '#705CF6',
          light: '#7B61FF',
          dark: '#5B4AE0',
          50: '#F3EEFF',
          100: '#E8E0FF',
          200: '#D4C7FE',
        },
        primary: {
          DEFAULT: '#705CF6',
          light: '#7B61FF',
          dark: '#5B4AE0',
        },
        secondary: {
          DEFAULT: '#5B8CFF',
          light: '#93B4FF',
          dark: '#3B6FE8',
        },
        accent: '#99F6E4',
        background: '#F0FDFA',
        surface: '#FFFFFF',
        success: '#10B981',
        error: '#EF4444',
        warning: '#F59E0B',
        info: '#3B82F6',
        gray: {
          50: '#F9FAFB',
          100: '#F3F4F6',
          200: '#E5E7EB',
          300: '#D1D5DB',
          400: '#9CA3AF',
          500: '#6B7280',
          600: '#4B5563',
          700: '#374151',
          800: '#1F2937',
          900: '#111827',
        },
      },
      fontFamily: {
        sans: ['Outfit', 'Inter', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        'button': '12px',
        'card': '16px',
        'input': '24px',
      },
      boxShadow: {
        'blinkstudy': '0 4px 14px rgba(112, 92, 246, 0.15)',
        'blinkstudy-lg': '0 8px 30px rgba(112, 92, 246, 0.22)',
        'card': '0 8px 32px rgba(15, 23, 42, 0.06)',
        'card-hover': '0 16px 48px rgba(112, 92, 246, 0.12)',
      },
    },
  },
  plugins: [],
  safelist: [
    'from-brand', 'to-brand-light', 'from-secondary', 'to-blue-400',
    'from-emerald-500', 'to-teal-400', 'from-amber-500', 'to-orange-400',
    'from-violet-500', 'to-brand', 'from-pink-500', 'to-rose-400',
  ],
}
