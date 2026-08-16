import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    port: 3000,
    host: '0.0.0.0', // Required for Docker container port binding
    proxy: {
      '/api': {
        target: 'http://backend:8000', // Route API requests to backend container
        changeOrigin: true,
        secure: false
      },
      '/storage': {
        target: 'http://backend:8000', // Route file storage requests to backend container
        changeOrigin: true,
        secure: false
      }
    }
  }
})