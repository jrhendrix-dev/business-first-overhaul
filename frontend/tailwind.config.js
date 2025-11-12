/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{html,ts}", "./index.html"],
  theme: {
    extend: {
      colors: { brand: { navy: "#191970", crimson: "#DC143C" } }
    }
  },
  plugins: []
};
