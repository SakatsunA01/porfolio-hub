<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { clientsApi, productsApi, salesApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const ui = useUiStore()

const loading = ref(true)
const error = ref('')
const sales = ref([])
const products = ref([])
const clients = ref([])
const productSearch = ref('')
const selectedClientId = ref('')
const selectedProductId = ref('')
const selectedQty = ref(1)
const items = ref([])
const moneda = ref('ARS')
const exchangeRate = ref('')

const tableClass = computed(() => (ui.state.density === 'compact' ? 'table-wrap table-compact' : 'table-wrap'))

const filteredProducts = computed(() => {
  const q = productSearch.value.trim().toLowerCase()
  if (!q) return products.value
  return products.value.filter(
    (item) => item.name.toLowerCase().includes(q) || item.sku.toLowerCase().includes(q),
  )
})

const total = computed(() => items.value.reduce((sum, item) => sum + item.cantidad * item.precio_unitario, 0))

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const [salesRes, productsRes, clientsRes] = await Promise.all([
      salesApi.list({ per_page: 25 }),
      productsApi.list({ per_page: 100 }),
      clientsApi.list({ per_page: 100 }),
    ])
    sales.value = salesRes.data.data
    products.value = productsRes.data.data
    clients.value = clientsRes.data.data
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo cargar ventas. Revisa conexion o permisos.'
  } finally {
    loading.value = false
  }
}

const productById = (id) => products.value.find((item) => item.id === Number(id))

const addItem = () => {
  const product = productById(selectedProductId.value)
  if (!product) return
  const qty = Math.max(1, Number(selectedQty.value || 1))
  items.value.push({
    product_id: product.id,
    sku: product.sku,
    name: product.name,
    cantidad: qty,
    precio_unitario: Number(product.sale_price),
  })
  selectedProductId.value = ''
  selectedQty.value = 1
  ui.toast('Item agregado al ticket.', 'success')
}

const removeItem = (index) => {
  items.value.splice(index, 1)
}

const submitSale = async () => {
  if (items.value.length === 0) {
    error.value = 'Agrega al menos un item para registrar la venta.'
    return
  }
  error.value = ''
  try {
    await salesApi.create({
      items: items.value.map((item) => ({
        product_id: item.product_id,
        cantidad: item.cantidad,
        precio_unitario: item.precio_unitario,
      })),
      client_id: selectedClientId.value ? Number(selectedClientId.value) : null,
      moneda_cobro: moneda.value,
      exchange_rate_used: moneda.value === 'USD' && exchangeRate.value ? Number(exchangeRate.value) : null,
    })
    items.value = []
    selectedClientId.value = ''
    moneda.value = 'ARS'
    exchangeRate.value = ''
    ui.toast('Venta registrada.', 'success')
    await load()
  } catch (err) {
    error.value = err?.response?.data?.errors?.items?.[0]
      || err?.response?.data?.message
      || 'No se pudo registrar la venta.'
    ui.toast('Error al registrar venta.', 'error')
  }
}

const keyboardHandler = (event) => {
  if (event.ctrlKey && event.key === 'Enter') {
    event.preventDefault()
    submitSale()
  }
  if (event.altKey && event.key.toLowerCase() === 'a') {
    event.preventDefault()
    addItem()
  }
}

onMounted(() => {
  load()
  window.addEventListener('keydown', keyboardHandler)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', keyboardHandler)
})
</script>

<template>
  <section class="grid gap-4">
    <PageHeader title="Ventas" subtitle="Flujo de caja/POS dentro del modulo comercial.">
      <span class="text-xs muted">Atajos: Alt+A agrega item, Ctrl+Enter confirma venta.</span>
      <button type="button" class="btn btn-secondary" @click="load">Actualizar</button>
    </PageHeader>

    <p v-if="error" class="surface px-3 py-2 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

    <div class="grid gap-3 lg:grid-cols-[1.1fr_0.9fr]">
      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Nueva venta</h2>
        <div class="mt-3 grid gap-2">
          <div class="grid gap-2 md:grid-cols-2">
            <select v-model="selectedClientId" class="field">
              <option value="">Consumidor final</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <select v-model="moneda" class="field">
              <option value="ARS">ARS</option>
              <option value="USD">USD</option>
            </select>
          </div>
          <input
            v-if="moneda === 'USD'"
            v-model="exchangeRate"
            type="number"
            min="0"
            step="0.01"
            class="field"
            placeholder="Tipo de cambio usado"
          />

          <input v-model="productSearch" class="field" placeholder="Buscar producto por SKU o nombre" />
          <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_100px_auto]">
            <select v-model="selectedProductId" class="field">
              <option value="">Seleccionar producto</option>
              <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                {{ product.sku }} - {{ product.name }} (${{ Number(product.sale_price).toLocaleString('es-AR') }})
              </option>
            </select>
            <input v-model="selectedQty" type="number" min="1" class="field" />
            <button type="button" class="btn btn-secondary" @click="addItem">Agregar</button>
          </div>
        </div>
      </article>

      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Ticket actual</h2>
        <div v-if="items.length" :class="tableClass" class="mt-3">
          <table class="d-table">
            <thead>
              <tr>
                <th class="sticky-col">SKU</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Subtotal</th>
                <th>Accion</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in items" :key="`${item.product_id}-${index}`">
                <td class="sticky-col">{{ item.sku }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.cantidad }}</td>
                <td>$ {{ Number(item.cantidad * item.precio_unitario).toLocaleString('es-AR') }}</td>
                <td><button type="button" class="btn btn-danger !px-2 !py-1 text-xs" @click="removeItem(index)">Quitar</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="mt-3 rounded-xl border border-dashed p-6 text-center muted" style="border-color: rgb(var(--border));">
          No hay items en el ticket. Agrega productos para continuar.
        </div>

        <p class="mt-3 text-lg font-semibold">Total: $ {{ Number(total).toLocaleString('es-AR') }}</p>
        <button type="button" class="btn btn-primary w-full" @click="submitSale">Registrar venta</button>
      </article>
    </div>

    <article class="surface p-4">
      <h2 class="m-0 text-base font-semibold">Historial de ventas</h2>
      <div v-if="loading" :class="tableClass" class="mt-3">
        <div class="skeleton h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
        <div class="skeleton mt-2 h-8"></div>
      </div>
      <div v-else :class="tableClass" class="mt-3">
        <table class="d-table">
          <thead>
            <tr>
              <th class="sticky-col">ID</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Moneda</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!sales.length">
              <td colspan="6" class="py-8 text-center muted">Sin ventas registradas.</td>
            </tr>
            <tr v-for="sale in sales" :key="sale.id">
              <td class="sticky-col">#{{ sale.id }}</td>
              <td>{{ sale.client?.name || 'Consumidor Final' }}</td>
              <td>$ {{ Number(sale.total_amount).toLocaleString('es-AR') }}</td>
              <td>{{ sale.moneda_cobro }}</td>
              <td><span class="chip">{{ sale.status }}</span></td>
              <td>{{ new Date(sale.created_at).toLocaleString('es-AR') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</template>
