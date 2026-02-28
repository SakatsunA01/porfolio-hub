<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { CheckCircle2, ChevronDown, ChevronUp, MapPin, MessageCircle, Moon, Navigation, Phone, Sun, Truck } from 'lucide-vue-next'
import { useDeliveryStore, type Order } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import DriverNavTabs from '../components/driver/DriverNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()
const isOnline = ref(true)
const successPulse = ref(false)
const queueOpen = ref(true)
const slideValue = ref(0)
const themePreference = ref<'auto' | 'light' | 'dark'>('auto')
const currentHour = ref(new Date().getHours())
const currentDriverId = computed(() => store.shiftDriverId || store.currentUser?.id || null)
let hourTicker = 0

const ordersForDriver = computed(() =>
  store.orders.filter((order) => {
    const isUnassignedReady = order.status === 'ready' && order.driverId === null
    const isAssignedActive = order.driverId === currentDriverId.value && ['ready', 'onroute'].includes(order.status)
    return isUnassignedReady || isAssignedActive
  }),
)

const activeMission = computed<Order | null>(
  () => ordersForDriver.value.find((order) => order.status === 'onroute') || ordersForDriver.value.find((order) => order.status === 'ready') || null,
)

const pendingOrders = computed(() => ordersForDriver.value.filter((order) => !activeMission.value || order.id !== activeMission.value.id))
const driverDeliveredToday = computed(() => {
  const now = new Date()
  return store.orders.filter((order) => {
    if (order.driverId !== currentDriverId.value || order.status !== 'delivered') return false
    const created = new Date(order.createdAt)
    return created.getFullYear() === now.getFullYear() && created.getMonth() === now.getMonth() && created.getDate() === now.getDate()
  })
})
const driverTodayCount = computed(() => driverDeliveredToday.value.length)
const driverTodayGain = computed(() => driverDeliveredToday.value.reduce((acc, order) => acc + Number(order.total || 0), 0))

const mapQuery = computed(() => activeMission.value?.address?.trim() || '')
const mapDirectionsUrl = computed(() =>
  mapQuery.value
    ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(mapQuery.value)}&travelmode=driving`
    : '',
)
const wazeDirectionsUrl = computed(() =>
  mapQuery.value
    ? `https://waze.com/ul?q=${encodeURIComponent(mapQuery.value)}&navigate=yes`
    : '',
)
const phoneHref = computed(() => `tel:+5491111111111`)

const estimateDistance = (orderId: number) => `${((orderId % 7) + 1.2).toFixed(1)} km`

const orderSummary = (order: Order) =>
  order.items.map((item) => `${item.qty} ${store.getProduct(item.productId)?.name || item.name || 'Producto'}`).join(', ')

const takeMission = (orderId: number) => {
  if (!currentDriverId.value || !isOnline.value) return
  store.leaveForDelivery(orderId, currentDriverId.value)
}

const confirmDelivery = (orderId: number) => {
  if (!isOnline.value) return
  store.markDelivered(orderId)
  successPulse.value = true
  if ('vibrate' in navigator) navigator.vibrate(45)
  window.setTimeout(() => {
    successPulse.value = false
  }, 1100)
}

const missionActionLabel = computed(() => {
  if (!activeMission.value) return 'Sin mision activa'
  return activeMission.value.status === 'ready' ? 'Retirar pedido' : 'Ya lo entregue'
})

const missionOrderCode = computed(() => {
  if (!activeMission.value) return ''
  return `#${String(activeMission.value.id).padStart(4, '0')}`
})

const autoDarkMode = computed(() => currentHour.value >= 19 || currentHour.value < 7)
const isDarkTheme = computed(() => {
  if (themePreference.value === 'dark') return true
  if (themePreference.value === 'light') return false
  return autoDarkMode.value
})

const toggleTheme = () => {
  if (themePreference.value === 'auto') {
    themePreference.value = isDarkTheme.value ? 'light' : 'dark'
    return
  }
  themePreference.value = themePreference.value === 'dark' ? 'light' : 'dark'
}

const resetThemeAuto = () => {
  themePreference.value = 'auto'
}

const commitMissionAction = () => {
  if (!activeMission.value || !isOnline.value) return
  if (activeMission.value.status === 'ready') {
    takeMission(activeMission.value.id)
  } else {
    confirmDelivery(activeMission.value.id)
  }
}

const onSlideInput = () => {
  if (slideValue.value >= 98) {
    commitMissionAction()
    window.setTimeout(() => {
      slideValue.value = 0
    }, 150)
  }
}

onMounted(() => {
  const stored = localStorage.getItem('delivery-driver-theme-v1')
  if (stored === 'auto' || stored === 'light' || stored === 'dark') {
    themePreference.value = stored
  }
  hourTicker = window.setInterval(() => {
    currentHour.value = new Date().getHours()
  }, 60000)
})

onBeforeUnmount(() => {
  if (hourTicker) window.clearInterval(hourTicker)
})

watch(themePreference, () => {
  localStorage.setItem('delivery-driver-theme-v1', themePreference.value)
})
</script>

<template>
  <section
    class="relative min-h-screen space-y-4 pb-32"
    :class="[
      isDarkTheme ? 'bg-[#0F172A]' : 'bg-[#F8F9FA]',
      { 'offline-shell': !isOnline },
    ]"
  >
    <header
      class="rounded-[24px] p-4"
      :class="isDarkTheme ? 'border border-[#334155] bg-[#1E293B]' : 'bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]'"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-extrabold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">Mision activa</h2>
          <p class="text-xs" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Hoy: {{ driverTodayCount }} entregas | ${{ driverTodayGain.toFixed(2) }}</p>
        </div>
        <div class="flex items-center gap-2.5">
          <button
            type="button"
            class="grid h-10 w-10 place-items-center rounded-full"
            :class="isDarkTheme ? 'border border-[#334155] bg-[#0F172A] text-[#F8FAFC]' : 'bg-slate-100 text-slate-700'"
            @click="toggleTheme"
            :title="isDarkTheme ? 'Cambiar a claro' : 'Cambiar a oscuro'"
          >
            <Sun v-if="isDarkTheme" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
            :class="themePreference === 'auto'
              ? (isDarkTheme ? 'bg-emerald-500/20 text-emerald-300' : 'bg-emerald-100 text-emerald-700')
              : (isDarkTheme ? 'bg-[#0F172A] text-[#94A3B8] border border-[#334155]' : 'bg-slate-100 text-slate-500')"
            @click="resetThemeAuto"
          >
            Auto
          </button>
          <span class="text-xs font-semibold" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">{{ isOnline ? 'En linea' : 'Desconectado' }}</span>
          <button type="button" class="toggle" :class="isOnline ? 'on' : 'off'" @click="isOnline = !isOnline">
            <span></span>
          </button>
        </div>
      </div>
      <DriverNavTabs />
    </header>

    <article
      class="rounded-[24px] p-5"
      :class="isDarkTheme ? 'border border-[#334155] bg-[#1E293B]' : 'bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]'"
    >
      <div v-if="activeMission" class="space-y-4">
        <div class="flex items-center justify-between gap-2">
          <span
            class="inline-flex items-center rounded-xl px-3 py-1 text-sm font-bold"
            :class="isDarkTheme ? 'border border-emerald-400/70 bg-[#0F172A] text-[#F8FAFC]' : 'bg-slate-900 text-white'"
          >
            {{ missionOrderCode }}
          </span>
          <span
            class="rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="[
              isDarkTheme ? 'bg-emerald-500/20 text-emerald-300' : 'bg-emerald-100 text-emerald-700',
              activeMission.status === 'onroute' ? 'status-glow' : '',
            ]"
          >
            {{ activeMission.status === 'ready' ? 'En local' : 'En camino' }}
          </span>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Direccion de entrega</p>
          <p class="mt-1 text-2xl font-extrabold leading-tight" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">{{ activeMission.address }}</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
          <a
            v-if="mapDirectionsUrl"
            :href="mapDirectionsUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-white transition active:scale-[0.98]"
            :class="isDarkTheme ? 'bg-[#2563EB]' : 'bg-emerald-600'"
          >
            <Navigation class="h-4 w-4" />
            Abrir Google Maps
          </a>
          <a
            v-if="wazeDirectionsUrl"
            :href="wazeDirectionsUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-white transition active:scale-[0.98]"
            :class="isDarkTheme ? 'bg-[#1D4ED8]' : 'bg-slate-900'"
          >
            <MapPin class="h-4 w-4" />
            Abrir Waze
          </a>
        </div>
        <div class="flex items-center justify-between rounded-[16px] px-3 py-2" :class="isDarkTheme ? 'bg-[#0F172A]' : 'bg-slate-50'">
          <div>
            <p class="text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">{{ activeMission.customer }}</p>
            <p class="text-xs" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Cliente</p>
          </div>
          <div class="flex items-center gap-2">
            <a :href="phoneHref" class="icon-btn"><Phone class="h-5 w-5" /></a>
            <a :href="`https://wa.me/?text=${encodeURIComponent(`Hola, voy con tu pedido ${missionOrderCode}.`)}`" target="_blank" rel="noopener noreferrer" class="icon-btn">
              <MessageCircle class="h-5 w-5" />
            </a>
          </div>
        </div>
        <div class="rounded-[16px] px-3 py-2" :class="isDarkTheme ? 'bg-[#0F172A]' : 'bg-slate-50'">
          <p class="text-xs font-semibold uppercase tracking-wide" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Resumen</p>
          <p class="mt-1 text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-800'">{{ orderSummary(activeMission) }}</p>
        </div>
      </div>
      <div v-else class="rounded-[16px] p-4 text-sm" :class="isDarkTheme ? 'bg-[#0F172A] text-[#94A3B8]' : 'bg-slate-50 text-slate-500'">
        No hay mision activa por ahora.
      </div>
    </article>

    <article
      class="rounded-[24px] p-4"
      :class="isDarkTheme ? 'border border-[#334155] bg-[#1E293B]' : 'bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]'"
    >
      <button type="button" class="flex w-full items-center justify-between" @click="queueOpen = !queueOpen">
        <span class="text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">Cola de entregas</span>
        <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">
          {{ pendingOrders.length }} proximas
          <ChevronUp v-if="queueOpen" class="h-4 w-4" />
          <ChevronDown v-else class="h-4 w-4" />
        </span>
      </button>
      <div v-if="queueOpen" class="mt-3 space-y-2">
        <article
          v-for="order in pendingOrders"
          :key="`pending-${order.id}`"
          class="rounded-[16px] px-3 py-2"
          :class="isDarkTheme ? 'bg-[#0F172A] border border-[#334155]' : 'bg-slate-100'"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-700'">#{{ String(order.id).padStart(4, '0') }}</p>
            <p class="text-xs font-semibold" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">A {{ estimateDistance(order.id) }}</p>
          </div>
          <p class="mt-1 truncate text-sm" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-600'">{{ order.address }}</p>
        </article>
        <p v-if="!pendingOrders.length" class="text-xs" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">No hay proximas paradas.</p>
      </div>
    </article>

    <div
      v-if="activeMission"
      class="fixed inset-x-0 bottom-0 z-40 border-t p-3 backdrop-blur"
      :class="isDarkTheme ? 'border-[#334155] bg-[#0F172A]/95' : 'border-slate-200 bg-white/95'"
    >
      <div class="mx-auto w-full max-w-3xl space-y-2">
        <p class="text-center text-[11px] font-semibold" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Desliza para confirmar</p>
        <div class="slide-wrap" :class="isDarkTheme ? 'slide-wrap-dark' : ''">
          <Truck class="slide-icon h-4 w-4" />
          <input
            v-model.number="slideValue"
            type="range"
            min="0"
            max="100"
            class="slide-control"
            :disabled="!isOnline"
            @input="onSlideInput"
          />
          <span class="slide-label">{{ missionActionLabel }}</span>
        </div>
      </div>
    </div>

    <Transition name="success">
      <div v-if="successPulse" class="fixed inset-0 z-50 grid place-items-center bg-emerald-500/10 backdrop-blur-[2px]">
        <div class="grid place-items-center rounded-full bg-white p-6 shadow-2xl">
          <CheckCircle2 class="h-16 w-16 text-emerald-500" />
        </div>
      </div>
    </Transition>
  </section>
</template>

<style scoped>
.offline-shell {
  filter: grayscale(0.8);
  opacity: 0.72;
}

.toggle {
  width: 2.7rem;
  height: 1.5rem;
  border-radius: 999px;
  border: 1px solid rgb(203 213 225);
  padding: 2px;
  transition: all 150ms ease;
}

.toggle span {
  display: block;
  height: 1.05rem;
  width: 1.05rem;
  border-radius: 999px;
  background: white;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
  transition: transform 150ms ease;
}

.toggle.on {
  border-color: rgb(16 185 129);
  background: rgb(16 185 129);
}

.toggle.on span {
  transform: translateX(1.15rem);
}

.toggle.off {
  background: rgb(241 245 249);
}

.icon-btn {
  border: 0;
  background: rgb(16 185 129);
  color: white;
  border-radius: 999px;
  width: 2.75rem;
  height: 2.75rem;
  display: grid;
  place-items: center;
  transition: all 160ms ease;
}

.icon-btn:active {
  transform: scale(0.97);
}

.slide-wrap {
  position: relative;
  border-radius: 9999px;
  background: rgb(15 23 42);
  height: 3.25rem;
  display: flex;
  align-items: center;
  padding: 0 0.8rem;
}

.slide-wrap-dark {
  background: linear-gradient(135deg, rgb(4 120 87), rgb(16 185 129));
}

.slide-icon {
  color: rgb(148 163 184);
  z-index: 2;
}

.slide-label {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
  pointer-events: none;
}

.slide-control {
  width: 100%;
  margin-left: 0.55rem;
  appearance: none;
  background: transparent;
  z-index: 3;
}

.slide-control::-webkit-slider-runnable-track {
  height: 2.35rem;
  border-radius: 9999px;
  background: rgb(30 41 59);
}

.slide-control::-webkit-slider-thumb {
  appearance: none;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 9999px;
  background: rgb(16 185 129);
  border: 2px solid white;
  margin-top: 0;
}

.slide-control::-moz-range-track {
  height: 2.35rem;
  border-radius: 9999px;
  background: rgb(30 41 59);
}

.slide-control::-moz-range-thumb {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 9999px;
  background: rgb(16 185 129);
  border: 2px solid white;
}

.slide-wrap-dark .slide-control::-webkit-slider-thumb {
  background: linear-gradient(135deg, rgb(6 95 70), rgb(16 185 129));
}

.slide-wrap-dark .slide-control::-moz-range-thumb {
  background: linear-gradient(135deg, rgb(6 95 70), rgb(16 185 129));
}

.status-glow {
  box-shadow: 0 0 0 1px rgb(59 130 246 / 0.25), 0 0 14px rgb(59 130 246 / 0.32);
}

.success-enter-active,
.success-leave-active {
  transition: opacity 240ms ease;
}

.success-enter-from,
.success-leave-to {
  opacity: 0;
}
</style>
