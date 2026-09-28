import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Admin-section build — sibling to the patient/doctor/hospital/pharmacy/delivery
// configs, separate for the same reason: Vite's multi-input builds share one
// outDir and the fixed main.js filename would collide.
export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build-admin',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/admin/main.jsx',
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'main.[ext]',
      },
    },
  },
});
