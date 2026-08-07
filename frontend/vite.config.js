import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

// Configuración exclusiva para tu Frontend SPA
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
});