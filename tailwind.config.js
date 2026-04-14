/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontSize: {
        '2xs': ['0.6875rem', { lineHeight: '1rem', letterSpacing: '0.06em' }]
      },
      lineHeight: {
        snug: '1.375',
        relaxed: '1.625'
      },
      fontFamily: {
        display: ['DM Sans', 'system-ui', 'sans-serif'],
        sans: ['Inter', 'system-ui', 'sans-serif']
      },
      colors: {
        /* Stripe/Linear-inspired semantic tokens */
        sc: {
          canvas: '#FAFBFC',
          elevated: '#FFFFFF',
          border: 'rgba(15, 23, 42, 0.08)',
          'border-strong': 'rgba(15, 23, 42, 0.12)',
          text: '#0F172A',
          muted: '#64748B',
          subtle: '#94A3B8',
          /* Accent #2CAAE2 — links, focus, highlights */
          accent: '#2CAAE2',
          'accent-hover': '#2498c9',
          'accent-muted': 'rgba(44, 170, 226, 0.12)',
          'accent-ring': 'rgba(44, 170, 226, 0.35)',
          line: '#E8EAEF',
          /* Primary #1C2D5B — nav, primary actions */
          navy: '#1C2D5B',
          'navy-muted': 'rgba(28, 45, 91, 0.08)'
        },
        /* SCH PRO 360: navy primary + sky accent */
        brand: {
          50: '#e8f7fc',
          100: '#d1eef8',
          200: '#a3d9f0',
          300: '#6ec3e8',
          400: '#3dade0',
          500: '#2CAAE2',
          600: '#1C2D5B',
          700: '#152447',
          800: '#101a38',
          900: '#0a1024'
        }
      },
      boxShadow: {
        soft: '0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06)',
        'soft-lg':
          '0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 12px 28px -8px rgba(28, 45, 91, 0.1), 0 0 0 1px rgba(15, 23, 42, 0.04)',
        card: '0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px -2px rgba(15, 23, 42, 0.08)',
        'card-hover':
          '0 8px 24px -6px rgba(28, 45, 91, 0.12), 0 4px 12px -4px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.04)',
        'inset-highlight': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.6)',
        focus: '0 0 0 3px rgba(44, 170, 226, 0.28)',
        'focus-sm': '0 0 0 2px rgba(44, 170, 226, 0.22)'
      },
      borderRadius: {
        xl: '0.75rem',
        '2xl': '1rem',
        '3xl': '1.25rem'
      },
      transitionDuration: {
        250: '250ms',
        350: '350ms',
        400: '400ms'
      },
      transitionTimingFunction: {
        smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
        'out-expo': 'cubic-bezier(0.16, 1, 0.3, 1)',
        spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(6px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        }
      },
      animation: {
        'fade-in': 'fade-in 0.35s ease-out forwards',
        'fade-up': 'fade-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards'
      }
    }
  },
  plugins: []
}
