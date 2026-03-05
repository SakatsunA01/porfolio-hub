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
const themePreference = ref<'auto' | 'light' | 'dark'>('auto')
const currentHour = ref(new Date().getHours())
const selectedOrderId = ref<number | null>(null)
const selectedBatchIds = ref<number[]>([])
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
const readyQueueOrders = computed(() => pendingOrders.value.filter((order) => order.status === 'ready'))
const selectableOrders = computed(() => {
  const rows: Order[] = []
  if (activeMission.value) rows.push(activeMission.value)
  rows.push(...pendingOrders.value)
  return rows
})
const previewOrder = computed<Order | null>(
  () => selectableOrders.value.find((order) => order.id === selectedOrderId.value) || activeMission.value || pendingOrders.value[0] || null,
)
const selectedActionOrder = computed<Order | null>(
  () => ordersForDriver.value.find((order) => order.id === selectedOrderId.value) || activeMission.value || null,
)
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

const mapQuery = computed(() => previewOrder.value?.address?.trim() || '')
const mapEmbedUrl = computed(() =>
  mapQuery.value
    ? `https://maps.google.com/maps?q=${encodeURIComponent(mapQuery.value)}&t=&z=15&ie=UTF8&iwloc=&output=embed`
    : '',
)
const mapDirectionsUrl = computed(() =>
  mapQuery.value
    ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(mapQuery.value)}&travelmode=driving`
    : '',
)
const phoneHref = computed(() => `tel:+5491111111111`)
const selectedBatchOrders = computed(() => readyQueueOrders.value.filter((order) => selectedBatchIds.value.includes(order.id)))
const isPreviewingDifferentOrder = computed(() => Boolean(activeMission.value && previewOrder.value && activeMission.value.id !== previewOrder.value.id))

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

const previewOrderCode = computed(() => {
  if (!previewOrder.value) return ''
  return `#${String(previewOrder.value.id).padStart(4, '0')}`
})
const selectedActionOrderCode = computed(() => {
  if (!selectedActionOrder.value) return ''
  return `#${String(selectedActionOrder.value.id).padStart(4, '0')}`
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

const selectOrder = (orderId: number) => {
  selectedOrderId.value = orderId
}

const toggleBatchOrder = (orderId: number) => {
  if (selectedBatchIds.value.includes(orderId)) {
    selectedBatchIds.value = selectedBatchIds.value.filter((id) => id !== orderId)
    return
  }
  selectedBatchIds.value = [...selectedBatchIds.value, orderId]
}

const isBatchSelected = (orderId: number) => selectedBatchIds.value.includes(orderId)

const takeSelectedOrders = async () => {
  if (!currentDriverId.value || !selectedBatchOrders.value.length || !isOnline.value) return
  for (const order of selectedBatchOrders.value) {
    await store.leaveForDelivery(order.id, currentDriverId.value)
  }
  selectedBatchIds.value = []
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

watch(
  selectableOrders,
  (orders) => {
    if (!orders.length) {
      selectedOrderId.value = null
      return
    }
    if (!selectedOrderId.value || !orders.some((order) => order.id === selectedOrderId.value)) {
      selectedOrderId.value = orders[0]?.id || null
    }
  },
  { immediate: true },
)

watch(
  readyQueueOrders,
  (orders) => {
    selectedBatchIds.value = selectedBatchIds.value.filter((id) => orders.some((order) => order.id === id))
  },
  { immediate: true },
)
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
      class="relative z-30 overflow-visible rounded-[24px] p-4"
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
      <div v-if="previewOrder" class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2">
          <div
            class="rounded-[18px] border px-3 py-3"
            :class="activeMission
              ? (isDarkTheme ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200' : 'border-emerald-200 bg-emerald-50 text-emerald-800')
              : (isDarkTheme ? 'border-[#334155] bg-[#0F172A] text-[#94A3B8]' : 'border-slate-200 bg-slate-50 text-slate-600')"
          >
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em]">Pedido activo</p>
            <p class="mt-1 text-sm font-bold">
              {{ activeMission ? `#${String(activeMission.id).padStart(4, '0')}` : 'Sin pedido en curso' }}
            </p>
            <p class="mt-1 text-xs">
              {{ activeMission ? (activeMission.status === 'ready' ? 'Listo para retirar' : 'Actualmente en entrega') : 'Todavia no tomaste ningun pedido.' }}
            </p>
          </div>
          <div
            class="rounded-[18px] border px-3 py-3"
            :class="isPreviewingDifferentOrder
              ? (isDarkTheme ? 'border-sky-500/40 bg-sky-500/10 text-sky-200' : 'border-sky-200 bg-sky-50 text-sky-800')
              : (isDarkTheme ? 'border-[#334155] bg-[#0F172A] text-[#94A3B8]' : 'border-slate-200 bg-slate-50 text-slate-600')"
          >
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em]">Pedido seleccionado</p>
            <p class="mt-1 text-sm font-bold">{{ previewOrderCode }}</p>
            <p class="mt-1 text-xs">
              {{ isPreviewingDifferentOrder ? 'Estas viendo otro pedido de la cola en el mapa.' : 'El mapa muestra el mismo pedido que esta activo.' }}
            </p>
          </div>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span
            class="inline-flex items-center rounded-xl px-3 py-1 text-sm font-bold"
            :class="isDarkTheme ? 'border border-emerald-400/70 bg-[#0F172A] text-[#F8FAFC]' : 'bg-slate-900 text-white'"
          >
            {{ previewOrderCode }}
          </span>
          <div class="flex items-center gap-2">
            <span
              class="rounded-full px-2.5 py-1 text-xs font-semibold"
              :class="[
                isPreviewingDifferentOrder
                  ? (isDarkTheme ? 'bg-sky-500/20 text-sky-300' : 'bg-sky-100 text-sky-700')
                  : (isDarkTheme ? 'bg-emerald-500/20 text-emerald-300' : 'bg-emerald-100 text-emerald-700'),
                previewOrder.status === 'onroute' ? 'status-glow' : '',
              ]"
            >
              {{ previewOrder.status === 'ready' ? 'En local' : 'En camino' }}
            </span>
            <span
              v-if="isPreviewingDifferentOrder"
              class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
              :class="isDarkTheme ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'bg-sky-100 text-sky-700'"
            >
              Vista previa
            </span>
          </div>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Direccion de entrega</p>
          <p class="mt-1 text-2xl font-extrabold leading-tight" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">{{ previewOrder.address }}</p>
        </div>
        <div class="overflow-hidden rounded-[20px] border" :class="isDarkTheme ? 'border-[#334155] bg-[#0F172A]' : 'border-slate-200 bg-slate-100'">
          <iframe
            v-if="mapEmbedUrl"
            :src="mapEmbedUrl"
            class="h-64 w-full border-0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Mapa de entrega"
          ></iframe>
          <div v-else class="grid h-64 place-items-center text-sm" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">
            No se pudo generar el mapa para esta direccion.
          </div>
        </div>
        <div>
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
        </div>
        <div class="flex items-center justify-between rounded-[16px] px-3 py-2" :class="isDarkTheme ? 'bg-[#0F172A]' : 'bg-slate-50'">
          <div>
            <p class="text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">{{ previewOrder.customer }}</p>
            <p class="text-xs" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Cliente</p>
          </div>
          <div class="flex items-center gap-2">
            <a :href="phoneHref" class="icon-btn"><Phone class="h-5 w-5" /></a>
            <a :href="`https://wa.me/?text=${encodeURIComponent(`Hola, voy con tu pedido ${previewOrderCode}.`)}`" target="_blank" rel="noopener noreferrer" class="icon-btn">
              <MessageCircle class="h-5 w-5" />
            </a>
          </div>
        </div>
        <div class="rounded-[16px] px-3 py-2" :class="isDarkTheme ? 'bg-[#0F172A]' : 'bg-slate-50'">
          <p class="text-xs font-semibold uppercase tracking-wide" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Resumen</p>
          <p class="mt-1 text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-800'">{{ orderSummary(previewOrder) }}</p>
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
        <div
          v-if="readyQueueOrders.length"
          class="flex flex-wrap items-center justify-between gap-2 rounded-[16px] px-3 py-2"
          :class="isDarkTheme ? 'bg-[#0F172A] border border-[#334155]' : 'bg-slate-50'"
        >
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">Seleccion multiple</p>
            <p class="text-sm font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-900'">
              {{ selectedBatchOrders.length }} pedido(s) listos para tomar
            </p>
          </div>
          <button
            type="button"
            class="rounded-full px-4 py-2 text-xs font-bold text-white transition active:scale-[0.98]"
            :class="selectedBatchOrders.length && isOnline ? (isDarkTheme ? 'bg-[#2563EB]' : 'bg-emerald-600') : 'bg-slate-400 cursor-not-allowed'"
            :disabled="!selectedBatchOrders.length || !isOnline"
            @click="takeSelectedOrders"
          >
            Tomar seleccionados
          </button>
        </div>
        <article
          v-for="order in pendingOrders"
          :key="`pending-${order.id}`"
          class="rounded-[16px] px-3 py-2 transition active:scale-[0.99]"
          :class="[
            isDarkTheme ? 'bg-[#0F172A] border border-[#334155]' : 'bg-slate-100',
            selectedOrderId === order.id
              ? (isDarkTheme ? 'ring-2 ring-emerald-400/70' : 'ring-2 ring-emerald-500')
              : '',
          ]"
          @click="selectOrder(order.id)"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <p class="text-xs font-semibold" :class="isDarkTheme ? 'text-[#F8FAFC]' : 'text-slate-700'">#{{ String(order.id).padStart(4, '0') }}</p>
              <span
                v-if="selectedOrderId === order.id"
                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                :class="isDarkTheme ? 'bg-sky-500/20 text-sky-300' : 'bg-sky-100 text-sky-700'"
              >
                Seleccionado
              </span>
              <span
                v-if="activeMission && activeMission.id === order.id"
                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                :class="isDarkTheme ? 'bg-emerald-500/20 text-emerald-300' : 'bg-emerald-100 text-emerald-700'"
              >
                Activo
              </span>
            </div>
            <div class="flex items-center gap-2">
              <label
                v-if="order.status === 'ready'"
                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold"
                :class="isBatchSelected(order.id)
                  ? (isDarkTheme ? 'bg-emerald-500/20 text-emerald-300' : 'bg-emerald-100 text-emerald-700')
                  : (isDarkTheme ? 'bg-[#1E293B] text-[#94A3B8]' : 'bg-white text-slate-500')"
                @click.stop
              >
                <input
                  type="checkbox"
                  class="h-3.5 w-3.5 rounded border-slate-300"
                  :checked="isBatchSelected(order.id)"
                  @change="toggleBatchOrder(order.id)"
                />
                Seleccionar
              </label>
              <p class="text-xs font-semibold" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">A {{ estimateDistance(order.id) }}</p>
            </div>
          </div>
          <p class="mt-1 truncate text-sm" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-600'">{{ order.address }}</p>
        </article>
        <p v-if="!pendingOrders.length" class="text-xs" :class="isDarkTheme ? 'text-[#94A3B8]' : 'text-slate-500'">No hay proximas paradas.</p>
      </div>
    </article>

    <div
      v-if="selectedActionOrder"
      class="fixed inset-x-0 bottom-0 z-40 border-t p-3 backdrop-blur"
      :class="isDarkTheme ? 'border-[#334155] bg-[#0F172A]/95' : 'border-slate-200 bg-white/95'"
    >
      <div class="mx-auto w-full max-w-3xl space-y-2">
        <div
          class="flex items-center justify-between rounded-2xl px-3 py-2 text-xs font-semibold"
          :class="isDarkTheme ? 'bg-[#1E293B] text-[#E2E8F0]' : 'bg-slate-100 text-slate-700'"
        >
          <span>Pedido seleccionado para accion</span>
          <span>{{ selectedActionOrderCode }}</span>
        </div>
        <div class="flex w-full flex-col gap-2 sm:flex-row">
        <button
          v-if="selectedActionOrder.status === 'ready'"
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold text-white transition active:scale-[0.98]"
          :class="!isOnline ? 'cursor-not-allowed bg-slate-400' : (isDarkTheme ? 'bg-[#2563EB]' : 'bg-emerald-600')"
          :disabled="!isOnline"
          @click="takeMission(selectedActionOrder.id)"
        >
          <Truck class="h-4 w-4" />
          Tomar pedido
        </button>
        <button
          v-else
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold text-white transition active:scale-[0.98]"
          :class="!isOnline ? 'cursor-not-allowed bg-slate-400' : (isDarkTheme ? 'bg-[#16A34A]' : 'bg-slate-900')"
          :disabled="!isOnline"
          @click="confirmDelivery(selectedActionOrder.id)"
        >
          <CheckCircle2 class="h-4 w-4" />
          Entregar pedido
        </button>
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
