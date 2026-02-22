<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AppButton from '../components/common/AppButton.vue'
import { useDeliveryStore, type IngredientItem, type Order } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

const store = useDeliveryStore()
useOrdersRealtime()

const activeTab = ref<'home' | 'orders' | 'catalog' | 'team' | 'roles' | 'combos' | 'categories' | 'cashbox' | 'customers' | 'audit'>('home')
const inventoryView = ref<'list' | 'new'>('list')
const statusBanner = ref('')
const knownOrderIds = ref<number[]>([])
const orderEditorOpen = ref(false)
const orderFlowFilter = ref<'all' | 'waiting_driver' | 'delayed' | 'in_kitchen'>('all')
const productFormTab = ref<'basic' | 'recipe' | 'ops' | 'media'>('basic')

const tabLabels: Record<typeof activeTab.value, string> = {
  home: 'Centro de acciones',
  orders: 'Centro de Control Operativo',
  catalog: 'Inventario y productos',
  categories: 'Categorias',
  combos: 'Ofertas y combos',
  cashbox: 'Caja del dia',
  customers: 'Clientes (CRM Light)',
  audit: 'Historial de auditoria',
  team: 'Equipo',
  roles: 'Roles y permisos',
}

const headerTitle = computed(() => (activeTab.value === 'home' ? 'Acciones agrupadas' : 'Panel Administrativo'))
const headerSubtitle = computed(() =>
  activeTab.value === 'home'
    ? 'Navega por tipo de accion desde este modulo.'
    : tabLabels[activeTab.value],
)

const groupedActions = [
  {
    group: 'Catalogo',
    description: 'Productos, inventario, categorias y combos.',
    actions: [
      { key: 'catalog', title: 'Productos e Inventario', hint: 'Crear, editar stock y activar productos.' },
      { key: 'categories', title: 'Categorias', hint: 'Ordenar menu y controlar alertas de falta.' },
      { key: 'combos', title: 'Combos y Ofertas', hint: 'Armar paquetes y promociones.' },
    ],
  },
  {
    group: 'Operacion',
    description: 'Monitoreo y cobros del dia.',
    actions: [
      { key: 'orders', title: 'Centro de Control Operativo', hint: 'Supervisar, editar y resolver incidencias.' },
      { key: 'cashbox', title: 'Caja', hint: 'Cobros por efectivo y Mercado Pago.' },
    ],
  },
  {
    group: 'Gestion',
    description: 'Usuarios y permisos.',
    actions: [
      { key: 'team', title: 'Equipo', hint: 'Crear y activar empleados.' },
      { key: 'roles', title: 'Roles', hint: 'Definir perfiles y accesos.' },
      { key: 'customers', title: 'Clientes (CRM)', hint: 'Top clientes, direccion y bloqueo rapido.' },
      { key: 'audit', title: 'Historial de auditoria', hint: 'Trazabilidad de cambios de negocio.' },
    ],
  },
] as const

const orderEditForm = reactive({
  id: 0,
  customer: '',
  address: '',
  status: 'received' as Order['status'],
  paymentMethod: 'cash' as Order['paymentMethod'],
  paymentStatus: 'pending' as Order['paymentStatus'],
  cashReceived: null as number | null,
  employeeId: '' as number | '',
  driverId: '' as number | '',
  etaMin: 25,
})

const productForm = reactive({
  name: '',
  price: 0,
  prepMin: 15,
  category: '',
  stockQuantity: 0,
  minStockQuantity: 0,
})
const selectedIngredientIds = ref<number[]>([])
const productExtras = ref<Array<{ name: string; additionalPrice: number }>>([])
const extraDraft = reactive({
  name: '',
  additionalPrice: 0,
})
const ingredientSearch = ref('')
const productImageFile = ref<File | null>(null)
const productImagePreview = ref<string>('')
const productCategoryFilter = ref('all')
const productStockFilter = ref<'all' | 'ok' | 'low' | 'out'>('all')
const selectedProductIds = ref<number[]>([])
const bulkPriceForm = reactive({
  mode: 'percentage' as 'percentage' | 'fixed_delta',
  value: 10,
  roundTo: 1 as 1 | 5 | 10 | 50 | 100,
  category: '',
  applyOn: 'category' as 'category' | 'selection',
})

const ingredientForm = reactive({
  name: '',
  additionalPrice: 0,
  stockQuantity: 0,
})

const roleForm = reactive({
  name: '',
  label: '',
})

const userForm = reactive({
  name: '',
  email: '',
  password: 'demo1234',
  roleId: 0,
})

const comboForm = reactive({
  name: '',
  description: '',
  basePrice: 0,
  imageUrl: '',
})
const comboImageFile = ref<File | null>(null)
const comboImagePreview = ref('')
const comboItems = ref<Array<{ productId: number; quantity: number }>>([])
const comboProductId = ref(0)
const comboQuantity = ref(1)
const bundleForm = reactive({
  name: '',
  description: '',
  pricingMode: 'fixed_price' as 'fixed_price' | 'discount_percentage',
  fixedPrice: 0,
  discountPercentage: 0,
})
const bundleItems = ref<Array<{ productId: number; quantity: number }>>([])
const bundleProductId = ref(0)
const bundleQuantity = ref(1)
const bundleImageFile = ref<File | null>(null)
const bundleImagePreview = ref('')

const activeOrders = computed(() => store.orders.filter((order) => !['delivered', 'canceled', 'rejected'].includes(order.status)))
const preparingOrders = computed(() => store.orders.filter((order) => order.status === 'preparing'))
const pendingShippingOrders = computed(() => store.orders.filter((order) => order.status === 'ready'))
const boardOrders = computed(() => {
  const base = [...activeOrders.value].sort((a, b) => b.createdAt - a.createdAt)
  if (orderFlowFilter.value === 'waiting_driver') {
    return base.filter((order) => order.status === 'ready')
  }
  if (orderFlowFilter.value === 'in_kitchen') {
    return base.filter((order) => ['received', 'preparing'].includes(order.status))
  }
  if (orderFlowFilter.value === 'delayed') {
    return base.filter((order) => elapsedMinutes(order.createdAt) >= 15)
  }
  return base
})
const cashOrders = computed(() => [...store.orders].sort((a, b) => b.createdAt - a.createdAt))
const cashboxSummary = computed(() => {
  const total = cashOrders.value.reduce((acc, order) => acc + (order.total || 0), 0)
  const cash = cashOrders.value
    .filter((order) => order.paymentMethod === 'cash' && order.paymentStatus === 'paid')
    .reduce((acc, order) => acc + (order.total || 0), 0)
  const mp = cashOrders.value
    .filter((order) => order.paymentMethod === 'mercado_pago' && order.paymentStatus === 'paid')
    .reduce((acc, order) => acc + (order.total || 0), 0)
  const pending = cashOrders.value
    .filter((order) => order.paymentStatus === 'pending')
    .reduce((acc, order) => acc + (order.total || 0), 0)

  return { total, cash, mp, pending }
})
const productCategories = computed(() => {
  const categories = new Set(store.products.map((product) => (product.category || '').trim()).filter(Boolean))
  return ['all', ...Array.from(categories)]
})
const categorySummary = computed(() => {
  const acc = new Map<string, { name: string; items: number; low: number; out: number }>()
  for (const product of store.products) {
    const key = (product.category || 'Sin categoria').trim() || 'Sin categoria'
    const entry = acc.get(key) || { name: key, items: 0, low: 0, out: 0 }
    entry.items += 1
    if (product.stockQuantity <= 0) {
      entry.out += 1
    } else if (product.stockQuantity <= product.minStockQuantity) {
      entry.low += 1
    }
    acc.set(key, entry)
  }
  return Array.from(acc.values()).sort((a, b) => a.name.localeCompare(b.name))
})
const topCustomer = computed(() => store.customerInsights[0] || null)
const selectedProductsCount = computed(() => selectedProductIds.value.length)
const filteredIngredients = computed(() => {
  const query = ingredientSearch.value.trim().toLowerCase()
  if (!query) return store.ingredients
  return store.ingredients.filter((ingredient) => ingredient.name.toLowerCase().includes(query))
})
const lowIngredients = computed(() => store.ingredients.filter((ingredient) => ingredient.active && Number(ingredient.stockQuantity || 0) <= 5))
const lowIngredientsCount = computed(() => lowIngredients.value.length)
const todaySales = computed(() => {
  const now = new Date()
  return store.orders
    .filter((order) => {
      const d = new Date(order.createdAt)
      return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()
    })
    .reduce((acc, order) => acc + (order.total || 0), 0)
})
const yesterdaySales = computed(() => {
  const y = new Date()
  y.setDate(y.getDate() - 1)
  return store.orders
    .filter((order) => {
      const d = new Date(order.createdAt)
      return d.getFullYear() === y.getFullYear() && d.getMonth() === y.getMonth() && d.getDate() === y.getDate()
    })
    .reduce((acc, order) => acc + (order.total || 0), 0)
})
const salesBarMax = computed(() => Math.max(todaySales.value, yesterdaySales.value, 1))
const driverPendingCash = computed(() => {
  return store.activeDrivers
    .map((driver) => {
      const pending = store.orders
        .filter((order) => order.driverId === driver.id && order.paymentMethod === 'cash' && order.paymentStatus === 'pending')
        .reduce((acc, order) => acc + (order.total || 0), 0)
      return {
        id: driver.id,
        name: driver.name,
        pending,
      }
    })
    .filter((row) => row.pending > 0)
})
const pricePreviewWithExtras = computed(() => {
  const extras = productExtras.value.reduce((acc, extra) => acc + (extra.additionalPrice || 0), 0)
  return Number(productForm.price || 0) + extras
})

const filteredProducts = computed(() => {
  return store.products.filter((product) => {
    const byCategory = productCategoryFilter.value === 'all' || (product.category || 'Sin categoria') === productCategoryFilter.value
    const stock = Number(product.stockQuantity || 0)
    const minStock = Number(product.minStockQuantity || 0)
    const status = stock <= 0 ? 'out' : stock <= minStock ? 'low' : 'ok'
    const byStock = productStockFilter.value === 'all' || productStockFilter.value === status
    return byCategory && byStock
  })
})

const elapsedText = (createdAt: number) => {
  const minutes = Math.max(1, Math.round((Date.now() - createdAt) / 60000))
  return `Hace ${minutes} min`
}

const orderCode = (orderId: number) => `#ORD-${String(orderId).padStart(4, '0')}`
const orderLines = (order: Order) =>
  order.items
    .map((item) => `${item.qty}x ${item.name || store.getProduct(item.productId)?.name || 'Producto'}`)
    .join(' | ')

const elapsedMinutes = (createdAt: number) => Math.max(1, Math.round((Date.now() - createdAt) / 60000))

const kdsCardClass = (createdAt: number) => {
  const minutes = elapsedMinutes(createdAt)
  if (minutes >= 20) return 'border-rose-400 bg-rose-100 kds-critical-hard'
  if (minutes >= 15) return 'border-rose-300 bg-rose-50 kds-critical'
  if (minutes >= 8) return 'border-amber-300 bg-amber-50'
  return 'border-slate-200 bg-white'
}

const itemRemovedIngredients = (item: Order['items'][number]) => {
  return (item.excludedIngredientIds || [])
    .map((id) => store.getIngredient(id)?.name || `Ingrediente #${id}`)
    .filter(Boolean)
}

const itemExtras = (item: Order['items'][number]) => item.extras || []

const orderPillClass = (status: Order['status']) => {
  if (status === 'preparing') return 'bg-amber-100 text-amber-700'
  if (status === 'ready') return 'bg-emerald-100 text-emerald-700'
  if (status === 'onroute') return 'bg-sky-100 text-sky-700'
  if (status === 'canceled') return 'bg-rose-100 text-rose-700'
  if (status === 'rejected') return 'bg-slate-200 text-slate-700'
  return 'bg-slate-100 text-slate-700'
}

const orderPillLabel = (status: Order['status']) => {
  if (status === 'received') return 'Pendiente'
  if (status === 'preparing') return 'En cocina'
  if (status === 'ready') return 'Listo'
  if (status === 'onroute') return 'En envio'
  if (status === 'canceled') return 'Cancelado'
  if (status === 'rejected') return 'Rechazado'
  return 'Entregado'
}

const ensureShiftAssignees = () => {
  const firstEmployee = store.activeEmployees[0]
  const firstDriver = store.activeDrivers[0]
  if (!store.shiftEmployeeId && firstEmployee) store.shiftEmployeeId = firstEmployee.id
  if (!store.shiftDriverId && firstDriver) store.shiftDriverId = firstDriver.id
}

const acceptOrder = async (orderId: number) => {
  ensureShiftAssignees()
  if (!store.shiftEmployeeId) return
  await store.startPreparing(orderId, store.shiftEmployeeId)
}

const completeOrder = async (orderId: number) => {
  await store.markReady(orderId)
}

const assignDriver = async (orderId: number) => {
  ensureShiftAssignees()
  if (!store.shiftDriverId) return
  await store.assignDriver(orderId, store.shiftDriverId)
  await store.leaveForDelivery(orderId, store.shiftDriverId)
  statusBanner.value = `Pedido ${orderCode(orderId)} despachado al repartidor.`
}

const paymentMethodLabel = (value: 'cash' | 'mercado_pago') => (value === 'mercado_pago' ? 'Mercado Pago' : 'Efectivo')
const paymentMethodClass = (value: 'cash' | 'mercado_pago') =>
  value === 'mercado_pago' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'
const paymentStatusLabel = (value: 'pending' | 'paid' | 'refunded') => {
  if (value === 'paid') return 'Cobrado'
  if (value === 'refunded') return 'Reintegrado'
  return 'Pendiente'
}
const paymentStatusClass = (value: 'pending' | 'paid' | 'refunded') => {
  if (value === 'paid') return 'bg-emerald-100 text-emerald-700'
  if (value === 'refunded') return 'bg-amber-100 text-amber-700'
  return 'bg-rose-100 text-rose-700'
}

const markOrderPaid = async (orderId: number) => {
  await store.updateOrderPayment(orderId, 'paid')
  statusBanner.value = `Pedido ${orderCode(orderId)} marcado como cobrado.`
}

const toggleProductSelection = (productId: number) => {
  if (selectedProductIds.value.includes(productId)) {
    selectedProductIds.value = selectedProductIds.value.filter((id) => id !== productId)
    return
  }
  selectedProductIds.value.push(productId)
}

const runBulkPriceUpdate = async () => {
  const category = bulkPriceForm.applyOn === 'category' ? bulkPriceForm.category : undefined
  const productIds = bulkPriceForm.applyOn === 'selection' ? selectedProductIds.value : undefined

  if (!category && (!productIds || !productIds.length)) {
    statusBanner.value = 'Selecciona una categoria o productos para accion masiva.'
    return
  }

  await store.bulkUpdateProductPrices({
    category,
    productIds,
    mode: bulkPriceForm.mode,
    value: bulkPriceForm.value,
    roundTo: bulkPriceForm.roundTo,
  })
  statusBanner.value = 'Accion masiva aplicada en catalogo.'
}

const toggleClientBlock = async (customerKey: string, customerName: string, blocked: boolean) => {
  await store.setCustomerBlocked({
    customerKey,
    customerName,
    blocked: !blocked,
  })
  statusBanner.value = !blocked ? 'Cliente bloqueado.' : 'Cliente desbloqueado.'
}

const auditActionLabel = (action: string) => {
  if (action === 'product.bulk_price_update') return 'Accion masiva de precios'
  if (action === 'product.created') return 'Producto creado'
  if (action === 'product.updated') return 'Producto actualizado'
  if (action === 'product.deleted') return 'Producto eliminado'
  if (action === 'user.created') return 'Usuario creado'
  if (action === 'user.updated') return 'Usuario actualizado'
  if (action === 'user.deleted') return 'Usuario eliminado'
  if (action === 'order.created') return 'Pedido creado'
  if (action === 'order.updated') return 'Pedido editado'
  if (action === 'order.status_updated') return 'Estado de pedido'
  if (action === 'order.payment_updated') return 'Pago de pedido'
  return action
}

const normalizeNullableId = (value: number | '') => (value === '' ? null : Number(value))

const openOrderEditor = (order: Order) => {
  orderEditForm.id = order.id
  orderEditForm.customer = order.customer
  orderEditForm.address = order.address
  orderEditForm.status = order.status
  orderEditForm.paymentMethod = order.paymentMethod
  orderEditForm.paymentStatus = order.paymentStatus
  orderEditForm.cashReceived = order.cashReceived
  orderEditForm.employeeId = order.employeeId ?? ''
  orderEditForm.driverId = order.driverId ?? ''
  orderEditForm.etaMin = order.etaMin
  orderEditorOpen.value = true
}

const closeOrderEditor = () => {
  orderEditorOpen.value = false
}

const saveOrderEditor = async () => {
  if (!orderEditForm.id) return

  await store.updateOrderDetails({
    id: orderEditForm.id,
    customer: orderEditForm.customer.trim(),
    address: orderEditForm.address.trim(),
    status: orderEditForm.status,
    paymentMethod: orderEditForm.paymentMethod,
    paymentStatus: orderEditForm.paymentStatus,
    cashReceived: orderEditForm.paymentMethod === 'cash' ? orderEditForm.cashReceived : null,
    employeeId: normalizeNullableId(orderEditForm.employeeId),
    driverId: normalizeNullableId(orderEditForm.driverId),
    etaMin: orderEditForm.etaMin,
  })

  statusBanner.value = `Pedido ${orderCode(orderEditForm.id)} actualizado.`
  closeOrderEditor()
}

const submitProduct = async () => {
  const ok = await store.addProduct({
    ...productForm,
    imageFile: productImageFile.value,
    ingredients: selectedIngredientIds.value.map((ingredientId) => ({
      ingredientId,
      isDefault: true,
      isRemovable: true,
      additionalPrice: 0,
    })),
    extras: productExtras.value.map((extra) => ({
      name: extra.name,
      additionalPrice: extra.additionalPrice,
    })),
  })
  if (!ok) return
  productForm.name = ''
  productForm.price = 0
  productForm.prepMin = 15
  productForm.category = ''
  productForm.stockQuantity = 0
  productForm.minStockQuantity = 0
  selectedIngredientIds.value = []
  productExtras.value = []
  extraDraft.name = ''
  extraDraft.additionalPrice = 0
  productImageFile.value = null
  productImagePreview.value = ''
  inventoryView.value = 'list'
}

const submitIngredient = async () => {
  if (!ingredientForm.name.trim()) return
  await store.createIngredient({
    name: ingredientForm.name.trim(),
    additionalPrice: ingredientForm.additionalPrice,
    stockQuantity: ingredientForm.stockQuantity,
  })
  ingredientForm.name = ''
  ingredientForm.additionalPrice = 0
  ingredientForm.stockQuantity = 0
  statusBanner.value = 'Ingrediente creado.'
}

const createInlineIngredient = async () => {
  const name = ingredientSearch.value.trim()
  if (!name) return
  await store.createIngredient({
    name,
    additionalPrice: 0,
    stockQuantity: 0,
  })
  const created = store.ingredients.find((ingredient) => ingredient.name.toLowerCase() === name.toLowerCase())
  if (created && !selectedIngredientIds.value.includes(created.id)) {
    selectedIngredientIds.value.push(created.id)
  }
  ingredientSearch.value = ''
  statusBanner.value = `Ingrediente ${name} creado y agregado a receta.`
}

const deactivateIngredientGlobal = async (ingredientId: number) => {
  await store.deactivateIngredientGlobally(ingredientId)
  statusBanner.value = 'Ingrediente desactivado globalmente. Se apagaron productos/combo relacionados.'
}

const addExtraDraft = () => {
  if (!extraDraft.name.trim()) return
  productExtras.value.push({
    name: extraDraft.name.trim(),
    additionalPrice: Math.max(0, extraDraft.additionalPrice),
  })
  extraDraft.name = ''
  extraDraft.additionalPrice = 0
}

const removeExtra = (index: number) => {
  productExtras.value.splice(index, 1)
}

const setProductImage = (file: File | null) => {
  productImageFile.value = file
  if (!file) {
    productImagePreview.value = ''
    return
  }
  productImagePreview.value = URL.createObjectURL(file)
}

const handleImageInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  setProductImage(target.files?.[0] || null)
}

const handleDropImage = (event: DragEvent) => {
  event.preventDefault()
  setProductImage(event.dataTransfer?.files?.[0] || null)
}

const ingredientLabel = (ingredient: IngredientItem) => `${ingredient.name}${ingredient.additionalPrice > 0 ? ` (+$${ingredient.additionalPrice})` : ''}`

const productStockClass = (stockQuantity: number, minStockQuantity: number) => {
  if (stockQuantity <= 0) return 'bg-rose-100 text-rose-700'
  if (stockQuantity <= minStockQuantity) return 'bg-amber-100 text-amber-700'
  return 'bg-emerald-100 text-emerald-700'
}

const productStockLabel = (stockQuantity: number, minStockQuantity: number) => {
  if (stockQuantity <= 0) return 'Sin stock'
  if (stockQuantity <= minStockQuantity) return 'Stock bajo'
  return 'Stock OK'
}

const stockValueClass = (stockQuantity: number, minStockQuantity: number) => {
  if (stockQuantity <= 0) return 'font-bold text-rose-700'
  if (stockQuantity <= minStockQuantity) return 'font-semibold text-amber-700'
  return 'text-emerald-700'
}

const duplicateProductCard = async (productId: number) => {
  await store.duplicateProduct(productId)
  statusBanner.value = 'Producto duplicado correctamente.'
}

const bundleEstimatedSaving = computed(() => {
  const listTotal = bundleItems.value.reduce((acc, item) => acc + ((store.getProduct(item.productId)?.price || 0) * item.quantity), 0)
  const offerTotal =
    bundleForm.pricingMode === 'fixed_price'
      ? bundleForm.fixedPrice
      : listTotal * (1 - (bundleForm.discountPercentage / 100))

  return Math.max(0, listTotal - offerTotal)
})

const submitRole = async () => {
  if (!roleForm.name.trim() || !roleForm.label.trim()) return
  await store.createRole({ name: roleForm.name.trim().toLowerCase(), label: roleForm.label.trim() })
  roleForm.name = ''
  roleForm.label = ''
  statusBanner.value = 'Rol creado.'
}

const submitUser = async () => {
  if (!userForm.name.trim() || !userForm.email.trim() || !userForm.roleId) return
  await store.createUser({
    name: userForm.name.trim(),
    email: userForm.email.trim(),
    password: userForm.password.trim(),
    roleId: userForm.roleId,
    isActive: true,
  })

  userForm.name = ''
  userForm.email = ''
  userForm.password = 'demo1234'
  userForm.roleId = 0
  statusBanner.value = 'Usuario creado.'
}

const toggleUserState = async (userId: number, isActive: boolean) => {
  await store.updateUser({ id: userId, isActive: !isActive })
}

const addComboItem = () => {
  if (!comboProductId.value) return
  const existing = comboItems.value.find((item) => item.productId === comboProductId.value)
  if (existing) {
    existing.quantity += Math.max(1, comboQuantity.value)
  } else {
    comboItems.value.push({
      productId: comboProductId.value,
      quantity: Math.max(1, comboQuantity.value),
    })
  }
  comboProductId.value = 0
  comboQuantity.value = 1
}

const removeComboItem = (productId: number) => {
  comboItems.value = comboItems.value.filter((item) => item.productId !== productId)
}

const submitCombo = async () => {
  if (!comboForm.name.trim() || !comboItems.value.length) return
  await store.createCombo({
    name: comboForm.name.trim(),
    description: comboForm.description.trim(),
    basePrice: comboForm.basePrice,
    imageUrl: comboForm.imageUrl.trim(),
    imageFile: comboImageFile.value,
    items: comboItems.value,
  })
  comboForm.name = ''
  comboForm.description = ''
  comboForm.basePrice = 0
  comboForm.imageUrl = ''
  comboItems.value = []
  comboImageFile.value = null
  comboImagePreview.value = ''
  statusBanner.value = 'Combo creado.'
}

const handleComboImageInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  comboImageFile.value = target.files?.[0] || null
  comboImagePreview.value = comboImageFile.value ? URL.createObjectURL(comboImageFile.value) : ''
}

const addBundleItem = () => {
  if (!bundleProductId.value) return
  const existing = bundleItems.value.find((item) => item.productId === bundleProductId.value)
  if (existing) {
    existing.quantity += Math.max(1, bundleQuantity.value)
  } else {
    bundleItems.value.push({
      productId: bundleProductId.value,
      quantity: Math.max(1, bundleQuantity.value),
    })
  }
  bundleProductId.value = 0
  bundleQuantity.value = 1
}

const removeBundleItem = (productId: number) => {
  bundleItems.value = bundleItems.value.filter((item) => item.productId !== productId)
}

const handleBundleImageInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  bundleImageFile.value = target.files?.[0] || null
  bundleImagePreview.value = bundleImageFile.value ? URL.createObjectURL(bundleImageFile.value) : ''
}

const submitBundle = async () => {
  if (!bundleForm.name.trim() || !bundleItems.value.length) return
  await store.createBundle({
    name: bundleForm.name.trim(),
    description: bundleForm.description.trim(),
    pricingMode: bundleForm.pricingMode,
    fixedPrice: bundleForm.fixedPrice,
    discountPercentage: bundleForm.discountPercentage,
    imageFile: bundleImageFile.value,
    items: bundleItems.value,
  })
  bundleForm.name = ''
  bundleForm.description = ''
  bundleForm.pricingMode = 'fixed_price'
  bundleForm.fixedPrice = 0
  bundleForm.discountPercentage = 0
  bundleItems.value = []
  bundleImageFile.value = null
  bundleImagePreview.value = ''
  statusBanner.value = 'Bundle creado.'
}

watch(
  () => store.orders.map((order) => order.id),
  (ids) => {
    const newOrders = ids.filter((id) => !knownOrderIds.value.includes(id))
    if (knownOrderIds.value.length && newOrders.length) {
      statusBanner.value = 'Nuevo pedido recibido.'
      window.setTimeout(() => {
        if (statusBanner.value === 'Nuevo pedido recibido.') statusBanner.value = ''
      }, 2500)
    }
    knownOrderIds.value = [...ids]
  },
  { immediate: true },
)

onMounted(async () => {
  await store.refreshAll()
})
</script>

<template>
  <section class="space-y-4">
    <div v-if="statusBanner" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
      {{ statusBanner }}
    </div>

    <header class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">{{ headerTitle }}</h2>
          <p class="text-sm text-slate-500">{{ headerSubtitle }}</p>
        </div>
        <AppButton v-if="activeTab !== 'home'" variant="soft" @click="activeTab = 'home'">Volver a acciones</AppButton>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Pedidos Activos</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ activeOrders.length }}</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">En Preparacion</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ preparingOrders.length }}</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Pendientes de Envio</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ pendingShippingOrders.length }}</p>
        </article>
        <article class="rounded-xl border p-3" :class="lowIngredientsCount > 0 ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
          <p class="text-xs uppercase tracking-wide" :class="lowIngredientsCount > 0 ? 'text-amber-700' : 'text-emerald-700'">Faltantes de ingredientes</p>
          <p class="mt-1 text-2xl font-semibold" :class="lowIngredientsCount > 0 ? 'text-amber-800' : 'text-emerald-800'">
            {{ lowIngredientsCount }}
          </p>
          <p class="text-xs" :class="lowIngredientsCount > 0 ? 'text-amber-700' : 'text-emerald-700'">
            {{ lowIngredientsCount > 0 ? `Atencion: ${lowIngredientsCount} por agotarse` : 'Stock de ingredientes estable' }}
          </p>
        </article>
      </div>
    </header>

    <div v-if="activeTab === 'home'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Navegacion por areas</h3>
        <p class="mt-1 text-sm text-slate-500">Entradas rapidas por tipo de tarea.</p>
        <div class="mt-4 grid gap-3 lg:grid-cols-3">
          <div v-for="group in groupedActions" :key="group.group" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-sm font-semibold text-slate-900">{{ group.group }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ group.description }}</p>
            <div class="mt-3 space-y-2">
              <button
                v-for="action in group.actions"
                :key="action.key"
                type="button"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-left transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-sm"
                @click="activeTab = action.key"
              >
                <p class="text-sm font-semibold text-slate-900">{{ action.title }}</p>
                <p class="text-xs text-slate-500">{{ action.hint }}</p>
              </button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'orders'" class="grid gap-4 lg:grid-cols-[260px_1fr]">
      <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Asignaciones rapidas</h3>
        <div class="mt-3 space-y-3">
          <label class="block">
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Asignar a cocina</span>
            <select v-model.number="store.shiftEmployeeId" class="input">
              <option v-for="employee in store.activeEmployees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
            </select>
          </label>
          <label class="block">
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Asignar a repartidor</span>
            <select v-model.number="store.shiftDriverId" class="input">
              <option v-for="driver in store.activeDrivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
            </select>
          </label>
        </div>
      </aside>

      <div>
        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Filtro de flujo:</span>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'all' }" @click="orderFlowFilter = 'all'">Todos</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'in_kitchen' }" @click="orderFlowFilter = 'in_kitchen'">En cocina</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'waiting_driver' }" @click="orderFlowFilter = 'waiting_driver'">Esperando repartidor</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'delayed' }" @click="orderFlowFilter = 'delayed'">Demorados</button>
        </div>

        <div v-if="!boardOrders.length" class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
          <p class="text-sm font-semibold text-slate-900">Esperando tu primera venta del dia</p>
          <p class="mt-1 text-xs text-slate-500">No hay pedidos para este filtro en este momento.</p>
        </div>

        <TransitionGroup name="order-fade" tag="div" class="grid gap-3 sm:grid-cols-2">
          <article v-for="order in boardOrders" :key="`order-${order.id}`" class="rounded-2xl border p-4 shadow-sm transition" :class="kdsCardClass(order.createdAt)">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-semibold text-slate-900">{{ orderCode(order.id) }}</p>
                <p class="text-xs text-slate-500">{{ elapsedText(order.createdAt) }}</p>
              </div>
              <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="orderPillClass(order.status)">{{ orderPillLabel(order.status) }}</span>
            </div>
            <p class="mt-2 text-sm font-medium text-slate-700">{{ order.customer }}</p>
            <p class="text-xs text-slate-500">{{ order.address }}</p>
            <p class="mt-1 text-xs text-slate-500">
              Pago: {{ paymentMethodLabel(order.paymentMethod) }} | {{ paymentStatusLabel(order.paymentStatus) }} | ETA {{ order.etaMin }} min
            </p>
            <p class="mt-2 text-xs text-slate-600">{{ orderLines(order) }}</p>
            <div class="mt-2 space-y-2">
              <div v-for="(item, index) in order.items" :key="`${order.id}-${index}`" class="rounded-lg border border-slate-200 bg-white/80 px-2 py-1.5">
                <p class="text-xs font-semibold text-slate-700">{{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}</p>
                <div class="mt-1 flex flex-wrap gap-1">
                  <span
                    v-for="removed in itemRemovedIngredients(item)"
                    :key="`${order.id}-${index}-rm-${removed}`"
                    class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] text-rose-700 line-through"
                  >
                    {{ removed }}
                  </span>
                  <span
                    v-for="(extra, extraIndex) in itemExtras(item)"
                    :key="`${order.id}-${index}-ex-${extraIndex}`"
                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
                  >
                    + {{ extra.name }}
                  </span>
                </div>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton v-if="order.status === 'received'" variant="primary" @click="acceptOrder(order.id)">Aceptar</AppButton>
              <AppButton v-if="order.status === 'preparing'" variant="primary" @click="completeOrder(order.id)">Completar</AppButton>
              <AppButton v-if="order.status === 'ready'" variant="soft" @click="assignDriver(order.id)">Despachar</AppButton>
              <AppButton variant="soft" @click="openOrderEditor(order)">Editar</AppButton>
            </div>
          </article>
        </TransitionGroup>
      </div>
    </div>

    <div v-if="activeTab === 'catalog'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900">Inventario de Productos</h3>
          <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-1">
            <button type="button" class="tab-btn" :class="{ active: inventoryView === 'list' }" @click="inventoryView = 'list'">Lista</button>
            <button type="button" class="tab-btn" :class="{ active: inventoryView === 'new' }" @click="inventoryView = 'new'">Nuevo</button>
          </div>
        </div>
      </article>

      <article v-if="inventoryView === 'new'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Configurador de producto</h3>
        <p class="text-xs text-slate-500">Divide y venceras: completa por secciones para evitar errores.</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'basic' }" @click="productFormTab = 'basic'">Basicos</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'recipe' }" @click="productFormTab = 'recipe'">Receta</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'ops' }" @click="productFormTab = 'ops'">Inventario/Logistica</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'media' }" @click="productFormTab = 'media'">Multimedia</button>
        </div>
      </article>

      <article v-if="inventoryView === 'new' && productFormTab === 'media'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Multimedia</h3>
        <p class="text-xs text-slate-500">Usa fotos cuadradas 1:1 para mantener el catalogo alineado.</p>
        <div
          class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center"
          @drop="handleDropImage"
          @dragover.prevent
        >
          <p class="text-sm font-semibold text-slate-700">Arrastra una imagen o selecciona archivo</p>
          <input class="mt-2 w-full text-sm text-slate-600" type="file" accept="image/*" @change="handleImageInput" />
          <img v-if="productImagePreview" :src="productImagePreview" alt="Preview" class="mx-auto mt-3 aspect-square h-36 rounded-xl border border-slate-200 object-cover" />
        </div>
      </article>

      <article v-if="inventoryView === 'new'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Datos del producto</h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ productForm.name ? `Editando: ${productForm.name}` : 'Crea un producto nuevo' }}
        </p>
        <form class="mt-3 grid gap-2 md:grid-cols-2" @submit.prevent="submitProduct">
          <template v-if="productFormTab === 'basic'">
            <input v-model="productForm.name" class="input" type="text" required placeholder="Nombre del producto" />
            <div class="relative">
              <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">$</span>
              <input v-model.number="productForm.price" class="input pl-8" type="number" min="1" required placeholder="Precio de venta" />
            </div>
            <input v-model="productForm.category" class="input md:col-span-2" type="text" placeholder="Categoria (ej: Pizzas, Bebidas)" />
          </template>

          <template v-if="productFormTab === 'ops'">
            <input v-model.number="productForm.prepMin" class="input" type="number" min="1" required placeholder="Tiempo de preparacion (min)" />
            <input v-model.number="productForm.stockQuantity" class="input" type="number" min="0" placeholder="Stock disponible" />
            <input v-model.number="productForm.minStockQuantity" class="input md:col-span-2" type="number" min="0" placeholder="Alerta de falta (minimo)" />
          </template>

          <div v-if="productFormTab === 'recipe'" class="md:col-span-2 rounded-xl border border-slate-200 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reglas del ingrediente</p>
            <p class="text-xs text-slate-500">Ingredientes incluidos y removibles para el cliente.</p>
            <input v-model="ingredientSearch" class="input mt-2" type="text" placeholder="Buscar ingrediente..." />
            <select v-model="selectedIngredientIds" class="input mt-2 h-32" multiple>
              <option v-for="ingredient in filteredIngredients" :key="ingredient.id" :value="ingredient.id">
                {{ ingredientLabel(ingredient) }}
              </option>
            </select>
            <div class="mt-2 flex justify-end">
              <AppButton
                v-if="ingredientSearch.trim() && !filteredIngredients.some((ingredient) => ingredient.name.toLowerCase() === ingredientSearch.trim().toLowerCase())"
                variant="soft"
                type="button"
                @click="createInlineIngredient"
              >
                Crear "{{ ingredientSearch.trim() }}" ahora
              </AppButton>
            </div>
          </div>

          <div v-if="productFormTab === 'recipe'" class="md:col-span-2 rounded-xl border border-slate-200 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Costo extra</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_160px_auto]">
              <input v-model="extraDraft.name" class="input" type="text" placeholder="Nombre del agregado" />
              <input v-model.number="extraDraft.additionalPrice" class="input" type="number" min="0" placeholder="Costo extra" />
              <AppButton variant="primary" type="button" @click="addExtraDraft">Agregar extra</AppButton>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                v-for="(extra, index) in productExtras"
                :key="`${extra.name}-${index}`"
                type="button"
                class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                @click="removeExtra(index)"
              >
                + {{ extra.name }} (${{ extra.additionalPrice }}) x
              </button>
            </div>
          </div>

          <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            Asi lo vera el cliente: Base ${{ Number(productForm.price || 0).toFixed(2) }} | Con extras ${{ pricePreviewWithExtras.toFixed(2) }}
          </div>

          <AppButton variant="primary" type="submit" class="md:col-span-2">Guardar producto</AppButton>
        </form>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Crear ingrediente rapido</h3>
        <form class="mt-3 grid gap-2 sm:grid-cols-[1.3fr_1fr_1fr_auto]" @submit.prevent="submitIngredient">
          <input v-model="ingredientForm.name" class="input" type="text" required placeholder="Nombre del ingrediente" />
          <input v-model.number="ingredientForm.additionalPrice" class="input" type="number" min="0" placeholder="Costo extra (opcional)" />
          <input v-model.number="ingredientForm.stockQuantity" class="input" type="number" min="0" placeholder="Stock inicial" />
          <AppButton variant="primary" type="submit">Agregar</AppButton>
        </form>
        <div class="mt-3 grid gap-2 md:grid-cols-2">
          <div
            v-for="ingredient in lowIngredients"
            :key="`low-ing-${ingredient.id}`"
            class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2"
          >
            <p class="text-sm font-semibold text-amber-800">{{ ingredient.name }}</p>
            <p class="text-xs text-amber-700">Stock: {{ Number(ingredient.stockQuantity || 0).toFixed(2) }}</p>
            <AppButton variant="soft" class="mt-2" @click="deactivateIngredientGlobal(ingredient.id)">Desactivar globalmente</AppButton>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Acciones masivas de precios</h3>
        <p class="mt-1 text-xs text-slate-500">Escala cambios por categoria o seleccion manual.</p>
        <div class="mt-3 grid gap-2 md:grid-cols-2">
          <select v-model="bulkPriceForm.applyOn" class="input">
            <option value="category">Aplicar por categoria</option>
            <option value="selection">Aplicar a seleccion manual</option>
          </select>
          <select v-model="bulkPriceForm.category" class="input" :disabled="bulkPriceForm.applyOn !== 'category'">
            <option value="">Seleccionar categoria</option>
            <option v-for="category in productCategories.filter((category) => category !== 'all')" :key="`bulk-${category}`" :value="category">
              {{ category }}
            </option>
          </select>
          <select v-model="bulkPriceForm.mode" class="input">
            <option value="percentage">Aumentar/Reducir %</option>
            <option value="fixed_delta">Monto fijo (+/-)</option>
          </select>
          <input v-model.number="bulkPriceForm.value" class="input" type="number" step="0.01" placeholder="Valor" />
          <select v-model.number="bulkPriceForm.roundTo" class="input">
            <option :value="1">Sin redondeo</option>
            <option :value="5">Redondear x5</option>
            <option :value="10">Redondear x10</option>
            <option :value="50">Redondear x50</option>
            <option :value="100">Redondear x100</option>
          </select>
          <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
            <span>Seleccionados: {{ selectedProductsCount }}</span>
            <AppButton variant="soft" @click="runBulkPriceUpdate">Aplicar accion</AppButton>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Productos</h3>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
          <select v-model="productCategoryFilter" class="input">
            <option v-for="category in productCategories" :key="category" :value="category">
              {{ category === 'all' ? 'Todas las categorias' : category }}
            </option>
          </select>
          <select v-model="productStockFilter" class="input">
            <option value="all">Todos los estados de stock</option>
            <option value="ok">Stock OK</option>
            <option value="low">Stock bajo</option>
            <option value="out">Sin stock</option>
          </select>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="product in filteredProducts" :key="product.id" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <label class="mb-1 flex cursor-pointer items-center gap-2 text-xs text-slate-600">
              <input
                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                type="checkbox"
                :checked="selectedProductIds.includes(product.id)"
                @change="toggleProductSelection(product.id)"
              />
              Seleccionar para accion masiva
            </label>
            <p class="font-semibold text-slate-900">{{ product.name }}</p>
            <p class="text-xs text-slate-500">${{ product.price }} | {{ product.prepMin }} min | {{ product.category || 'Sin categoria' }}</p>
            <div class="mt-2 flex items-center gap-2">
              <span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="productStockClass(product.stockQuantity, product.minStockQuantity)">
                {{ productStockLabel(product.stockQuantity, product.minStockQuantity) }}
              </span>
              <span class="text-[11px]" :class="stockValueClass(product.stockQuantity, product.minStockQuantity)">Stock actual: {{ product.stockQuantity }}</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
              <AppButton variant="soft" @click="store.toggleProduct(product.id)">
                {{ product.enabled ? 'Desactivar' : 'Activar' }}
              </AppButton>
              <AppButton variant="ghost" @click="duplicateProductCard(product.id)">Duplicar</AppButton>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'combos'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Ofertas y combos</h3>
        <form class="mt-3 grid gap-2 md:grid-cols-2" @submit.prevent="submitCombo">
          <input v-model="comboForm.name" class="input" type="text" required placeholder="Nombre de la oferta" />
          <input v-model.number="comboForm.basePrice" class="input" type="number" min="0" required placeholder="Precio final del combo" />
          <input v-model="comboForm.description" class="input md:col-span-2" type="text" placeholder="Descripcion" />
          <input v-model="comboForm.imageUrl" class="input md:col-span-2" type="url" placeholder="Imagen URL (fallback)" />
          <div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Imagen del combo (upload)</p>
            <input class="mt-2 w-full text-sm text-slate-600" type="file" accept="image/*" @change="handleComboImageInput" />
            <img v-if="comboImagePreview" :src="comboImagePreview" alt="Preview combo" class="mt-3 h-24 rounded-xl border border-slate-200 object-cover" />
          </div>
          <div class="md:col-span-2 rounded-xl border border-slate-200 p-3">
            <div class="grid gap-2 sm:grid-cols-[1fr_120px_auto]">
              <select v-model.number="comboProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in store.products" :key="product.id" :value="product.id">{{ product.name }}</option>
              </select>
              <input v-model.number="comboQuantity" class="input" type="number" min="1" />
              <AppButton variant="soft" @click="addComboItem">Agregar Item</AppButton>
            </div>
            <div class="mt-3 space-y-2">
              <div v-for="item in comboItems" :key="item.productId" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <span class="flex items-center gap-2">
                  <img
                    v-if="store.getProduct(item.productId)?.imageUrl"
                    :src="store.getProduct(item.productId)?.imageUrl || ''"
                    alt="thumb"
                    class="h-8 w-8 rounded-md border border-slate-200 object-cover"
                  />
                  {{ store.getProduct(item.productId)?.name || 'Producto' }} x{{ item.quantity }}
                </span>
                <AppButton variant="ghost" @click="removeComboItem(item.productId)">Quitar</AppButton>
              </div>
            </div>
          </div>
          <AppButton variant="primary" type="submit" class="md:col-span-2">Guardar Combo</AppButton>
        </form>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Bundle Builder</h3>
        <form class="mt-3 grid gap-2 md:grid-cols-2" @submit.prevent="submitBundle">
          <input v-model="bundleForm.name" class="input" type="text" required placeholder="Nombre bundle" />
          <select v-model="bundleForm.pricingMode" class="input">
            <option value="fixed_price">Precio fijo</option>
            <option value="discount_percentage">Descuento porcentual</option>
          </select>
          <input v-model="bundleForm.description" class="input md:col-span-2" type="text" placeholder="Descripcion" />
          <input
            v-if="bundleForm.pricingMode === 'fixed_price'"
            v-model.number="bundleForm.fixedPrice"
            class="input"
            type="number"
            min="0"
            placeholder="Precio final del combo"
          />
          <input
            v-else
            v-model.number="bundleForm.discountPercentage"
            class="input"
            type="number"
            min="0"
            max="100"
            placeholder="Porcentaje de descuento aplicado"
          />
          <div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Imagen del bundle</p>
            <input class="mt-2 w-full text-sm text-slate-600" type="file" accept="image/*" @change="handleBundleImageInput" />
            <img v-if="bundleImagePreview" :src="bundleImagePreview" alt="Preview bundle" class="mt-3 h-24 rounded-xl border border-slate-200 object-cover" />
          </div>
          <div class="md:col-span-2 rounded-xl border border-slate-200 p-3">
            <div class="grid gap-2 sm:grid-cols-[1fr_120px_auto]">
              <select v-model.number="bundleProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in store.products" :key="product.id" :value="product.id">{{ product.name }}</option>
              </select>
              <input v-model.number="bundleQuantity" class="input" type="number" min="1" />
              <AppButton variant="soft" @click="addBundleItem">Agregar Item</AppButton>
            </div>
            <div class="mt-3 space-y-2">
              <div v-for="item in bundleItems" :key="item.productId" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <span class="flex items-center gap-2">
                  <img
                    v-if="store.getProduct(item.productId)?.imageUrl"
                    :src="store.getProduct(item.productId)?.imageUrl || ''"
                    alt="thumb"
                    class="h-8 w-8 rounded-md border border-slate-200 object-cover"
                  />
                  {{ store.getProduct(item.productId)?.name || 'Producto' }} x{{ item.quantity }}
                </span>
                <AppButton variant="ghost" @click="removeBundleItem(item.productId)">Quitar</AppButton>
              </div>
            </div>
          </div>
          <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            Ahorro estimado del cliente: {{ bundleEstimatedSaving.toFixed(2) }}
          </div>
          <AppButton variant="primary" type="submit" class="md:col-span-2">Guardar Bundle</AppButton>
        </form>
      </article>
    </div>

    <div v-if="activeTab === 'categories'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Categorias del menu</h3>
        <p class="mt-1 text-xs text-slate-500">Organiza el menu y detecta rubros con alerta de falta.</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="category in categorySummary" :key="category.name" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="font-semibold text-slate-900">{{ category.name }}</p>
            <p class="text-xs text-slate-500">{{ category.items }} productos</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Alerta de falta: {{ category.low }}</span>
              <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Sin stock: {{ category.out }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'cashbox'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Caja del dia</h3>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total facturado</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">${{ cashboxSummary.total.toFixed(2) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-emerald-50 p-3">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Efectivo cobrado</p>
            <p class="mt-1 text-xl font-semibold text-emerald-800">${{ cashboxSummary.cash.toFixed(2) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-sky-50 p-3">
            <p class="text-xs uppercase tracking-wide text-sky-700">Mercado Pago</p>
            <p class="mt-1 text-xl font-semibold text-sky-800">${{ cashboxSummary.mp.toFixed(2) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-rose-50 p-3">
            <p class="text-xs uppercase tracking-wide text-rose-700">Pendiente de cobro</p>
            <p class="mt-1 text-xl font-semibold text-rose-800">${{ cashboxSummary.pending.toFixed(2) }}</p>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Comparativa rapida</h3>
        <p class="mt-1 text-xs text-slate-500">Ventas de hoy vs ayer.</p>
        <div class="mt-3 space-y-3">
          <div>
            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
              <span>Hoy</span>
              <span>${{ todaySales.toFixed(2) }}</span>
            </div>
            <div class="h-3 rounded-full bg-slate-100">
              <div class="h-3 rounded-full bg-emerald-500" :style="{ width: `${(todaySales / salesBarMax) * 100}%` }"></div>
            </div>
          </div>
          <div>
            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
              <span>Ayer</span>
              <span>${{ yesterdaySales.toFixed(2) }}</span>
            </div>
            <div class="h-3 rounded-full bg-slate-100">
              <div class="h-3 rounded-full bg-sky-500" :style="{ width: `${(yesterdaySales / salesBarMax) * 100}%` }"></div>
            </div>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Conciliacion de repartidores</h3>
        <div v-if="driverPendingCash.length" class="mt-3 space-y-2">
          <div
            v-for="row in driverPendingCash"
            :key="`driver-pending-${row.id}`"
            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
          >
            <p class="text-sm font-semibold text-slate-900">{{ row.name }}</p>
            <p class="text-sm font-semibold text-rose-700">${{ row.pending.toFixed(2) }} pendiente</p>
          </div>
        </div>
        <p v-else class="mt-2 text-sm text-slate-500">No hay efectivo pendiente por repartir.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Movimientos</h3>
        <div class="mt-3 space-y-2">
          <div
            v-for="order in cashOrders"
            :key="`cash-${order.id}`"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
          >
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ orderCode(order.id) }} - {{ order.customer }}</p>
              <p class="text-xs text-slate-500">{{ order.address }}</p>
              <p v-if="order.paymentMethod === 'cash' && order.cashReceived" class="text-xs text-slate-500">
                Recibido: ${{ order.cashReceived.toFixed(2) }} | Vuelto: ${{ order.changeAmount.toFixed(2) }}
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="paymentMethodClass(order.paymentMethod)">
                {{ paymentMethodLabel(order.paymentMethod) }}
              </span>
              <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="paymentStatusClass(order.paymentStatus)">
                {{ paymentStatusLabel(order.paymentStatus) }}
              </span>
              <span class="text-sm font-semibold text-slate-900">${{ order.total.toFixed(2) }}</span>
              <AppButton v-if="order.paymentStatus === 'pending'" variant="soft" @click="markOrderPaid(order.id)">
                Marcar cobrado
              </AppButton>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'roles'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Crear Rol</h3>
        <form class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="submitRole">
          <input v-model="roleForm.name" class="input" type="text" required placeholder="role_name" />
          <input v-model="roleForm.label" class="input" type="text" required placeholder="Etiqueta" />
          <AppButton variant="primary" type="submit">Crear</AppButton>
        </form>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Roles disponibles</h3>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
          <div v-for="role in store.roles" :key="role.id" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <p class="font-semibold text-slate-900">{{ role.label }}</p>
            <p class="text-xs text-slate-500">{{ role.name }}</p>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'team'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Crear Usuario / Empleado</h3>
        <form class="mt-3 grid gap-2 md:grid-cols-2" @submit.prevent="submitUser">
          <input v-model="userForm.name" class="input" type="text" required placeholder="Nombre" />
          <input v-model="userForm.email" class="input" type="email" required placeholder="Email" />
          <input v-model="userForm.password" class="input" type="text" required placeholder="Password temporal" />
          <select v-model.number="userForm.roleId" class="input" required>
            <option :value="0">Seleccionar rol</option>
            <option v-for="role in store.roles" :key="role.id" :value="role.id">{{ role.label }}</option>
          </select>
          <AppButton variant="primary" type="submit" class="md:col-span-2">Crear Usuario</AppButton>
        </form>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Equipo</h3>
        <div class="mt-3 space-y-2">
          <div v-for="user in store.users" :key="user.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div>
              <p class="font-semibold text-slate-900">{{ user.name }} <span class="text-xs text-slate-500">({{ user.role }})</span></p>
              <p class="text-xs text-slate-500">{{ user.email }}</p>
            </div>
            <AppButton variant="soft" @click="toggleUserState(user.id, user.active)">
              {{ user.active ? 'Desactivar' : 'Activar' }}
            </AppButton>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'customers'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Top Customer</h3>
        <div v-if="topCustomer" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
          <p class="text-base font-semibold text-emerald-800">{{ topCustomer.customerName }}</p>
          <p class="text-sm text-emerald-700">
            Ordenes: {{ topCustomer.totalOrders }} | Total: ${{ topCustomer.totalSpent.toFixed(2) }}
          </p>
          <p class="text-xs text-emerald-700">Ultima direccion: {{ topCustomer.lastAddress }}</p>
        </div>
        <p v-else class="mt-2 text-sm text-slate-500">Sin datos de clientes por ahora.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">CRM Light</h3>
        <div class="mt-3 space-y-2">
          <div
            v-for="customer in store.customerInsights"
            :key="customer.customerKey"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
          >
            <div>
              <p class="text-sm font-semibold text-slate-900">
                #{{ customer.rank }} {{ customer.customerName }}
              </p>
              <p class="text-xs text-slate-500">
                {{ customer.totalOrders }} compras | ${{ customer.totalSpent.toFixed(2) }} | {{ customer.lastAddress }}
              </p>
            </div>
            <AppButton
              :variant="customer.isBlocked ? 'soft' : 'ghost'"
              @click="toggleClientBlock(customer.customerKey, customer.customerName, customer.isBlocked)"
            >
              {{ customer.isBlocked ? 'Desbloquear' : 'Bloquear' }}
            </AppButton>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'audit'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Historial de auditoria</h3>
        <p class="mt-1 text-xs text-slate-500">Trazabilidad de cambios: precios, usuarios y estados operativos.</p>
        <div class="mt-3 space-y-2">
          <div v-for="log in store.auditLogs" :key="log.id" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ auditActionLabel(log.action) }}</p>
              <span class="text-xs text-slate-500">{{ new Date(log.createdAt).toLocaleString('es-AR') }}</span>
            </div>
            <p class="text-xs text-slate-500">
              Usuario: {{ log.userName }} | Entidad: {{ log.entityType }}{{ log.entityId ? ` #${log.entityId}` : '' }}
            </p>
          </div>
        </div>
      </article>
    </div>

    <div v-if="orderEditorOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
      <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Editar pedido {{ orderCode(orderEditForm.id) }}</h3>
            <p class="text-xs text-slate-500">Actualiza datos de cliente, estado y pago.</p>
          </div>
          <AppButton variant="ghost" @click="closeOrderEditor">Cerrar</AppButton>
        </div>

        <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="saveOrderEditor">
          <input v-model="orderEditForm.customer" class="input" type="text" required placeholder="Cliente" />
          <input v-model="orderEditForm.address" class="input" type="text" required placeholder="Direccion" />

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Estado
            <select v-model="orderEditForm.status" class="input mt-1">
              <option value="received">Pendiente</option>
              <option value="preparing">En cocina</option>
              <option value="ready">Listo</option>
              <option value="onroute">En envio</option>
              <option value="delivered">Entregado</option>
              <option value="canceled">Cancelado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </label>

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Tiempo estimado (min)
            <input v-model.number="orderEditForm.etaMin" class="input mt-1" type="number" min="0" />
          </label>

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Metodo de pago
            <select v-model="orderEditForm.paymentMethod" class="input mt-1">
              <option value="cash">Efectivo</option>
              <option value="mercado_pago">Mercado Pago</option>
            </select>
          </label>

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Estado de cobro
            <select v-model="orderEditForm.paymentStatus" class="input mt-1">
              <option value="pending">Pendiente</option>
              <option value="paid">Cobrado</option>
              <option value="refunded">Reintegrado</option>
            </select>
          </label>

          <label v-if="orderEditForm.paymentMethod === 'cash'" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Efectivo recibido
            <input v-model.number="orderEditForm.cashReceived" class="input mt-1" type="number" min="0" step="0.01" />
          </label>

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Cocina asignada
            <select v-model="orderEditForm.employeeId" class="input mt-1">
              <option value="">Sin asignar</option>
              <option v-for="employee in store.activeEmployees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
            </select>
          </label>

          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Repartidor asignado
            <select v-model="orderEditForm.driverId" class="input mt-1">
              <option value="">Sin asignar</option>
              <option v-for="driver in store.activeDrivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
            </select>
          </label>

          <div class="mt-2 flex justify-end gap-2 md:col-span-2">
            <AppButton variant="ghost" type="button" @click="closeOrderEditor">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">Guardar cambios</AppButton>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
.input {
  width: 100%;
  border: 1px solid rgb(226 232 240);
  border-radius: 0.75rem;
  padding: 0.55rem 0.7rem;
  background: white;
  color: rgb(51 65 85);
}

.tab-btn {
  border-radius: 0.6rem;
  padding: 0.35rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: rgb(100 116 139);
}

.tab-btn.active {
  background: white;
  color: rgb(15 23 42);
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
}

.order-fade-enter-active,
.order-fade-leave-active {
  transition: all 260ms ease;
}

.order-fade-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}

.order-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

.kds-critical {
  animation: kdsCriticalPulse 1.4s ease-in-out infinite;
}

.kds-critical-hard {
  animation: kdsCriticalHardPulse 0.95s ease-in-out infinite;
}

@keyframes kdsCriticalPulse {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgb(244 63 94 / 0.2);
  }
  50% {
    box-shadow: 0 0 0 6px rgb(244 63 94 / 0.08);
  }
}

@keyframes kdsCriticalHardPulse {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgb(225 29 72 / 0.35);
    transform: translateY(0);
  }
  50% {
    box-shadow: 0 0 0 10px rgb(225 29 72 / 0.08);
    transform: translateY(-1px);
  }
}
</style>
