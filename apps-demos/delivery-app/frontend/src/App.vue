<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { RouterView } from 'vue-router'
import { useDeliveryStore } from './stores/delivery'

const store = useDeliveryStore()
const route = useRoute()

let timerId = 0
let autoLabelObserver: MutationObserver | null = null
let autoLabelFrame = 0
let autoLabelSeed = 0

const AUTO_LABEL_ID_PREFIX = 'auto-field-'
const AUTO_LABEL_CLASS = 'auto-input-label'
const isLandingLayout = computed(() => Boolean(route.meta.landing))

const ignoredInputTypes = new Set(['hidden', 'checkbox', 'radio', 'file', 'button', 'submit', 'reset', 'image'])

const toLabelText = (raw: string) =>
  raw
    .replace(/\(.*?\)/g, ' ')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/^\w/, (char) => char.toUpperCase())

const deriveLabelText = (field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) => {
  const aria = field.getAttribute('aria-label')
  if (aria?.trim()) return toLabelText(aria)

  const placeholder = field.getAttribute('placeholder')
  if (placeholder?.trim()) return toLabelText(placeholder)

  if (field instanceof HTMLSelectElement) {
    const firstOption = field.querySelector('option')
    if (firstOption?.textContent?.trim()) {
      return toLabelText(firstOption.textContent)
    }
  }

  if (field.name?.trim()) return toLabelText(field.name)
  if (field.id?.trim()) return toLabelText(field.id.replace(/^auto-field-\d+/, 'campo'))

  if (field instanceof HTMLInputElement && field.type === 'search') return 'Buscar'
  return 'Campo'
}

const ensureFieldId = (field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) => {
  if (field.id?.trim()) return field.id
  autoLabelSeed += 1
  field.id = `${AUTO_LABEL_ID_PREFIX}${autoLabelSeed}`
  return field.id
}

const hasVisibleLabel = (field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement, id: string) => {
  if (field.closest('label')) return true
  return Boolean(document.querySelector(`label[for="${id}"]`))
}

const autoLabelFields = () => {
  const root = document.getElementById('app')
  if (!root) return

  const fields = root.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea')
  fields.forEach((field) => {
    if (field.closest('[data-skip-auto-label="true"]')) return
    if (field.dataset.autoLabelManaged === 'true') return
    if (field instanceof HTMLInputElement && ignoredInputTypes.has(field.type)) return
    if (field.disabled) return

    const id = ensureFieldId(field)
    if (hasVisibleLabel(field, id)) {
      field.dataset.autoLabelManaged = 'true'
      return
    }

    const label = document.createElement('label')
    label.className = AUTO_LABEL_CLASS
    label.htmlFor = id
    label.textContent = deriveLabelText(field)
    field.insertAdjacentElement('beforebegin', label)
    field.dataset.autoLabelManaged = 'true'
  })
}

const scheduleAutoLabeling = () => {
  if (autoLabelFrame) return
  autoLabelFrame = window.requestAnimationFrame(() => {
    autoLabelFrame = 0
    autoLabelFields()
  })
}

onMounted(() => {
  store.initialize()
  store.initializeAuth()
  if (store.isAuthenticated) {
    store.refreshAll()
  }
  timerId = window.setInterval(() => {
    store.tickEta()
  }, 60000)

  scheduleAutoLabeling()
  autoLabelObserver = new MutationObserver(() => {
    scheduleAutoLabeling()
  })
  autoLabelObserver.observe(document.body, {
    childList: true,
    subtree: true,
  })
})

onBeforeUnmount(() => {
  window.clearInterval(timerId)
  if (autoLabelFrame) {
    window.cancelAnimationFrame(autoLabelFrame)
    autoLabelFrame = 0
  }
  if (autoLabelObserver) {
    autoLabelObserver.disconnect()
    autoLabelObserver = null
  }
})
</script>

<template>
  <RouterView v-if="isLandingLayout" v-slot="{ Component }">
    <component :is="Component" />
  </RouterView>

  <div v-else class="min-h-screen font-['Inter',sans-serif]">
    <RouterView v-slot="{ Component }">
      <component :is="Component" />
    </RouterView>

    <Transition name="toast">
      <div
        v-if="store.flashMessage"
        class="fixed bottom-5 right-5 z-50 rounded-xl border border-emerald-200/60 bg-white/90 px-3 py-2 text-sm font-semibold text-emerald-700 shadow-sm backdrop-blur-md"
      >
        {{ store.flashMessage }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

</style>
