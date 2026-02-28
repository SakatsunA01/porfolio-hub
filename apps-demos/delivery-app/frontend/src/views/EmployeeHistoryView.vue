<script setup lang="ts">
import { computed } from 'vue'
import { useDeliveryStore } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import EmployeeNavTabs from '../components/employee/EmployeeNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()
const businessLabel = computed(() => store.activeStorefrontName || 'Dunamis Store')

const recentKitchenOrders = computed(() =>
  [...store.orders]
    .filter((order) => ['ready', 'onroute', 'delivered'].includes(order.status))
    .sort((a, b) => b.createdAt - a.createdAt)
    .slice(0, 20),
)

const statusLabel = (status: string) => {
  if (status === 'ready') return 'Listo'
  if (status === 'onroute') return 'En camino'
  if (status === 'delivered') return 'Entregado'
  return status
}
</script>

<template>
  <article class="forest-card relative overflow-hidden p-4 backdrop-blur-md">
    <span class="forest-glow -right-8 -top-8"></span>
    <h2 class="text-lg font-bold text-slate-800">{{ businessLabel }}</h2>
    <p class="mt-1 text-sm text-slate-500">Seguimiento de pedidos procesados.</p>
    <EmployeeNavTabs />

    <div v-if="!recentKitchenOrders.length" class="mt-2 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
      Sin historial reciente.
    </div>

    <div v-else class="space-y-2">
      <article
        v-for="order in recentKitchenOrders"
        :key="`kitchen-history-${order.id}`"
        class="rounded-2xl border border-slate-200 bg-white p-3"
      >
        <div class="flex items-center justify-between gap-2">
          <p class="font-semibold text-slate-900">#ORD-{{ String(order.id).padStart(4, '0') }} | {{ order.customer }}</p>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ statusLabel(order.status) }}</span>
        </div>
        <p class="mt-1 text-xs text-slate-500">{{ order.address }}</p>
      </article>
    </div>
  </article>
</template>
