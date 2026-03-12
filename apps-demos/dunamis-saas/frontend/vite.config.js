import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vue: ['vue', 'vue-router'],
          vendor: ['axios'],
        },
      },
    },
  },
  server: {
    port: 5175,
    host: '127.0.0.1'
  }
})
