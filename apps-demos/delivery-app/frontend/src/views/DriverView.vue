<script setup lang="ts">
import { computed, ref } from 'vue'
import { CheckCircle2, MapPin, MessageCircle, Phone } from 'lucide-vue-next'
import { useDeliveryStore, type Order } from '../stores/delivery'
import AppButton from '../components/common/AppButton.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

const store = useDeliveryStore()
useOrdersRealtime()
const isOnline = ref(true)
const successPulse = ref(false)

const ordersForDriver = computed(() =>
  store.orders.filter((order) => order.driverId === store.shiftDriverId || (order.status === 'ready' && order.driverId === null)),
)

const activeMission = computed<Order | null>(
  () => ordersForDriver.value.find((order) => order.status === 'onroute') || ordersForDriver.value.find((order) => order.status === 'ready') || null,
)

const pendingOrders = computed(() => ordersForDriver.value.filter((order) => !activeMission.value || order.id !== activeMission.value.id))

const estimateDistance = (orderId: number) => `${((orderId % 7) + 1.2).toFixed(1)} km`
const estimateGain = (orderId: number) => `$${((orderId % 9) + 6) * 700}`

const orderSummary = (order: Order) =>
  order.items
    .map((item) => `${item.qty}x ${store.getProduct(item.productId)?.name || 'Producto'}`)
    .join(' | ')

const takeMission = (orderId: number) => {
  if (!store.shiftDriverId || !isOnline.value) return
  store.leaveForDelivery(orderId, store.shiftDriverId)
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
</script>

<template>
  <section class="relative space-y-4">
    <span class="absolute right-0 top-0 flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">
      <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
      Sincronizado
    </span>

    <header class="forest-card relative overflow-hidden p-4">
      <span class="forest-glow -right-8 -top-8"></span>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Mapa de Ruta</h2>
          <p class="text-sm text-slate-500">Navegacion y entregas con foco en eficiencia.</p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500">Estado de disponibilidad</span>
          <button type="button" class="toggle" :class="isOnline ? 'on' : 'off'" @click="isOnline = !isOnline">
            <span></span>
          </button>
        </div>
      </div>
    </header>

    <div class="grid gap-4 lg:grid-cols-[1.45fr_1fr]">
      <section class="space-y-4">
        <article class="forest-card p-4">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Mision Activa</h3>

          <div v-if="activeMission" class="mt-3 space-y-3">
            <div class="flex items-start gap-3 rounded-xl border border-slate-200/60 bg-white/90 p-3">
              <MapPin class="mt-0.5 h-5 w-5 text-emerald-600" />
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ activeMission.address }}</p>
                <p class="text-xs text-slate-500">Destino de entrega</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/70 bg-white p-3">
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ activeMission.customer }}</p>
                <p class="text-xs text-slate-500">Cliente</p>
              </div>
              <StatusBadge :value="activeMission.status === 'onroute' ? 'En Camino' : 'Pendiente'" />
              <div class="flex items-center gap-2">
                <button type="button" class="icon-btn">
                  <Phone class="h-4 w-4" />
                </button>
                <button type="button" class="icon-btn">
                  <MessageCircle class="h-4 w-4" />
                </button>
              </div>
            </div>

            <p class="text-xs text-slate-600">{{ orderSummary(activeMission) }}</p>

            <AppButton
              v-if="activeMission.status === 'ready'"
              variant="primary"
              :full="true"
              py="py-4"
              :disabled="!isOnline"
              @click="takeMission(activeMission.id)"
            >
              Iniciar Entrega
            </AppButton>

            <AppButton
              v-else
              variant="primary"
              :full="true"
              py="py-4"
              :disabled="!isOnline"
              @click="confirmDelivery(activeMission.id)"
            >
              Confirmar Entrega
            </AppButton>
          </div>

          <div v-else class="mt-3 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
            No hay mision activa por ahora.
          </div>
        </article>

        <article class="forest-card p-3">
          <div class="aspect-[16/9] rounded-xl border border-slate-200 bg-slate-50 p-3">
            <svg viewBox="0 0 620 340" class="h-full w-full">
              <path d="M20 290 C110 260, 190 185, 270 195 C360 205, 420 120, 600 35" stroke="#cbd5e1" stroke-width="7" fill="none" stroke-linecap="round" />
              <path d="M20 290 C110 260, 190 185, 270 195 C360 205, 420 120, 600 35" stroke="#10b981" stroke-width="4" fill="none" stroke-linecap="round" />
              <circle cx="20" cy="290" r="11" fill="#0f172a" />
              <circle cx="600" cy="35" r="11" fill="#10b981" />
              <text x="35" y="285" fill="#334155" font-size="14" font-weight="700">Local</text>
              <text x="515" y="28" fill="#065f46" font-size="14" font-weight="700">Cliente</text>
            </svg>
          </div>
        </article>
      </section>

      <aside class="forest-card p-4">
        <h3 class="text-sm font-semibold text-slate-900">Pedidos Disponibles</h3>
        <p class="mt-1 text-xs text-slate-500">Selecciona una orden para incorporarla a tu ruta.</p>

        <div class="mt-3 space-y-2">
          <article
            v-for="order in pendingOrders"
            :key="`pending-${order.id}`"
            class="rounded-xl border border-slate-200/70 bg-white p-3 shadow-sm"
          >
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">#ORD-{{ order.id }}</p>
              <p class="text-xs font-semibold text-slate-500">ETA {{ order.etaMin }} min</p>
            </div>
            <p class="mt-1 truncate text-xs text-slate-500">{{ order.address }}</p>
            <div class="mt-2 flex items-center justify-between text-xs">
              <span class="font-medium text-slate-600">{{ estimateDistance(order.id) }}</span>
              <span class="font-semibold text-emerald-600">{{ estimateGain(order.id) }}</span>
            </div>
            <AppButton variant="soft" :full="true" py="py-3" class="mt-3" :disabled="!isOnline" @click="takeMission(order.id)">
              Tomar Pedido
            </AppButton>
          </article>

          <div v-if="!pendingOrders.length" class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
            No hay pedidos pendientes para tomar.
          </div>
        </div>
      </aside>
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
  border: 1px solid rgb(226 232 240);
  background: white;
  color: rgb(71 85 105);
  border-radius: 999px;
  width: 2rem;
  height: 2rem;
  display: grid;
  place-items: center;
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
