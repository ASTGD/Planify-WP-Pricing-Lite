import React from 'react';
import Box from '@mui/material/Box';
import AppBar from '@mui/material/AppBar';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';
import IconButton from '@mui/material/IconButton';
import MenuIcon from '@mui/icons-material/Menu';
import Drawer from '@mui/material/Drawer';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';
import Divider from '@mui/material/Divider';
import Paper from '@mui/material/Paper';
import Button from '@mui/material/Button';
import SettingsIcon from '@mui/icons-material/Settings';
import ColorLensIcon from '@mui/icons-material/ColorLens';
import TextFieldsIcon from '@mui/icons-material/TextFields';
import AnimationIcon from '@mui/icons-material/Animation';

const drawerWidth = 264;

export default function AppShell({ sections, activeKey, onSelect, children }) {
  const [open, setOpen] = React.useState(true);
  const handleToggle = () => setOpen((v) => !v);

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh' }}>
      <AppBar position="fixed" color="inherit" elevation={1} sx={{ zIndex: (t) => t.zIndex.drawer + 1 }}>
        <Toolbar>
          <IconButton edge="start" onClick={handleToggle} sx={{ mr: 2 }}>
            <MenuIcon />
          </IconButton>
          <Typography variant="h6" sx={{ flexGrow: 1 }}>Planify UI</Typography>
          <Button variant="contained" size="small">Save</Button>
        </Toolbar>
      </AppBar>

      <Drawer variant="persistent" open={open} sx={{
        width: drawerWidth,
        flexShrink: 0,
        '& .MuiDrawer-paper': { width: drawerWidth, boxSizing: 'border-box', p: 1 }
      }}>
        <Toolbar />
        <Box sx={{ px: 1, py: 1 }}>
          <Paper variant="outlined" sx={{ p: 1 }}>
            <Typography variant="subtitle2" sx={{ px: 1, py: 1, color: 'text.secondary' }}>Navigation</Typography>
            <Divider />
            <List>
              {sections.map((s) => (
                <ListItem key={s.key} disablePadding>
                  <ListItemButton selected={activeKey === s.key} onClick={() => onSelect(s.key)}>
                    <ListItemIcon>{s.icon || <SettingsIcon />}</ListItemIcon>
                    <ListItemText primary={s.label} />
                  </ListItemButton>
                </ListItem>
              ))}
            </List>
          </Paper>
        </Box>
      </Drawer>

      <Box component="main" sx={{ flexGrow: 1, p: 3 }}>
        <Toolbar />
        <Box sx={{ display: 'grid', gap: 3 }}>{children}</Box>
      </Box>
    </Box>
  );
}

export const DefaultSections = [
  { key: 'typography', label: 'Typography', icon: <TextFieldsIcon /> },
  { key: 'colors', label: 'Colors', icon: <ColorLensIcon /> },
  { key: 'animation', label: 'Animation', icon: <AnimationIcon /> },
];

