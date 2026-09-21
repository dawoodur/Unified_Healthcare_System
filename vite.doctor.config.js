import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Doctor-section build — sibling to vite.config.js (patient), kept as a
// separate config rather than a multi-entry build because Vite's
// multi-input builds share one outDir, and this app's fixed-filename
// convention (main.js for both apps) would collide if merged.
export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build-doctor',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/doctor/main.jsx',
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'main.[ext]',
      },
    },
  },
});
