import React from 'react';
import SectionCard from '../components/SectionCard.jsx';
import ButtonsDemo from '../components/Buttons.jsx';
import TypographyText from '../components/TypographyText.jsx';
import Button from '@mui/material/Button';

export default function SettingsIndex(){
  return (
    <>
      <SectionCard title="Getting Started" subtitle="This is a Google‑style shell you can extend.">
        <TypographyText />
        <ButtonsDemo />
      </SectionCard>
      <SectionCard title="Cards & Surfaces" subtitle="Use Paper components with consistent spacing." actions={<Button variant="outlined">Learn More</Button>}>
        <div>Drop in your settings controls here.</div>
      </SectionCard>
    </>
  );
}

