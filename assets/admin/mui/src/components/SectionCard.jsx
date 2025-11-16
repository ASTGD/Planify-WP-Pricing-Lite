import React from 'react';
import Paper from '@mui/material/Paper';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';

export default function SectionCard({ title, subtitle, children, actions }){
  return (
    <Paper elevation={0} variant="outlined" sx={{ p: 3 }}>
      {(title || actions) && (
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 2, gap: 2 }}>
          {title && (
            <Box>
              <Typography variant="h6">{title}</Typography>
              {subtitle && <Typography variant="body2" color="text.secondary">{subtitle}</Typography>}
            </Box>
          )}
          <Box sx={{ ml: 'auto' }}>{actions}</Box>
        </Box>
      )}
      <Box sx={{ display: 'grid', gap: 2 }}>{children}</Box>
    </Paper>
  );
}

