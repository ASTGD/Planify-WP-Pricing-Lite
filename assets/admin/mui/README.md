# Planify — UI V2 (React + Material UI)

This directory contains a Google‑style admin shell for building settings panels using React and Material UI.

- Build: `npm run build:mui`
- Dev watch: `npm run watch:mui`
- Entry: `assets/admin/mui/src/index.jsx`
- Bundle output: `assets/admin/mui/build/app.js`

The shell includes:
- ThemeProvider + CssBaseline wrapping the app
- Custom theme (Google primary blue, 8px radius, MD3‑like typography, light shadows)
- AppBar + persistent Drawer layout (topbar + sidebar + main content)
- Reusable components: Buttons, Typography demo, SectionCard
- Placeholder pages: Typography, Colors, Animation

To add new panels, create a component under `src/pages/` and register it in `src/index.jsx`.
