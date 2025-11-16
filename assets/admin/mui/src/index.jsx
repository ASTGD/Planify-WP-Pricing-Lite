import React from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider, CssBaseline } from '@mui/material';
import Box from '@mui/material/Box';
import AppShell, { DefaultSections } from './components/AppShell.jsx';
import theme from './theme.js';
import SettingsIndex from './pages/SettingsIndex.jsx';
import Typography from '@mui/material/Typography';

function Placeholder({ label }) {
  return (
    <Box>
      <Typography variant="h5" gutterBottom>{label}</Typography>
      <Typography variant="body1" color="text.secondary">Add your controls for {label} here.</Typography>
    </Box>
  );
}

function App(){
  const [active, setActive] = React.useState('typography');
  const sections = DefaultSections;

  const registry = {
    typography: <SettingsIndex />,
    colors: <Placeholder label="Colors" />,
    animation: <Placeholder label="Animation" />,
  };

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <AppShell sections={sections} activeKey={active} onSelect={setActive}>
        {registry[active] || <Placeholder label={active} />}
      </AppShell>
    </ThemeProvider>
  );
}

function mount(){
  const rootEl = document.getElementById('pwpl-mui-root');
  if (!rootEl) return;
  const root = createRoot(rootEl);
  root.render(<App />);
}

document.addEventListener('DOMContentLoaded', mount);

