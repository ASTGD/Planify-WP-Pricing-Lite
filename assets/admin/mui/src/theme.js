// Material UI custom theme for Planify — Google-style
import { createTheme } from '@mui/material/styles';

const primaryBlue = '#1a73e8'; // Google primary blue

const theme = createTheme({
  palette: {
    mode: 'light',
    primary: { main: primaryBlue },
    secondary: { main: '#5f6368' },
    background: {
      default: '#fafafa',
      paper: '#ffffff',
    },
  },
  shape: {
    borderRadius: 8, // Slight rounded corners
  },
  typography: {
    // MD3-ish scale
    fontFamily: 'Roboto, system-ui, -apple-system, Segoe UI, Helvetica, Arial, sans-serif',
    h1: { fontSize: '3.0rem', fontWeight: 600, letterSpacing: -0.5 },
    h2: { fontSize: '2.4rem', fontWeight: 600, letterSpacing: -0.25 },
    h3: { fontSize: '2.0rem', fontWeight: 600 },
    h4: { fontSize: '1.7rem', fontWeight: 600 },
    h5: { fontSize: '1.4rem', fontWeight: 600 },
    h6: { fontSize: '1.2rem', fontWeight: 600 },
    subtitle1: { fontSize: '1.0rem', fontWeight: 500, letterSpacing: 0.15 },
    subtitle2: { fontSize: '0.9rem', fontWeight: 500, letterSpacing: 0.1 },
    body1: { fontSize: '0.95rem', letterSpacing: 0.15 },
    body2: { fontSize: '0.88rem', letterSpacing: 0.1 },
    button: { textTransform: 'none', fontWeight: 600 },
    caption: { fontSize: '0.78rem' },
    overline: { fontSize: '0.75rem', letterSpacing: 0.6, textTransform: 'uppercase' },
  },
  shadows: [
    'none',
    '0px 1px 2px rgba(0,0,0,0.06), 0px 1px 1px rgba(0,0,0,0.04)',
    '0px 1px 3px rgba(0,0,0,0.08), 0px 1px 2px rgba(0,0,0,0.05)',
    '0px 2px 4px rgba(0,0,0,0.10), 0px 1px 3px rgba(0,0,0,0.06)',
    '0px 2px 6px rgba(0,0,0,0.12), 0px 1px 4px rgba(0,0,0,0.07)',
    ...Array(20).fill('0px 2px 8px rgba(0,0,0,0.14)'),
  ],
  spacing: 8, // Google-like spacing 8/16/24
  components: {
    MuiButton: {
      defaultProps: { disableRipple: true },
      styleOverrides: {
        root: { borderRadius: 8 },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: { borderRadius: 8 },
      },
    },
  },
});

export default theme;

