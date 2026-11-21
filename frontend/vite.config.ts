import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * vite.config.ts
 *
 * Configuration Vite pour le frontend React.
 * Le proxy /api redirige les appels vers le backend PHP
 * en développement local pour éviter les problèmes CORS.
 */
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
});
