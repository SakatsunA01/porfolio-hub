import { reactive, readonly } from 'vue'

const DENSITY_KEY = 'dunamis-ui-density'
const THEME_KEY = 'dunamis-ui-theme'

const loadDensity = () => {
  const value = localStorage.getItem(DENSITY_KEY)
  return value === 'compact' ? 'compact' : 'comfortable'
}

const loadTheme = () => {
  const value = localStorage.getItem(THEME_KEY)
  return value === 'dark' ? 'dark' : 'light'
}

const state = reactive({
  density: loadDensity(),
  theme: loadTheme(),
  commandOpen: false,
  toasts: [],
})

let toastId = 0

const setDensity = (value) => {
  state.density = value === 'compact' ? 'compact' : 'comfortable'
  localStorage.setItem(DENSITY_KEY, state.density)
}

const toggleDensity = () => {
  setDensity(state.density === 'comfortable' ? 'compact' : 'comfortable')
}

const setTheme = (value) => {
  state.theme = value === 'dark' ? 'dark' : 'light'
  localStorage.setItem(THEME_KEY, state.theme)
}

const toggleTheme = () => {
  setTheme(state.theme === 'light' ? 'dark' : 'light')
}

const openCommand = () => {
  state.commandOpen = true
}

const closeCommand = () => {
  state.commandOpen = false
}

const toast = (message, type = 'info') => {
  const id = ++toastId
  state.toasts.push({ id, message, type })
  window.setTimeout(() => {
    const index = state.toasts.findIndex((item) => item.id === id)
    if (index >= 0) state.toasts.splice(index, 1)
  }, 2600)
}

const removeToast = (id) => {
  const index = state.toasts.findIndex((item) => item.id === id)
  if (index >= 0) state.toasts.splice(index, 1)
}

export const useUiStore = () => ({
  state: readonly(state),
  setDensity,
  toggleDensity,
  setTheme,
  toggleTheme,
  openCommand,
  closeCommand,
  toast,
  removeToast,
})
