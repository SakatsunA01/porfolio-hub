<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Check, Search, ShoppingCart, X } from 'lucide-vue-next'
import { useDeliveryStore, type Product } from '../stores/delivery'
import AppButton from '../components/common/AppButton.vue'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

type Category = 'Populares' | 'Bebidas' | 'Postres'

interface CartLine {
  productId: number
  qty: number
  unitPrice: number
  customLabel?: string
}

interface ComboView {
  id: number
  name: string
  imageUrl: string
  includes: string[]
  price: number
}

interface ProductConfig {
  ingredients: string[]
  extras: Array<{ id: string; name: string; price: number }>
}

interface BackendIngredient {
  id: number
  name: string
}

interface BackendExtra {
  id: number
  name: string
  additional_price: number
}

interface BackendProductDetailResponse {
  product: {
    ingredients?: BackendIngredient[]
    extras?: BackendExtra[]
  }
}

interface BackendComboResponse {
  id: number
  name: string
  image_url: string | null
  base_price: number
  products?: Array<{ name: string }>
}

const store = useDeliveryStore()
const router = useRouter()
useOrdersRealtime()

const query = ref('')
const activeCategory = ref<Category>('Populares')
const isCartOpen = ref(false)
const addedProductId = ref<number | null>(null)
const cart = ref<CartLine[]>([])
const checkoutDone = ref(false)
const trackingOrderId = ref<number | null>(null)
const selectedProduct = ref<Product | null>(null)
const selectedCombo = ref<ComboView | null>(null)
const selectedConfigState = ref<ProductConfig | null>(null)
const detailLoading = ref(false)
const remoteCombos = ref<ComboView[]>([])
const apiBaseUrl = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'
const orderSyncMessage = ref('')

const customization = reactive({
  removedIngredients: [] as string[],
  selectedExtras: [] as string[],
})

const checkoutForm = reactive({
  customer: 'Cliente Demo',
  address: 'Av. Siempre Viva 742',
  paymentMethod: 'cash' as 'cash' | 'mercado_pago',
  cashReceived: 0,
})

const categories: Category[] = ['Populares', 'Bebidas', 'Postres']

const combosOfDay = computed<ComboView[]>(() => remoteCombos.value)

const categoryOf = (name: string): Category => {
  const value = name.toLowerCase()
  if (value.includes('pizza') || value.includes('hamburguesa')) return 'Populares'
  if (value.includes('bebida') || value.includes('agua') || value.includes('gaseosa')) return 'Bebidas'
  if (value.includes('postre') || value.includes('helado') || value.includes('torta') || value.includes('empanadas')) return 'Postres'
  return 'Populares'
}

const filteredProducts = computed(() => {
  const q = query.value.trim().toLowerCase()
  return store.activeProducts.filter((product) => {
    if (categoryOf(product.name) !== activeCategory.value) return false
    if (!q) return true
    return product.name.toLowerCase().includes(q)
  })
})

const cartCount = computed(() => cart.value.reduce((acc, line) => acc + line.qty, 0))

const cartView = computed(() =>
  cart.value
    .map((line) => {
      const product = store.getProduct(line.productId)
      if (!product) return null
      return {
        ...line,
        product,
        subtotal: line.unitPrice * line.qty,
      }
    })
    .filter(Boolean) as Array<{ productId: number; qty: number; unitPrice: number; customLabel?: string; product: Product; subtotal: number }>,
)

const total = computed(() => cartView.value.reduce((acc, line) => acc + line.subtotal, 0))

const selectedConfig = computed(() => {
  return selectedConfigState.value || { ingredients: [], extras: [] }
})

const selectedExtrasTotal = computed(() => {
  const config = selectedConfig.value
  if (!config) return 0
  return config.extras
    .filter((extra) => customization.selectedExtras.includes(extra.id))
    .reduce((acc, extra) => acc + extra.price, 0)
})

const detailTotal = computed(() => {
  if (!selectedProduct.value) return 0
  return selectedProduct.value.price + selectedExtrasTotal.value
})

const trackedOrder = computed(() => {
  if (trackingOrderId.value !== null) {
    return store.orders.find((order) => order.id === trackingOrderId.value) || null
  }
  const customer = checkoutForm.customer.trim().toLowerCase()
  if (!customer) return null
  return (
    [...store.orders]
      .sort((a, b) => b.createdAt - a.createdAt)
      .find((order) => order.customer.toLowerCase() === customer) || null
  )
})

const statusSteps = [
  { key: 'received', label: 'Recibido' },
  { key: 'preparing', label: 'En cocina' },
  { key: 'ready', label: 'Listo' },
  { key: 'onroute', label: 'En camino' },
  { key: 'delivered', label: 'Entregado' },
] as const

const statusIndexMap: Record<string, number> = {
  received: 0,
  preparing: 1,
  ready: 2,
  onroute: 3,
  delivered: 4,
  canceled: 0,
  rejected: 0,
}

const trackedStatusIndex = computed(() => {
  if (!trackedOrder.value) return -1
  return statusIndexMap[trackedOrder.value.status] ?? -1
})

const trackedExceptionLabel = computed(() => {
  if (!trackedOrder.value) return ''
  if (trackedOrder.value.status === 'canceled') return 'Pedido cancelado'
  if (trackedOrder.value.status === 'rejected') return 'Pedido rechazado'
  return ''
})

const initials = (name: string) =>
  name
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('')

const addToCart = (productId: number, unitPrice?: number, customLabel?: string) => {
  const finalPrice = unitPrice || store.getProduct(productId)?.price || 0
  const existing = cart.value.find((item) => item.productId === productId && item.unitPrice === finalPrice && item.customLabel === customLabel)
  if (existing) {
    existing.qty += 1
  } else {
    cart.value.push({ productId, qty: 1, unitPrice: finalPrice, customLabel })
  }
  addedProductId.value = productId
  window.setTimeout(() => {
    if (addedProductId.value === productId) addedProductId.value = null
  }, 800)
}

const buildApiUrl = (path: string) => `${apiBaseUrl.replace(/\/$/, '')}${path}`

const removeFromCart = (line: { productId: number; unitPrice: number; customLabel?: string }) => {
  const target = cart.value.find(
    (item) => item.productId === line.productId && item.unitPrice === line.unitPrice && item.customLabel === line.customLabel,
  )
  if (!target) return
  target.qty -= 1
  if (target.qty <= 0) {
    cart.value = cart.value.filter((item) => !(item.productId === target.productId && item.unitPrice === target.unitPrice && item.customLabel === target.customLabel))
  }
}

const openProductDetail = async (product: Product) => {
  selectedProduct.value = product
  selectedConfigState.value = { ingredients: [], extras: [] }
  customization.removedIngredients = []
  customization.selectedExtras = []
  detailLoading.value = true

  try {
    const response = await fetch(buildApiUrl(`/products/${product.id}`))
    if (!response.ok) throw new Error('No se pudo cargar el detalle')
    const payload = (await response.json()) as BackendProductDetailResponse
    const ingredients = (payload.product.ingredients || []).map((item) => item.name)
    const extras = (payload.product.extras || []).map((item) => ({
      id: String(item.id),
      name: item.name,
      price: Number(item.additional_price || 0),
    }))
    selectedConfigState.value = { ingredients, extras }
  } catch {
    selectedConfigState.value = { ingredients: [], extras: [] }
  } finally {
    detailLoading.value = false
  }
}

const hasProductOptions = (config: ProductConfig | null) => {
  if (!config) return false
  return config.ingredients.length > 0 || config.extras.length > 0
}

const quickAddProduct = async (product: Product) => {
  detailLoading.value = true
  try {
    const response = await fetch(buildApiUrl(`/products/${product.id}`))
    if (!response.ok) {
      addToCart(product.id)
      return
    }
    const payload = (await response.json()) as BackendProductDetailResponse
    const ingredients = (payload.product.ingredients || []).map((item) => item.name)
    const extras = (payload.product.extras || []).map((item) => ({
      id: String(item.id),
      name: item.name,
      price: Number(item.additional_price || 0),
    }))
    const config: ProductConfig = { ingredients, extras }

    if (!hasProductOptions(config)) {
      addToCart(product.id)
      return
    }

    selectedProduct.value = product
    selectedConfigState.value = config
    customization.removedIngredients = []
    customization.selectedExtras = []
  } catch {
    addToCart(product.id)
  } finally {
    detailLoading.value = false
  }
}

const toggleIngredient = (ingredient: string) => {
  if (customization.removedIngredients.includes(ingredient)) {
    customization.removedIngredients = customization.removedIngredients.filter((value) => value !== ingredient)
    return
  }
  customization.removedIngredients.push(ingredient)
}

const toggleExtra = (extraId: string) => {
  if (customization.selectedExtras.includes(extraId)) {
    customization.selectedExtras = customization.selectedExtras.filter((value) => value !== extraId)
    return
  }
  customization.selectedExtras.push(extraId)
}

const addCustomizedProduct = () => {
  if (!selectedProduct.value) return
  const config = selectedConfig.value
  const removed = customization.removedIngredients.length ? `Sin ${customization.removedIngredients.join(', ')}` : ''
  const extras = config
    ? config.extras
        .filter((extra) => customization.selectedExtras.includes(extra.id))
        .map((extra) => extra.name)
        .join(', ')
    : ''
  const label = [removed, extras ? `+ ${extras}` : ''].filter(Boolean).join(' | ')
  addToCart(selectedProduct.value.id, detailTotal.value, label || undefined)
  selectedProduct.value = null
}

const confirmOrder = () => {
  if (!cart.value.length) return
  const payload = {
    customer: checkoutForm.customer.trim(),
    address: checkoutForm.address.trim(),
    paymentMethod: checkoutForm.paymentMethod,
    cashReceived: checkoutForm.paymentMethod === 'cash' && checkoutForm.cashReceived > 0 ? checkoutForm.cashReceived : null,
    items: cart.value.map((line) => ({ productId: line.productId, qty: line.qty })),
  }
  const localTotal = total.value
  const localChange =
    payload.paymentMethod === 'cash' && payload.cashReceived !== null && payload.cashReceived > localTotal
      ? Number((payload.cashReceived - localTotal).toFixed(2))
      : 0
  const created = store.createOrder({
    ...payload,
    paymentStatus: payload.paymentMethod === 'mercado_pago' ? 'paid' : 'pending',
    changeAmount: localChange,
  })
  if (!created) return

  fetch(buildApiUrl('/orders'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      customer: payload.customer,
      address: payload.address,
      payment_method: payload.paymentMethod,
      cash_received: payload.cashReceived,
      items: payload.items.map((item) => ({
        product_id: item.productId,
        qty: item.qty,
      })),
    }),
  })
    .then(async (response) => {
      if (!response.ok) throw new Error('sync-error')
      const createdOrder = (await response.json()) as { id?: number }
      if (createdOrder?.id) {
        trackingOrderId.value = Number(createdOrder.id)
        await store.refreshAll()
      }
      orderSyncMessage.value = 'Pedido sincronizado con backend.'
      window.setTimeout(() => {
        if (orderSyncMessage.value === 'Pedido sincronizado con backend.') orderSyncMessage.value = ''
      }, 2400)
    })
    .catch(() => {
      const localOrder = store.sortedOrders[0]
      if (localOrder) trackingOrderId.value = localOrder.id
      orderSyncMessage.value = 'Pedido guardado en modo local demo.'
      window.setTimeout(() => {
        if (orderSyncMessage.value === 'Pedido guardado en modo local demo.') orderSyncMessage.value = ''
      }, 2400)
    })

  cart.value = []
  isCartOpen.value = false
  checkoutDone.value = true
}

const restart = () => {
  checkoutDone.value = false
  query.value = ''
  activeCategory.value = 'Populares'
}

const backToLogin = async () => {
  await store.logout()
  router.push('/login')
}

onMounted(async () => {
  try {
    const response = await fetch(buildApiUrl('/combos'))
    if (!response.ok) throw new Error('No combos')
    const payload = (await response.json()) as BackendComboResponse[]
    remoteCombos.value = payload.map((combo) => ({
      id: combo.id,
      name: combo.name,
      imageUrl:
        combo.image_url ||
        'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=80',
      includes: (combo.products || []).map((item) => item.name),
      price: Number(combo.base_price || 0),
    }))
  } catch {
    remoteCombos.value = []
  }
})
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
    <header class="mb-4 flex items-center justify-between rounded-xl border border-slate-100 bg-white p-3">
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 active:scale-95"
        @click="backToLogin"
      >
        <ArrowLeft class="h-4 w-4" />
        Atras
      </button>
      <p class="text-sm font-semibold text-slate-800">Dunamis Store</p>
      <span class="w-16"></span>
    </header>

    <Transition name="fade" mode="out-in">
      <div v-if="!checkoutDone" key="shop" class="space-y-5">
        <section v-if="trackedOrder" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de tu pedido</p>
            <p class="text-xs font-semibold text-slate-700">#ORD-{{ String(trackedOrder.id).padStart(4, '0') }}</p>
          </div>
          <div class="mt-2 grid grid-cols-5 gap-1">
            <div v-for="(step, index) in statusSteps" :key="step.key" class="text-center">
              <div
                class="mx-auto h-2.5 w-full rounded-full"
                :class="index <= trackedStatusIndex ? 'bg-emerald-500' : 'bg-slate-200'"
              ></div>
              <p class="mt-1 text-[10px]" :class="index <= trackedStatusIndex ? 'text-emerald-700' : 'text-slate-400'">
                {{ step.label }}
              </p>
            </div>
          </div>
          <p v-if="trackedExceptionLabel" class="mt-2 text-xs font-semibold text-rose-600">{{ trackedExceptionLabel }}</p>
        </section>
        <div
          v-if="orderSyncMessage"
          class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600"
        >
          {{ orderSyncMessage }}
        </div>

        <header class="sticky top-0 z-10 rounded-xl border border-slate-100 bg-white/95 p-3 backdrop-blur">
          <div class="flex items-center gap-2">
            <div class="relative flex-1">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="query"
                type="text"
                placeholder="Buscar productos"
                class="w-full rounded-full border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700"
              />
            </div>
            <button
              type="button"
              class="relative rounded-full border border-slate-200 bg-white p-2.5 transition hover:border-emerald-300 hover:text-emerald-600 active:scale-95"
              @click="isCartOpen = true"
            >
              <ShoppingCart class="h-5 w-5 text-slate-500" />
              <span class="absolute -right-1 -top-1 rounded-full bg-emerald-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                {{ cartCount }}
              </span>
            </button>
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <button
              v-for="category in categories"
              :key="category"
              type="button"
              class="rounded-full border px-3 py-1 text-xs font-semibold transition active:scale-95"
              :class="activeCategory === category ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
              @click="activeCategory = category"
            >
              {{ category }}
            </button>
          </div>
        </header>

        <section class="space-y-2">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Combos del Dia</h3>
          <div v-if="combosOfDay.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article
              v-for="combo in combosOfDay"
              :key="combo.id"
              class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
              <img :src="combo.imageUrl" :alt="combo.name" class="h-40 w-full object-cover" />
              <div class="p-3">
                <p class="font-semibold text-slate-900">{{ combo.name }}</p>
                <p class="mt-1 text-sm font-bold text-emerald-600">${{ combo.price }}</p>
                <AppButton variant="soft" class="mt-2" @click="selectedCombo = combo">Ver combo</AppButton>
              </div>
            </article>
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
            No hay combos cargados en backend.
          </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="product in filteredProducts"
            :key="product.id"
            class="group relative rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
          >
            <button type="button" class="w-full text-left" @click="openProductDetail(product)">
              <div class="aspect-square rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 p-4">
                <div class="grid h-full place-items-center rounded-lg border border-slate-200/80 bg-white">
                  <span class="text-3xl font-bold text-slate-300">{{ initials(product.name) }}</span>
                </div>
              </div>

              <div class="mt-3">
                <h3 class="text-sm font-semibold text-slate-900">{{ product.name }}</h3>
                <p class="mt-1 text-xs text-slate-500">Seleccion premium con preparacion optimizada.</p>
                <p class="mt-2 text-base font-bold text-emerald-600">${{ product.price }}</p>
              </div>
            </button>

            <button
              type="button"
              class="absolute bottom-3 right-3 grid h-9 w-9 place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 transition hover:bg-emerald-600 active:scale-95"
              @click.stop="quickAddProduct(product)"
            >
              <Transition name="pop" mode="out-in">
                <Check v-if="addedProductId === product.id" key="ok" class="h-4 w-4" />
                <span v-else key="plus" class="text-lg leading-none">+</span>
              </Transition>
            </button>
          </article>
        </div>
      </div>

      <div v-else key="done" class="grid min-h-[420px] place-items-center rounded-2xl border border-emerald-100 bg-emerald-50/40 p-8 text-center">
        <div>
          <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
            <Check class="h-7 w-7" />
          </span>
          <h3 class="mt-4 text-2xl font-semibold text-slate-900">Pedido en Camino</h3>
          <p class="mt-2 text-sm text-slate-600">Tu compra fue confirmada y el equipo ya la esta preparando.</p>
          <div v-if="trackedOrder" class="mx-auto mt-4 w-full max-w-md rounded-xl border border-slate-200 bg-white p-3 text-left">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seguimiento</p>
            <div class="mt-2 grid grid-cols-5 gap-1">
              <div v-for="(step, index) in statusSteps" :key="`done-${step.key}`" class="text-center">
                <div class="mx-auto h-2.5 w-full rounded-full" :class="index <= trackedStatusIndex ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                <p class="mt-1 text-[10px]" :class="index <= trackedStatusIndex ? 'text-emerald-700' : 'text-slate-400'">{{ step.label }}</p>
              </div>
            </div>
            <p v-if="trackedExceptionLabel" class="mt-2 text-xs font-semibold text-rose-600">{{ trackedExceptionLabel }}</p>
          </div>
          <AppButton variant="soft" class="mt-5 rounded-full" @click="restart">Volver a la tienda</AppButton>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="isCartOpen" class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm" @click="isCartOpen = false"></div>
    </Transition>

    <Transition name="drawer">
      <aside v-if="isCartOpen" class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md flex-col border-l border-slate-200 bg-white p-4 shadow-2xl">
        <header class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900">Tu carrito</h3>
          <button
            type="button"
            class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 active:scale-95"
            @click="isCartOpen = false"
          >
            <X class="h-4 w-4" />
          </button>
        </header>

        <div class="mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
          <article
            v-for="line in cartView"
            :key="`${line.productId}-${line.unitPrice}-${line.customLabel}`"
            class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3"
          >
            <div class="grid h-12 w-12 place-items-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500">
              {{ initials(line.product.name) }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">{{ line.product.name }}</p>
              <p v-if="line.customLabel" class="truncate text-[11px] text-slate-500">{{ line.customLabel }}</p>
              <p class="text-xs text-slate-500">Subtotal: ${{ line.subtotal }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 text-sm text-slate-600 active:scale-95"
                @click="removeFromCart(line)"
              >
                -
              </button>
              <span class="w-5 text-center text-sm font-semibold text-slate-700">{{ line.qty }}</span>
              <button
                type="button"
                class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 text-sm text-slate-600 active:scale-95"
                @click="addToCart(line.productId, line.unitPrice, line.customLabel)"
              >
                +
              </button>
            </div>
          </article>

          <div v-if="!cartView.length" class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
            Tu carrito esta vacio.
          </div>
        </div>

        <footer class="mt-4 space-y-3 border-t border-slate-200 pt-4">
          <div class="space-y-2">
            <input
              v-model="checkoutForm.customer"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
              placeholder="Nombre"
            />
            <input
              v-model="checkoutForm.address"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
              placeholder="Direccion de entrega"
            />
            <select
              v-model="checkoutForm.paymentMethod"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
            >
              <option value="cash">Pagar en efectivo</option>
              <option value="mercado_pago">Pagar con Mercado Pago</option>
            </select>
            <input
              v-if="checkoutForm.paymentMethod === 'cash'"
              v-model.number="checkoutForm.cashReceived"
              type="number"
              min="0"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
              placeholder="Con cuanto pagas (opcional)"
            />
          </div>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Total</span>
            <span class="text-lg font-bold text-slate-900">${{ total }}</span>
          </div>

          <AppButton variant="primary" :full="true" py="py-3" :disabled="!cartView.length" @click="confirmOrder">
            Confirmar Pedido
          </AppButton>
        </footer>
      </aside>
    </Transition>

    <Transition name="fade">
      <div v-if="selectedProduct" class="fixed inset-0 z-50 bg-white md:bg-slate-900/35 md:backdrop-blur-sm">
        <div class="h-full overflow-y-auto md:grid md:place-items-center md:p-6">
          <article class="h-full bg-white md:h-auto md:w-full md:max-w-2xl md:rounded-2xl">
            <div class="relative">
              <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-50 p-6">
                <div class="grid h-full place-items-center rounded-xl border border-slate-200 bg-white">
                  <span class="text-5xl font-bold text-slate-300">{{ initials(selectedProduct.name) }}</span>
                </div>
              </div>
              <button
                type="button"
                class="absolute right-3 top-3 rounded-full bg-white p-2 text-slate-600 shadow-md active:scale-95"
                @click="selectedProduct = null"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div class="space-y-5 p-4 md:p-6">
              <div>
                <h3 class="text-xl font-semibold text-slate-900">Arma tu {{ selectedProduct.name }}</h3>
                <p class="text-sm text-slate-500">Elegi agregados y quita lo que no te guste.</p>
              </div>

              <section>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Ingredientes incluidos</h4>
                <p v-if="detailLoading" class="mt-2 text-xs text-slate-400">Cargando detalle...</p>
                <div class="mt-2 space-y-2">
                  <label
                    v-for="ingredient in selectedConfig?.ingredients || []"
                    :key="ingredient"
                    class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm"
                  >
                    <span class="text-slate-700">{{ ingredient }}</span>
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-emerald-500"
                      :checked="customization.removedIngredients.includes(ingredient)"
                      @change="toggleIngredient(ingredient)"
                    />
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-400">Marca para quitar (ej: sin cebolla).</p>
              </section>

              <section>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Extras disponibles</h4>
                <div class="mt-2 space-y-2">
                  <label
                    v-for="extra in selectedConfig?.extras || []"
                    :key="extra.id"
                    class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm"
                  >
                    <span class="text-slate-700">{{ extra.name }}</span>
                    <span class="flex items-center gap-3">
                      <span class="font-semibold text-emerald-600">+${{ extra.price }}</span>
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-emerald-500"
                        :checked="customization.selectedExtras.includes(extra.id)"
                        @change="toggleExtra(extra.id)"
                      />
                    </span>
                  </label>
                </div>
              </section>
            </div>

            <footer class="sticky bottom-0 border-t border-slate-200 bg-white p-4 md:rounded-b-2xl">
              <div class="mb-2 flex items-center justify-between text-sm">
                <span class="text-slate-500">Total personalizado</span>
                <span class="text-lg font-bold text-slate-900">${{ detailTotal }}</span>
              </div>
              <AppButton variant="primary" :full="true" py="py-3" @click="addCustomizedProduct">
                Confirmar y agregar al carrito | ${{ detailTotal }}
              </AppButton>
            </footer>
          </article>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="selectedCombo" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/35 p-4 backdrop-blur-sm">
        <article class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <img :src="selectedCombo.imageUrl" :alt="selectedCombo.name" class="h-56 w-full object-cover" />
          <div class="space-y-3 p-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">{{ selectedCombo.name }}</h3>
              <span class="text-base font-bold text-emerald-600">${{ selectedCombo.price }}</span>
            </div>
            <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
              <li v-for="item in selectedCombo.includes" :key="item">{{ item }}</li>
            </ul>
            <AppButton variant="soft" :full="true" @click="selectedCombo = null">Cerrar</AppButton>
          </div>
        </article>
      </div>
    </Transition>
  </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.drawer-enter-active,
.drawer-leave-active {
  transition: transform 220ms ease;
}

.drawer-enter-from,
.drawer-leave-to {
  transform: translateX(100%);
}

.pop-enter-active,
.pop-leave-active {
  transition: all 160ms ease;
}

.pop-enter-from,
.pop-leave-to {
  opacity: 0;
  transform: scale(0.8);
}
</style>
