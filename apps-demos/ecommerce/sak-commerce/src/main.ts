import './index.css'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import { useAuthStore } from './stores/auth'
import { useStorefrontSettingsStore } from './stores/storefrontSettings'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Fetch user on app load
const authStore = useAuthStore()
const storefrontSettingsStore = useStorefrontSettingsStore()

Promise.allSettled([
  authStore.fetchUser(),
  storefrontSettingsStore.fetchSettings(),
]).then(() => {
  app.mount('#app')
})
