import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Delivery-section build — sibling to the patient/doctor/hospital/pharmacy
// configs, separate for the same reason: Vite's multi-input builds share one
// outDir and the fixed main.js filename would collide.
export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build-delivery',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/delivery/main.jsx',
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'main.[ext]',
      },
    },
  },
});
