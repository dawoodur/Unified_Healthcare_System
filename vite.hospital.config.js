import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Hospital-section build — sibling to vite.config.js (patient) and
// vite.doctor.config.js, separate for the same reason: Vite's multi-input
// builds share one outDir, and the fixed main.js filename would collide.
export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build-hospital',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/hospital/main.jsx',
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'main.[ext]',
      },
    },
  },
});
