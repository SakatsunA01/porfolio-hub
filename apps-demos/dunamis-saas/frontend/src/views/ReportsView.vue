<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { reportsApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const ui = useUiStore()
const loading = ref(true)
const error = ref('')
const rows = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const sort = reactive({ key: 'created_at', dir: 'desc' })
const filters = reactive({ from: '', to: '' })

const tableClass = computed(() => (ui.state.density === 'compact' ? 'table-wrap table-compact' : 'table-wrap'))
const hasFilters = computed(() => Boolean(filters.from || filters.to))

const sortedRows = computed(() => {
  const next = [...rows.value]
  next.sort((a, b) => {
    const normalize = (v) => (typeof v === 'string' ? v.toLowerCase() : Number(v || 0))
    const aValue = normalize(a[sort.key])
    const bValue = normalize(b[sort.key])
    const cmp = aValue > bValue ? 1 : aValue < bValue ? -1 : 0
    return sort.dir === 'asc' ? cmp : -cmp
  })
  return next
})

const load = async (page = pagination.value.current_page || 1) => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await reportsApi.profit({
      from: filters.from || undefined,
      to: filters.to || undefined,
      page,
      per_page: pagination.value.per_page,
    })
    rows.value = data.data
    pagination.value = data.meta?.pagination || pagination.value
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo cargar el reporte. Prueba con otro rango de fechas.'
  } finally {
    loading.value = false
  }
}

const clearFilters = async () => {
  filters.from = ''
  filters.to = ''
  await load(1)
}

const setSort = (key) => {
  if (sort.key === key) {
    sort.dir = sort.dir === 'asc' ? 'desc' : 'asc'
  } else {
    sort.key = key
    sort.dir = 'asc'
  }
}

onMounted(() => load(1))
</script>

<template>
  <section class="grid gap-4">
    <PageHeader title="Reportes" subtitle="Rentabilidad por venta y margen operativo.">
      <button type="button" class="btn btn-secondary" @click="load()">Actualizar</button>
    </PageHeader>

    <article class="surface p-4">
      <div class="grid gap-2 md:grid-cols-[1fr_1fr_auto_auto]">
        <input v-model="filters.from" type="date" class="field" />
        <input v-model="filters.to" type="date" class="field" />
        <button type="button" class="btn btn-secondary" @click="load(1)">Aplicar</button>
        <button type="button" class="btn btn-secondary" @click="clearFilters">Limpiar</button>
      </div>

      <div v-if="hasFilters" class="mt-2 flex flex-wrap gap-2">
        <span v-if="filters.from" class="chip">Desde: {{ filters.from }}</span>
        <span v-if="filters.to" class="chip">Hasta: {{ filters.to }}</span>
      </div>

      <p v-if="error" class="mt-3 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

      <div v-if="loading" :class="tableClass" class="mt-3">
        <div class="skeleton h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
      </div>
      <template v-else>
        <div v-if="sortedRows.length === 0" class="mt-3 rounded-xl border border-dashed p-8 text-center muted" style="border-color: rgb(var(--border));">
          No hay datos para el rango seleccionado.
        </div>
        <div v-else :class="tableClass" class="mt-3">
          <table class="d-table">
            <thead>
              <tr>
                <th class="sticky-col" @click="setSort('id')">ID</th>
                <th @click="setSort('client')" class="cursor-pointer">Cliente</th>
                <th @click="setSort('total')" class="cursor-pointer">Total</th>
                <th @click="setSort('cost_total')" class="cursor-pointer">Costo</th>
                <th @click="setSort('profit')" class="cursor-pointer">Ganancia</th>
                <th @click="setSort('margin')" class="cursor-pointer">Margen</th>
                <th @click="setSort('created_at')" class="cursor-pointer">Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in sortedRows" :key="row.id">
                <td class="sticky-col">#{{ row.id }}</td>
                <td>{{ row.client }}</td>
                <td>$ {{ Number(row.total).toLocaleString('es-AR') }}</td>
                <td>$ {{ Number(row.cost_total).toLocaleString('es-AR') }}</td>
                <td>$ {{ Number(row.profit).toLocaleString('es-AR') }}</td>
                <td>{{ row.margin }}%</td>
                <td>{{ new Date(row.created_at).toLocaleString('es-AR') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex items-center justify-end gap-2">
          <button type="button" class="btn btn-secondary" :disabled="pagination.current_page <= 1" @click="load(pagination.current_page - 1)">Anterior</button>
          <span class="text-sm muted">Pagina {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <button type="button" class="btn btn-secondary" :disabled="pagination.current_page >= pagination.last_page" @click="load(pagination.current_page + 1)">Siguiente</button>
        </div>
      </template>
    </article>
  </section>
</template>
