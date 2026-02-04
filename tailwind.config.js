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
        // BlinkStudy Brand Colors (matching mobile app)
        primary: {
          DEFAULT: '#0D9488',
          light: '#99F6E4',
          dark: '#0F766E',
        },
        secondary: {
          DEFAULT: '#F59E0B',
          light: '#FDE68A',
          dark: '#D97706',
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
        'blinkstudy': '0 4px 14px rgba(13, 148, 136, 0.15)',
        'blinkstudy-lg': '0 8px 30px rgba(13, 148, 136, 0.2)',
      },
    },
  },
  plugins: [],
}
