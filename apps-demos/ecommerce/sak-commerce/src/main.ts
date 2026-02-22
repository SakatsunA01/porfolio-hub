import './index.css'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Fetch user on app load
const authStore = useAuthStore()
authStore.fetchUser().then(() => {
  app.mount('#app')
})

