/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./*.{html,js}"],
  theme: {
    extend: {
      keyframes: {
        background: {
          '0%': { transform: 'scale(1.2)', opacity: '.1' },
          '100%': { transform: 'scale(1.0)', opacity: '1' },
        },
        title: {
          '0%': { transform: 'scale(1)', opacity: '.1' },
          '100%': { transform: 'scale(1.1)', opacity: '1' },
        },
      },
      animation: {
        background: 'background 1.5s forwards ease-in-out',
        title: 'title 1.5s forwards ease-in-out',
      },
    },
  },
  plugins: [],
}

