<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { RouterView } from 'vue-router'
import { useDeliveryStore } from './stores/delivery'

const store = useDeliveryStore()
const router = useRouter()
const route = useRoute()

let timerId = 0

const handleBack = () => {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push(store.allowedRouteByRole)
}

const handleLogout = async () => {
  await store.logout()
  router.push('/login')
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
})

onBeforeUnmount(() => {
  window.clearInterval(timerId)
})
</script>

<template>
  <div class="min-h-screen px-4 py-6 font-['Inter',sans-serif] md:px-6 md:py-8">
    <div class="forest-shell relative mx-auto w-full max-w-6xl p-6 md:p-10">
      <span class="forest-glow -right-10 -top-10"></span>
      <div class="flex min-h-[78vh] flex-col gap-4 overflow-hidden">
        <header v-if="store.isAuthenticated && route.path !== '/login'" class="flex items-center justify-between border-b border-slate-100 pb-3">
          <button
            type="button"
            class="rounded-lg border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:border-emerald-300 hover:text-emerald-600 active:scale-95"
            @click="handleBack"
          >
            Volver
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200/70 bg-white/85 px-3 py-2 text-sm font-semibold text-slate-600 transition duration-200 hover:text-rose-600 active:scale-95"
            @click="handleLogout"
          >
            Cerrar sesion
          </button>
        </header>

        <main class="min-h-0 flex-1 overflow-y-auto pr-1 custom-scroll">
          <RouterView v-slot="{ Component }">
            <component :is="Component" />
          </RouterView>
        </main>

        <footer class="flex items-center justify-end border-t border-slate-100 pt-2">
          <span
            class="inline-flex items-center gap-2 rounded-full border border-slate-200/60 px-2 py-1 text-[11px] font-semibold"
            :class="store.realtimeConnected ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
          >
            <span class="h-2 w-2 rounded-full" :class="store.realtimeConnected ? 'bg-emerald-500 pulse' : 'bg-slate-400'"></span>
            Conexion en tiempo real
          </span>
        </footer>
      </div>
    </div>

    <Transition name="toast">
      <div v-if="store.flashMessage" class="fixed bottom-5 right-5 z-50 rounded-xl border border-emerald-200/60 bg-white/90 px-3 py-2 text-sm font-semibold text-emerald-700 shadow-sm backdrop-blur-md">
        {{ store.flashMessage }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
  width: 6px;
}

.custom-scroll::-webkit-scrollbar-thumb {
  background: rgb(148 163 184 / 0.5);
  border-radius: 999px;
}

.pulse {
  animation: pulse 1.6s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.55;
    transform: scale(1.2);
  }
}

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
