/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './src/Views/internal/**/*.blade.php',
    './internal/js/**/*.js', // quét class trong các file JS
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
