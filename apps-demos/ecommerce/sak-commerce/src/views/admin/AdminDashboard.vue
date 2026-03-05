<template>
  <AdminLayout>
    <div class="space-y-8">
      <div>
        <p class="text-sm tracking-wide text-text-secondary">Dashboard</p>
        <h1 class="mt-2 font-serif text-4xl tracking-wide text-text-primary">
          Estado del negocio
        </h1>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="card in cards"
          :key="card.label"
          class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6"
        >
          <template v-if="isLoading">
            <div class="space-y-4">
              <div class="h-4 w-28 animate-pulse rounded bg-bg-secondary" />
              <div class="h-10 w-20 animate-pulse rounded bg-bg-secondary" />
            </div>
          </template>

          <template v-else>
            <p class="text-sm tracking-wide text-text-secondary">{{ card.label }}</p>
            <p class="mt-4 font-serif text-4xl tracking-wide text-text-primary">
              {{ card.value }}
            </p>
          </template>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '../../components/admin/AdminLayout.vue'
import api from '../../services/api'
import { formatPrice } from '../../utils/catalog'

type DashboardMetrics = {
  sales_this_month: number
  pending_orders: number
  out_of_stock_products: number
  active_preorders: number
}

const isLoading = ref(true)
const metrics = ref<DashboardMetrics>({
  sales_this_month: 0,
  pending_orders: 0,
  out_of_stock_products: 0,
  active_preorders: 0,
})

const cards = computed(() => [
  { label: 'Total ventas mes', value: formatPrice(metrics.value.sales_this_month) },
  { label: 'Ordenes pendientes', value: metrics.value.pending_orders.toString() },
  { label: 'Productos sin stock', value: metrics.value.out_of_stock_products.toString() },
  { label: 'Preventas activas', value: metrics.value.active_preorders.toString() },
])

onMounted(async () => {
  try {
    const response = await api.get('/admin/dashboard')
    metrics.value = response.data.data
  } finally {
    isLoading.value = false
  }
})
</script>
