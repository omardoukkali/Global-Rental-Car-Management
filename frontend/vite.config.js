import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3333,
    watch: {
      usePolling: true, // nécessaire pour le hot reload dans Docker sur Windows
    },
  },
  test: {
    globals: true,           // describe/it/expect sans import
    environment: 'jsdom',    // simule un navigateur pour Vue
  },
})