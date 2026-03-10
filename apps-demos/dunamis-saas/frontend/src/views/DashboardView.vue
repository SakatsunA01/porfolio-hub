<script setup>
import { computed, onMounted, ref } from 'vue'
import { dashboardApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const ui = useUiStore()
const loading = ref(true)
const error = ref('')
const payload = ref(null)
const lastUpdated = ref('')

const tableClass = computed(() => (ui.state.density === 'compact' ? 'table-wrap table-compact' : 'table-wrap'))

const loadDashboard = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await dashboardApi.fetch()
    payload.value = data.data
    lastUpdated.value = new Date().toLocaleString('es-AR')
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo cargar el dashboard. Reintenta en unos segundos.'
  } finally {
    loading.value = false
  }
}

const formatMoney = (value) => Number(value || 0).toLocaleString('es-AR')

onMounted(loadDashboard)
</script>

<template>
  <section class="grid gap-4">
    <PageHeader
      title="Dashboard operativo"
      subtitle="KPIs y alertas de operacion en tiempo real."
    >
      <span class="text-xs muted">Actualizado: {{ lastUpdated || 'sin datos' }}</span>
      <button type="button" class="btn btn-secondary" @click="loadDashboard">Actualizar</button>
    </PageHeader>

    <p v-if="error" class="surface px-3 py-2 text-sm" style="color: rgb(var(--destructive));">
      {{ error }}
    </p>

    <div class="grid gap-3 md:grid-cols-4">
      <article v-for="n in 4" v-if="loading" :key="n" class="surface p-4">
        <div class="skeleton h-3 w-24 rounded"></div>
        <div class="skeleton mt-3 h-7 w-28 rounded"></div>
      </article>
      <template v-else-if="payload">
        <article class="surface p-4">
          <p class="m-0 text-xs uppercase tracking-widest muted">Ventas hoy</p>
          <p class="m-0 mt-2 text-2xl font-semibold">$ {{ formatMoney(payload.stats.sales_today) }}</p>
        </article>
        <article class="surface p-4">
          <p class="m-0 text-xs uppercase tracking-widest muted">Tickets hoy</p>
          <p class="m-0 mt-2 text-2xl font-semibold">{{ payload.stats.sales_count_today }}</p>
        </article>
        <article class="surface p-4">
          <p class="m-0 text-xs uppercase tracking-widest muted">Stock bajo</p>
          <p class="m-0 mt-2 text-2xl font-semibold">{{ payload.stats.low_stock_count }}</p>
        </article>
        <article class="surface p-4">
          <p class="m-0 text-xs uppercase tracking-widest muted">Dolar blue</p>
          <p class="m-0 mt-2 text-2xl font-semibold">$ {{ formatMoney(payload.stats.blue_rate) }}</p>
        </article>
      </template>
    </div>

    <div class="grid gap-3 md:grid-cols-[1.1fr_minmax(0,1.9fr)]">
      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Alertas</h2>
        <ul class="m-0 mt-3 space-y-2 pl-4 text-sm">
          <li>Productos con stock bajo: {{ payload?.stats?.low_stock_count ?? '-' }}</li>
          <li>Revisar cierre del dia si hay ventas pendientes.</li>
          <li>Confirmar tipo de cambio antes de ventas en USD.</li>
        </ul>
      </article>

      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Ultimas ventas</h2>
        <div :class="tableClass" class="mt-3">
          <table class="d-table">
            <thead>
              <tr>
                <th class="sticky-col">ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Items</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!payload?.latest_sales?.length">
                <td colspan="5" class="py-8 text-center muted">Sin ventas recientes.</td>
              </tr>
              <tr v-for="sale in payload?.latest_sales || []" :key="sale.id">
                <td class="sticky-col">#{{ sale.id }}</td>
                <td>{{ sale.client_name }}</td>
                <td>$ {{ formatMoney(sale.total_amount) }}</td>
                <td>{{ sale.items_count }}</td>
                <td>{{ new Date(sale.created_at).toLocaleString('es-AR') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>
