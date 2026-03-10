/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}'
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Playfair Display', 'serif'],
        sans: ['Poppins', 'system-ui', 'sans-serif']
      },
      colors: {
        rose: {
          DEFAULT: '#E11D48',
          50: '#FFF1F2',
          100: '#FFE4E6',
          200: '#FECDD3',
          300: '#FDA4AF',
          400: '#FB7185',
          500: '#E11D48',
          600: '#BE123C',
          700: '#9F1239',
          800: '#881337',
          900: '#4C0519'
        },
        gold: {
          DEFAULT: '#C9A227',
          50: '#FDF8E8',
          100: '#FAF0CC',
          200: '#F5E099',
          300: '#EED066',
          400: '#E5C040',
          500: '#C9A227',
          600: '#A8841F',
          700: '#876618',
          800: '#664D12',
          900: '#45340C'
        },
        wmis: {
          primary: '#E11D48',
          secondary: '#FB7185',
          accent: '#C9A227',
          'bg-light': '#FFF8F9',
          'bg-gradient': 'linear-gradient(135deg, #FFF8F9 0%, #FFF1F2 50%, #FFE4E6 100%)',
          card: '#FFFFFF',
          text: '#1F2937'
        }
      },
      backgroundImage: {
        'gradient-rose': 'linear-gradient(135deg, #FFF1F2 0%, #FFE4E6 50%, #FECDD3 100%)',
        'gradient-rose-subtle': 'linear-gradient(180deg, #FFF8F9 0%, #FFF1F2 100%)',
        'gradient-gold-subtle': 'linear-gradient(135deg, #FDF8E8 0%, #FAF0CC 100%)',
        'gradient-card': 'linear-gradient(145deg, #ffffff 0%, #fffbfc 100%)',
        'gradient-hero': 'linear-gradient(135deg, rgba(255,241,242,0.6) 0%, rgba(254,205,211,0.3) 100%)'
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(225, 29, 72, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'soft-lg': '0 10px 40px -10px rgba(225, 29, 72, 0.12), 0 4px 20px -2px rgba(0, 0, 0, 0.06)',
        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)',
        'card-hover': '0 8px 30px -6px rgba(225, 29, 72, 0.12), 0 4px 15px -4px rgba(0, 0, 0, 0.06)',
        'luxury': '0 4px 20px -2px rgba(201, 162, 39, 0.15), 0 2px 10px -2px rgba(225, 29, 72, 0.06)',
        'inner-gold': 'inset 0 1px 0 0 rgba(255,255,255,0.4)'
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.25rem',
        '3xl': '1.5rem'
      },
      transitionDuration: {
        '250': '250ms',
        '350': '350ms'
      },
      transitionTimingFunction: {
        'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
        'luxury': 'cubic-bezier(0.33, 1, 0.68, 1)'
      }
    }
  },
  plugins: []
}
