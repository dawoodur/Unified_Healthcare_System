import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Pharmacy-section build — sibling to the patient/doctor/hospital configs,
// separate for the same reason: Vite's multi-input builds share one outDir
// and the fixed main.js filename would collide.
export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build-pharmacy',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/pharmacy/main.jsx',
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'main.[ext]',
      },
    },
  },
});
