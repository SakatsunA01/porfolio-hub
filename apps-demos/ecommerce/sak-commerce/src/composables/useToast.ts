import { ref } from 'vue'

const isVisible = ref(false)
const message = ref('')
let timeoutId: number | null = null

export function useToast() {
  const showToast = (value: string, duration = 2400) => {
    message.value = value
    isVisible.value = true

    if (timeoutId) {
      window.clearTimeout(timeoutId)
    }

    timeoutId = window.setTimeout(() => {
      isVisible.value = false
      timeoutId = null
    }, duration)
  }

  const hideToast = () => {
    isVisible.value = false

    if (timeoutId) {
      window.clearTimeout(timeoutId)
      timeoutId = null
    }
  }

  return {
    isVisible,
    message,
    showToast,
    success: showToast,
    error: showToast,
    hideToast,
  }
}
