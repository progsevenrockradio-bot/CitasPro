<template>
  <router-view></router-view>
  <CookieBanner />
</template>

<script setup>
import { onMounted } from 'vue';
import axios from 'axios';
import CookieBanner from './Pages/Components/CookieBanner.vue';

const applyThemeColors = (configs) => {
  if (!configs) return;
  const root = document.documentElement;
  
  if (configs.color_bg) {
    root.style.setProperty('--color-bg', configs.color_bg);
    root.style.setProperty('--color-dark', configs.color_bg);
  }
  if (configs.color_bg_card) {
    root.style.setProperty('--color-bg-card', configs.color_bg_card);
    root.style.setProperty('--color-dark-card', configs.color_bg_card);
  }
  if (configs.color_primary) {
    root.style.setProperty('--color-primary', configs.color_primary);
    root.style.setProperty('--color-primary-hover', configs.color_primary);
  }
  if (configs.color_accent) {
    root.style.setProperty('--color-accent', configs.color_accent);
    root.style.setProperty('--color-accent-hover', configs.color_accent);
  }
  if (configs.color_border) {
    root.style.setProperty('--color-border', configs.color_border);
    root.style.setProperty('--color-border-sutil', configs.color_border);
  }
};

onMounted(async () => {
  try {
    const res = await axios.get('/api/web-config');
    if (res.data.success && res.data.data) {
      applyThemeColors(res.data.data);
    }
  } catch (error) {
    console.error("Error al cargar colores de marca:", error);
  }
});
</script>
