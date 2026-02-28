<script setup lang="ts">
import { computed } from 'vue'
import { useDeliveryStore } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import DriverNavTabs from '../components/driver/DriverNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()
const currentDriverId = computed(() => store.shiftDriverId || store.currentUser?.id || null)

const deliveredOrders = computed(() =>
  [...store.orders]
    .filter((order) => order.driverId === currentDriverId.value && order.status === 'delivered')
    .sort((a, b) => b.createdAt - a.createdAt)
    .slice(0, 20),
)

const pendingOrders = computed(() =>
  [...store.orders]
    .filter((order) => order.driverId === currentDriverId.value && ['ready', 'onroute'].includes(order.status))
    .sort((a, b) => b.createdAt - a.createdAt)
    .slice(0, 20),
)

const statusLabel = (status: string) => {
  if (status === 'onroute') return 'En camino'
  if (status === 'delivered') return 'Entregado'
  if (status === 'ready') return 'Listo'
  return status
}
</script>

<template>
  <section class="relative space-y-4">
    <header class="forest-card relative overflow-hidden p-4">
      <span class="forest-glow -right-8 -top-8"></span>
      <h2 class="text-xl font-semibold text-slate-900">Historial de Entregas</h2>
      <p class="text-sm text-slate-500">Consulta tus misiones recientes.</p>
      <DriverNavTabs />
    </header>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Pedidos pendientes</h3>
      <div v-if="!pendingOrders.length" class="mt-3 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
        No hay pedidos pendientes para este repartidor.
      </div>
      <div v-else class="mt-3 space-y-2">
        <div
          v-for="order in pendingOrders"
          :key="`driver-pending-${order.id}`"
          class="rounded-xl border border-amber-200 bg-amber-50/50 p-3"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="font-semibold text-slate-900">#ORD-{{ String(order.id).padStart(4, '0') }} | {{ order.customer }}</p>
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ statusLabel(order.status) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-600">{{ order.address }}</p>
        </div>
      </div>
    </article>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Pedidos entregados</h3>
      <div v-if="!deliveredOrders.length" class="mt-3 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
        No hay pedidos entregados para este repartidor.
      </div>
      <div v-else class="mt-3 space-y-2">
        <div
          v-for="order in deliveredOrders"
          :key="`driver-history-${order.id}`"
          class="rounded-xl border border-slate-200 bg-white p-3"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="font-semibold text-slate-900">#ORD-{{ String(order.id).padStart(4, '0') }} | {{ order.customer }}</p>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ statusLabel(order.status) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ order.address }}</p>
        </div>
      </div>
    </article>
  </section>
</template>
