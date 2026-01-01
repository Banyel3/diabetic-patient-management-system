/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        // CarePoint Color Palette
        primary: "#1E292C", // Dark slate for active states
        secondary: "#7697A3", // Muted teal-gray
        accent: "#2DD4BF", // Teal accent color
        "accent-light": "#E0F7F4", // Light teal for backgrounds
        danger: "#EF4444",
        "danger-light": "#FEE2E2",
        warning: "#F59E0B",
        "warning-light": "#FEF3C7",
        success: "#10B981",
        "success-light": "#D1FAE5",
        info: "#3B82F6",
        "info-light": "#DBEAFE",
        background: "#EBF1F5", // Light gray-blue background
        surface: "#FFFFFF",
        "surface-secondary": "#F8FAFC",
        "card-bg": "#FFFFFF",
        "text-primary": "#1E292C",
        "text-secondary": "#646C6F",
        "text-muted": "#ACBCC3",
        "border-light": "#E2E8F0",
      },
      borderRadius: {
        "2xl": "1rem",
        "3xl": "1.5rem",
        "4xl": "2rem",
      },
      boxShadow: {
        soft: "0 4px 20px -2px rgba(0, 0, 0, 0.06)",
        card: "0 2px 12px -3px rgba(0, 0, 0, 0.08)",
        hover: "0 8px 30px -5px rgba(0, 0, 0, 0.1)",
        "inner-soft": "inset 0 2px 4px 0 rgba(0, 0, 0, 0.04)",
      },
      fontFamily: {
        sans: ["Inter", "system-ui", "sans-serif"],
      },
    },
  },
  plugins: [],
};
