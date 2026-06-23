/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    'node_modules/preline/dist/*.js',
  ],
  darkMode: 'class',
 
  theme: {

    extend: {
      backgroundImage: {
        'break-pattern': "url('img/home/bg3.jpg')",
    }
    },
  },
  plugins: [
    require('preline/plugin'),
    require('tailwindcss/plugin')(({ matchUtilities }) => {
      matchUtilities({
        'group': (value) => ({
          [`@apply ${value.replaceAll(',', ' ')}`]: {}
        })
      })
    })
  ],
}

