<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { clientsApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const ui = useUiStore()
const loading = ref(true)
const error = ref('')
const items = ref([])
const selectedIds = ref([])
const editingId = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = reactive({
  search: '',
})

const sort = reactive({
  key: 'name',
  dir: 'asc',
})

const form = reactive({
  name: '',
  tax_id: '',
  email: '',
  phone: '',
  address: '',
})

const tableClass = computed(() => (ui.state.density === 'compact' ? 'table-wrap table-compact' : 'table-wrap'))
const hasFilters = computed(() => Boolean(filters.search))

const sortedRows = computed(() => {
  const next = [...items.value]
  next.sort((a, b) => {
    const aValue = String(a[sort.key] || '').toLowerCase()
    const bValue = String(b[sort.key] || '').toLowerCase()
    const cmp = aValue > bValue ? 1 : aValue < bValue ? -1 : 0
    return sort.dir === 'asc' ? cmp : -cmp
  })
  return next
})

const allSelected = computed({
  get: () => sortedRows.value.length > 0 && sortedRows.value.every((i) => selectedIds.value.includes(i.id)),
  set: (checked) => {
    selectedIds.value = checked ? sortedRows.value.map((i) => i.id) : []
  },
})

const resetForm = () => {
  editingId.value = null
  form.name = ''
  form.tax_id = ''
  form.email = ''
  form.phone = ''
  form.address = ''
}

const load = async (page = pagination.value.current_page || 1) => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await clientsApi.list({
      search: filters.search || undefined,
      per_page: pagination.value.per_page,
      page,
    })
    items.value = data.data
    pagination.value = data.meta?.pagination || pagination.value
    selectedIds.value = []
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudieron cargar clientes. Verifica permisos.'
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  error.value = ''
  try {
    const payload = { ...form }
    if (editingId.value) {
      await clientsApi.update(editingId.value, payload)
      ui.toast('Cliente actualizado.', 'success')
    } else {
      await clientsApi.create(payload)
      ui.toast('Cliente creado.', 'success')
    }
    resetForm()
    await load(1)
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo guardar el cliente.'
    ui.toast('Error al guardar cliente.', 'error')
  }
}

const editItem = (item) => {
  editingId.value = item.id
  form.name = item.name
  form.tax_id = item.tax_id || ''
  form.email = item.email || ''
  form.phone = item.phone || ''
  form.address = item.address || ''
}

const removeItem = async (id) => {
  const ok = window.confirm('Eliminar cliente?')
  if (!ok) return
  try {
    await clientsApi.remove(id)
    ui.toast('Cliente eliminado.', 'success')
    await load()
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo eliminar cliente.'
  }
}

const removeSelected = async () => {
  if (!selectedIds.value.length) return
  const ok = window.confirm(`Eliminar ${selectedIds.value.length} clientes seleccionados?`)
  if (!ok) return
  try {
    await Promise.all(selectedIds.value.map((id) => clientsApi.remove(id)))
    ui.toast('Eliminacion masiva completada.', 'success')
    await load()
  } catch {
    error.value = 'No se pudieron eliminar todos los clientes seleccionados.'
  }
}

const clearFilters = async () => {
  filters.search = ''
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
    <PageHeader title="Clientes" subtitle="Directorio operativo y datos de contacto.">
      <button type="button" class="btn btn-secondary" @click="load()">Actualizar</button>
      <button type="button" class="btn btn-primary" @click="document.getElementById('client-form')?.scrollIntoView({ behavior: 'smooth' })">
        Nuevo cliente
      </button>
    </PageHeader>

    <article id="client-form" class="surface p-4">
      <h2 class="m-0 text-base font-semibold">{{ editingId ? 'Editar cliente' : 'Alta de cliente' }}</h2>
      <form class="mt-3 grid gap-2" @submit.prevent="submit">
        <div class="grid gap-2 md:grid-cols-2">
          <input v-model="form.name" class="field" placeholder="Nombre" required />
          <input v-model="form.tax_id" class="field" placeholder="CUIT/DNI" />
        </div>
        <div class="grid gap-2 md:grid-cols-2">
          <input v-model="form.email" class="field" type="email" placeholder="Email" />
          <input v-model="form.phone" class="field" placeholder="Telefono" />
        </div>
        <input v-model="form.address" class="field" placeholder="Direccion" />
        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary">{{ editingId ? 'Guardar cambios' : 'Crear cliente' }}</button>
          <button v-if="editingId" type="button" class="btn btn-secondary" @click="resetForm">Cancelar</button>
        </div>
      </form>
    </article>

    <article class="surface p-4">
      <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_auto_auto]">
        <input v-model="filters.search" class="field" placeholder="Buscar por nombre, email o CUIT" @keyup.enter="load(1)" />
        <button type="button" class="btn btn-secondary" @click="load(1)">Aplicar</button>
        <button type="button" class="btn btn-secondary" @click="clearFilters">Limpiar</button>
      </div>

      <div v-if="hasFilters" class="mt-2 flex flex-wrap gap-2">
        <span class="chip">Busqueda: {{ filters.search }}</span>
      </div>

      <p v-if="error" class="mt-3 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

      <div v-if="loading" :class="tableClass" class="mt-3">
        <div class="skeleton h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
      </div>

      <template v-else>
        <div v-if="sortedRows.length === 0" class="mt-3 rounded-xl border border-dashed p-8 text-center muted" style="border-color: rgb(var(--border));">
          No hay clientes para mostrar.
          <div class="mt-3">
            <button type="button" class="btn btn-secondary" @click="clearFilters">Ver todo</button>
          </div>
        </div>

        <div v-else :class="tableClass" class="mt-3">
          <table class="d-table">
            <thead>
              <tr>
                <th class="sticky-col"><input v-model="allSelected" type="checkbox" /></th>
                <th @click="setSort('name')" class="cursor-pointer">Nombre</th>
                <th @click="setSort('tax_id')" class="cursor-pointer">CUIT/DNI</th>
                <th @click="setSort('email')" class="cursor-pointer">Email</th>
                <th>Telefono</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in sortedRows" :key="item.id">
                <td class="sticky-col"><input v-model="selectedIds" :value="item.id" type="checkbox" /></td>
                <td>{{ item.name }}</td>
                <td>{{ item.tax_id || '-' }}</td>
                <td>{{ item.email || '-' }}</td>
                <td>{{ item.phone || '-' }}</td>
                <td class="flex flex-wrap gap-2">
                  <button type="button" class="btn btn-secondary !px-2 !py-1 text-xs" @click="editItem(item)">Editar</button>
                  <button type="button" class="btn btn-danger !px-2 !py-1 text-xs" @click="removeItem(item.id)">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" :disabled="!selectedIds.length" @click="selectedIds = []">Limpiar seleccion</button>
            <button type="button" class="btn btn-danger" :disabled="!selectedIds.length" @click="removeSelected">Eliminar seleccionados</button>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" class="btn btn-secondary" :disabled="pagination.current_page <= 1" @click="load(pagination.current_page - 1)">Anterior</button>
            <span class="text-sm muted">Pagina {{ pagination.current_page }} de {{ pagination.last_page }}</span>
            <button type="button" class="btn btn-secondary" :disabled="pagination.current_page >= pagination.last_page" @click="load(pagination.current_page + 1)">Siguiente</button>
          </div>
        </div>
      </template>
    </article>
  </section>
</template>
