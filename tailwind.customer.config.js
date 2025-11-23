/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './src/Views/customer/**/*.blade.php',
    './customer/js/**/*.js', // quét class trong các file JS
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
