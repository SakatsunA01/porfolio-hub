<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { productsApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const error = ref('')
const items = ref([])
const selectedIds = ref([])
const editingId = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = reactive({
  search: '',
  stock: 'all',
})

const sort = reactive({
  key: 'created_at',
  dir: 'desc',
})

const form = reactive({
  sku: '',
  name: '',
  description: '',
  cost_ars: '',
  cost_usd: '',
  sale_price: '',
  stock_quantity: 0,
  min_stock_quantity: 0,
  image: null,
})

const tableClass = computed(() => (ui.state.density === 'compact' ? 'table-wrap table-compact' : 'table-wrap'))
const hasFilters = computed(() => Boolean(filters.search) || filters.stock !== 'all')

const sortedFiltered = computed(() => {
  const list = [...items.value]
  let next = list
  if (filters.stock === 'low') {
    next = next.filter((item) => Number(item.stock_quantity) <= Number(item.min_stock_quantity))
  } else if (filters.stock === 'out') {
    next = next.filter((item) => Number(item.stock_quantity) === 0)
  }
  next.sort((a, b) => {
    const aValue = a[sort.key]
    const bValue = b[sort.key]
    const normalize = (v) => (typeof v === 'string' ? v.toLowerCase() : Number(v || 0))
    const cmp = normalize(aValue) > normalize(bValue) ? 1 : normalize(aValue) < normalize(bValue) ? -1 : 0
    return sort.dir === 'asc' ? cmp : -cmp
  })
  return next
})

const allSelected = computed({
  get: () => sortedFiltered.value.length > 0 && sortedFiltered.value.every((i) => selectedIds.value.includes(i.id)),
  set: (checked) => {
    selectedIds.value = checked ? sortedFiltered.value.map((i) => i.id) : []
  },
})

const resetForm = () => {
  editingId.value = null
  form.sku = ''
  form.name = ''
  form.description = ''
  form.cost_ars = ''
  form.cost_usd = ''
  form.sale_price = ''
  form.stock_quantity = 0
  form.min_stock_quantity = 0
  form.image = null
}

const load = async (page = pagination.value.current_page || 1) => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await productsApi.list({
      search: filters.search || undefined,
      per_page: pagination.value.per_page,
      page,
    })
    items.value = data.data
    pagination.value = data.meta?.pagination || pagination.value
    selectedIds.value = []
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudieron cargar productos. Revisa conexion y permisos.'
  } finally {
    loading.value = false
  }
}

const onFileChange = (event) => {
  form.image = event.target.files?.[0] || null
}

const buildPayload = () => {
  const payload = new FormData()
  payload.append('sku', form.sku)
  payload.append('name', form.name)
  payload.append('description', form.description || '')
  payload.append('cost_ars', form.cost_ars || '')
  payload.append('cost_usd', form.cost_usd || '')
  payload.append('sale_price', String(form.sale_price || 0))
  payload.append('stock_quantity', String(form.stock_quantity || 0))
  payload.append('min_stock_quantity', String(form.min_stock_quantity || 0))
  if (form.image) payload.append('image', form.image)
  return payload
}

const submit = async () => {
  error.value = ''
  try {
    if (editingId.value) {
      await productsApi.update(editingId.value, buildPayload())
      ui.toast('Producto actualizado.', 'success')
    } else {
      await productsApi.create(buildPayload())
      ui.toast('Producto creado.', 'success')
    }
    resetForm()
    await load(1)
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo guardar el producto.'
    ui.toast('Error al guardar producto.', 'error')
  }
}

const editItem = (item) => {
  editingId.value = item.id
  form.sku = item.sku
  form.name = item.name
  form.description = item.description || ''
  form.cost_ars = item.cost_ars ?? ''
  form.cost_usd = item.cost_usd ?? ''
  form.sale_price = item.sale_price
  form.stock_quantity = item.stock_quantity
  form.min_stock_quantity = item.min_stock_quantity
  form.image = null
}

const removeItem = async (id) => {
  const ok = window.confirm('Eliminar producto seleccionado?')
  if (!ok) return
  try {
    await productsApi.remove(id)
    ui.toast('Producto eliminado.', 'success')
    await load()
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo eliminar producto.'
  }
}

const removeSelected = async () => {
  if (!selectedIds.value.length) return
  const ok = window.confirm(`Eliminar ${selectedIds.value.length} productos seleccionados?`)
  if (!ok) return
  try {
    await Promise.all(selectedIds.value.map((id) => productsApi.remove(id)))
    ui.toast('Eliminacion masiva completada.', 'success')
    await load()
  } catch {
    error.value = 'No se pudieron eliminar todos los productos seleccionados.'
  }
}

const clearFilters = async () => {
  filters.search = ''
  filters.stock = 'all'
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

watch(
  () => route.query.focus,
  (focus) => {
    if (focus === 'sku') {
      const input = document.getElementById('product-search')
      input?.focus()
      router.replace({ path: route.path, query: {} })
    }
  },
  { immediate: true },
)

onMounted(() => load(1))
</script>

<template>
  <section class="grid gap-4">
    <PageHeader title="Productos" subtitle="Gestion de catalogo, stock y precios.">
      <button type="button" class="btn btn-secondary" @click="load()">Actualizar</button>
      <button type="button" class="btn btn-primary" @click="document.getElementById('product-form')?.scrollIntoView({ behavior: 'smooth' })">
        Nuevo producto
      </button>
    </PageHeader>

    <article id="product-form" class="surface p-4">
      <h2 class="m-0 text-base font-semibold">{{ editingId ? 'Editar producto' : 'Alta rapida' }}</h2>
      <form class="mt-3 grid gap-2" @submit.prevent="submit">
        <div class="grid gap-2 md:grid-cols-2">
          <input v-model="form.sku" class="field" placeholder="SKU" required />
          <input v-model="form.name" class="field" placeholder="Nombre" required />
        </div>
        <textarea v-model="form.description" class="field" rows="2" placeholder="Descripcion"></textarea>
        <div class="grid gap-2 md:grid-cols-4">
          <input v-model="form.cost_ars" class="field" type="number" min="0" step="0.01" placeholder="Costo ARS" />
          <input v-model="form.cost_usd" class="field" type="number" min="0" step="0.01" placeholder="Costo USD" />
          <input v-model="form.sale_price" class="field" type="number" min="0" step="0.01" placeholder="Precio venta" required />
          <input v-model="form.stock_quantity" class="field" type="number" min="0" placeholder="Stock" required />
        </div>
        <div class="grid gap-2 md:grid-cols-2">
          <input v-model="form.min_stock_quantity" class="field" type="number" min="0" placeholder="Stock minimo" required />
          <input type="file" class="field" accept=".jpg,.jpeg,.png,image/*" @change="onFileChange" />
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary">{{ editingId ? 'Guardar cambios' : 'Crear producto' }}</button>
          <button v-if="editingId" type="button" class="btn btn-secondary" @click="resetForm">Cancelar</button>
        </div>
      </form>
    </article>

    <article class="surface p-4">
      <div class="grid gap-2 md:grid-cols-[2fr_1fr_auto_auto]">
        <input id="product-search" v-model="filters.search" class="field" placeholder="Buscar por SKU o nombre" @keyup.enter="load(1)" />
        <select v-model="filters.stock" class="field">
          <option value="all">Todo el stock</option>
          <option value="low">Stock bajo</option>
          <option value="out">Sin stock</option>
        </select>
        <button type="button" class="btn btn-secondary" @click="load(1)">Aplicar</button>
        <button type="button" class="btn btn-secondary" @click="clearFilters">Limpiar</button>
      </div>

      <div v-if="hasFilters" class="mt-2 flex flex-wrap gap-2">
        <span v-if="filters.search" class="chip">Busqueda: {{ filters.search }}</span>
        <span v-if="filters.stock !== 'all'" class="chip">Stock: {{ filters.stock }}</span>
      </div>

      <p v-if="error" class="mt-3 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

      <div v-if="loading" :class="tableClass" class="mt-3">
        <div class="skeleton h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
      </div>

      <template v-else>
        <div v-if="sortedFiltered.length === 0" class="mt-3 rounded-xl border border-dashed p-8 text-center muted" style="border-color: rgb(var(--border));">
          No hay productos para este filtro.
          <div class="mt-3">
            <button type="button" class="btn btn-secondary" @click="clearFilters">Ver todo</button>
          </div>
        </div>

        <div v-else :class="tableClass" class="mt-3">
          <table class="d-table">
            <thead>
              <tr>
                <th class="sticky-col"><input v-model="allSelected" type="checkbox" /></th>
                <th @click="setSort('sku')" class="cursor-pointer">SKU</th>
                <th @click="setSort('name')" class="cursor-pointer">Nombre</th>
                <th @click="setSort('sale_price')" class="cursor-pointer">Precio</th>
                <th @click="setSort('stock_quantity')" class="cursor-pointer">Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in sortedFiltered" :key="item.id">
                <td class="sticky-col">
                  <input v-model="selectedIds" :value="item.id" type="checkbox" />
                </td>
                <td>{{ item.sku }}</td>
                <td>{{ item.name }}</td>
                <td>$ {{ Number(item.sale_price).toLocaleString('es-AR') }}</td>
                <td>{{ item.stock_quantity }}</td>
                <td>
                  <span class="chip" :style="Number(item.stock_quantity) <= Number(item.min_stock_quantity) ? 'color: rgb(var(--warn)); border-color: rgb(var(--warn));' : ''">
                    {{ Number(item.stock_quantity) === 0 ? 'Sin stock' : Number(item.stock_quantity) <= Number(item.min_stock_quantity) ? 'Stock bajo' : 'Disponible' }}
                  </span>
                </td>
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
