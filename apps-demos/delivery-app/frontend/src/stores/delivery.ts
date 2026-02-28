import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { resolveAssetUrl, resolveProductImageUrl } from '../utils/media'

export type OrderStatus = 'received' | 'preparing' | 'ready' | 'onroute' | 'delivered' | 'canceled' | 'rejected'
export type UserRole = 'superadmin' | 'admin' | 'employee' | 'driver' | 'client'
export type PaymentMethod = 'cash' | 'mercado_pago'
export type PaymentStatus = 'pending' | 'paid' | 'refunded'

interface SessionUser {
  id: number
  name: string
  email: string
  role: UserRole
  tenantId?: number | null
  tenantSlug?: string | null
  tenantName?: string | null
}

export interface Product {
  id: number
  name: string
  price: number
  prepMin: number
  category?: string | null
  stockQuantity: number
  minStockQuantity: number
  enabled: boolean
  imageUrl?: string | null
}

export interface IngredientItem {
  id: number
  name: string
  additionalPrice: number
  stockQuantity?: number
  unitCost?: number
  active: boolean
}

export interface TeamMember {
  id: number
  name: string
  email?: string
  role: UserRole
  active: boolean
}

export interface RoleItem {
  id: number
  name: string
  label: string
}

export interface BundleItem {
  id: number
  name: string
  pricingMode: 'fixed_price' | 'discount_percentage'
  fixedPrice: number
  discountPercentage: number
  imageUrl?: string | null
  enabled: boolean
}

export interface ComboItem {
  id: number
  name: string
  description?: string | null
  basePrice: number
  imageUrl?: string | null
  enabled: boolean
  items: Array<{ productId: number; quantity: number }>
}

export interface DailyMenuItem {
  id: number
  itemType: 'product' | 'combo'
  itemId: number
  promoPrice: number | null
  sortOrder: number
  name?: string | null
  imageUrl?: string | null
  basePrice?: number
}

export interface DailyMenu {
  id: number
  name: string
  description?: string | null
  imageUrl?: string | null
  isActive: boolean
  slot: 'all_day' | 'lunch' | 'dinner'
  weekdays: number[]
  activeFrom?: string | null
  activeTo?: string | null
  priority: number
  items: DailyMenuItem[]
}

export interface AuditLogItem {
  id: number
  action: string
  entityType: string
  entityId: number | null
  metadata: Record<string, unknown> | null
  createdAt: number
  userName: string
}

export interface CustomerInsight {
  customerKey: string
  customerName: string
  lastAddress: string
  totalOrders: number
  totalSpent: number
  lastOrderAt: number
  isBlocked: boolean
  notes?: string | null
  rank: number
}

export interface OrderItem {
  productId: number
  qty: number
  name?: string
  excludedIngredientIds?: number[]
  extras?: Array<{ id?: number; name: string; additionalPrice?: number }>
}

export interface Order {
  id: number
  customer: string
  address: string
  paymentMethod: PaymentMethod
  paymentStatus: PaymentStatus
  cashReceived: number | null
  changeAmount: number
  total: number
  items: OrderItem[]
  status: OrderStatus
  employeeId: number | null
  driverId: number | null
  createdAt: number
  etaMin: number
}

export interface OrderUpdatePayload {
  id: number
  customer?: string
  address?: string
  status?: OrderStatus
  employeeId?: number | null
  driverId?: number | null
  paymentMethod?: PaymentMethod
  paymentStatus?: PaymentStatus
  cashReceived?: number | null
  etaMin?: number
}

const AUTH_KEY = 'delivery-vue-auth-v2'
const BACKEND_API_URL = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'
const DEFAULT_TENANT_SLUG = import.meta.env.VITE_DEFAULT_TENANT_SLUG || 'demo-delivery'

const statusFromApi = (value: string): OrderStatus => {
  if (value === 'pendiente') return 'received'
  if (value === 'preparando') return 'preparing'
  if (value === 'listo') return 'ready'
  if (value === 'en_camino') return 'onroute'
  if (value === 'cancelado') return 'canceled'
  if (value === 'rechazado') return 'rejected'
  return 'delivered'
}

const statusToApi = (value: OrderStatus): string => {
  if (value === 'received') return 'pendiente'
  if (value === 'preparing') return 'preparando'
  if (value === 'ready') return 'listo'
  if (value === 'onroute') return 'en_camino'
  if (value === 'canceled') return 'cancelado'
  if (value === 'rejected') return 'rechazado'
  return 'entregado'
}

const normalizeSessionUser = (raw: SessionUser & { tenant_id?: number | null; tenant_slug?: string | null; tenant_name?: string | null }): SessionUser => ({
  id: Number(raw.id),
  name: String(raw.name || ''),
  email: String(raw.email || ''),
  role: raw.role,
  tenantId: raw.tenantId ?? raw.tenant_id ?? null,
  tenantSlug: raw.tenantSlug ?? raw.tenant_slug ?? null,
  tenantName: raw.tenantName ?? raw.tenant_name ?? null,
})

export const useDeliveryStore = defineStore('delivery', () => {
  const products = ref<Product[]>([])
  const employees = ref<TeamMember[]>([])
  const drivers = ref<TeamMember[]>([])
  const users = ref<TeamMember[]>([])
  const roles = ref<RoleItem[]>([])
  const orders = ref<Order[]>([])
  const ingredients = ref<IngredientItem[]>([])
  const combos = ref<ComboItem[]>([])
  const bundles = ref<BundleItem[]>([])
  const dailyMenus = ref<DailyMenu[]>([])
  const auditLogs = ref<AuditLogItem[]>([])
  const customerInsights = ref<CustomerInsight[]>([])

  const shiftEmployeeId = ref<number | null>(null)
  const shiftDriverId = ref<number | null>(null)

  const clientLookup = ref('')
  const flashMessage = ref('')
  const currentUser = ref<SessionUser | null>(null)
  const publicTenantSlug = ref('')
  const storefrontName = ref('Dunamis Store')
  const authError = ref('')
  const apiToken = ref('')
  const realtimeConnected = ref(false)
  const realtimeError = ref('')

  const activeOrders = computed(() => orders.value.filter((order) => !['delivered', 'canceled', 'rejected'].includes(order.status)).length)
  const kitchenOrders = computed(() => orders.value.filter((order) => ['received', 'preparing'].includes(order.status)).length)
  const routeOrders = computed(() => orders.value.filter((order) => order.status === 'onroute').length)
  const avgEta = computed(() => {
    if (!orders.value.length) return 0
    return Math.round(orders.value.reduce((acc, order) => acc + order.etaMin, 0) / orders.value.length)
  })

  const activeProducts = computed(() => products.value.filter((product) => product.enabled))
  const activeEmployees = computed(() => employees.value.filter((member) => member.active))
  const activeDrivers = computed(() => drivers.value.filter((member) => member.active))
  const sortedOrders = computed(() => [...orders.value].sort((a, b) => b.createdAt - a.createdAt))
  const clientOrders = computed(() => {
    const query = clientLookup.value.trim().toLowerCase()
    if (!query) return sortedOrders.value
    return sortedOrders.value.filter((order) => order.customer.toLowerCase().includes(query))
  })
  const isAuthenticated = computed(() => currentUser.value !== null)
  const activeTenantSlug = computed(() => {
    const sessionSlug = (currentUser.value?.tenantSlug || '').trim()
    if (sessionSlug) return sessionSlug
    return publicTenantSlug.value.trim()
  })
  const activeStorefrontName = computed(() => {
    const sessionName = (currentUser.value?.tenantName || '').trim()
    if (sessionName) return sessionName
    const fallbackName = storefrontName.value.trim()
    return fallbackName || 'Dunamis Store'
  })
  const allowedRouteByRole = computed(() => {
    switch (currentUser.value?.role) {
      case 'superadmin':
        return '/superadmin/home'
      case 'admin':
        return '/admin/home'
      case 'employee':
        return '/empleado/panel'
      case 'driver':
        return '/repartidor/ruta'
      case 'client':
        return '/cliente/tienda'
      default:
        return '/login'
    }
  })

  const getProduct = (id: number) => products.value.find((product) => product.id === id) || null
  const getIngredient = (id: number) => ingredients.value.find((ingredient) => ingredient.id === id) || null
  const getEmployee = (id: number) => employees.value.find((employee) => employee.id === id) || null
  const getDriver = (id: number) => drivers.value.find((driver) => driver.id === id) || null
  const findOrder = (id: number) => orders.value.find((order) => order.id === id) || null

  const pushFlash = (message: string) => {
    flashMessage.value = message
    window.setTimeout(() => {
      if (flashMessage.value === message) {
        flashMessage.value = ''
      }
    }, 2200)
  }

  const persistAuth = () => {
    if (!currentUser.value || !apiToken.value) {
      localStorage.removeItem(AUTH_KEY)
      return
    }
    localStorage.setItem(
      AUTH_KEY,
      JSON.stringify({
        user: currentUser.value,
        token: apiToken.value,
      }),
    )
  }

  const initializeAuth = () => {
    authError.value = ''
    const raw = localStorage.getItem(AUTH_KEY)
    if (!raw) {
      currentUser.value = null
      apiToken.value = ''
      return
    }
    try {
      const parsed = JSON.parse(raw) as { user: SessionUser & { tenant_id?: number | null; tenant_slug?: string | null; tenant_name?: string | null }; token: string }
      currentUser.value = normalizeSessionUser(parsed.user)
      if (currentUser.value.tenantSlug) {
        setPublicTenantSlug(currentUser.value.tenantSlug)
      }
      if (currentUser.value.tenantName) {
        storefrontName.value = currentUser.value.tenantName
      }
      apiToken.value = parsed.token
    } catch {
      currentUser.value = null
      apiToken.value = ''
      localStorage.removeItem(AUTH_KEY)
    }
  }

  const restoreAuthFromStorage = () => {
    if (apiToken.value) return
    const raw = localStorage.getItem(AUTH_KEY)
    if (!raw) return
    try {
      const parsed = JSON.parse(raw) as {
        user?: SessionUser & { tenant_id?: number | null; tenant_slug?: string | null; tenant_name?: string | null }
        token?: string
      }
      if (parsed.token) {
        apiToken.value = parsed.token
      }
      if (parsed.user && !currentUser.value) {
        currentUser.value = normalizeSessionUser(parsed.user)
        if (currentUser.value.tenantSlug) {
          setPublicTenantSlug(currentUser.value.tenantSlug)
        }
        if (currentUser.value.tenantName) {
          storefrontName.value = currentUser.value.tenantName
        }
      }
    } catch {
      // noop
    }
  }

  const withTenantSlug = (path: string) => {
    const slug = activeTenantSlug.value
    if (!slug || currentUser.value) return path
    const separator = path.includes('?') ? '&' : '?'
    return `${path}${separator}tenant_slug=${encodeURIComponent(slug)}`
  }

  const setPublicTenantSlug = (slugRaw: string) => {
    publicTenantSlug.value = (slugRaw || '').trim()
  }

  const fetchStorefront = async (slugRaw?: string) => {
    const slug = (slugRaw || activeTenantSlug.value || '').trim()
    if (!slug) {
      storefrontName.value = 'Dunamis Store'
      return
    }
    try {
      const payload = await apiRequest<{ name?: string }>(`/storefront/${encodeURIComponent(slug)}`)
      storefrontName.value = String(payload?.name || 'Dunamis Store')
    } catch {
      storefrontName.value = 'Dunamis Store'
    }
  }

  const apiRequest = async <T>(path: string, init?: RequestInit, auth = false): Promise<T> => {
    if (auth && !apiToken.value) {
      restoreAuthFromStorage()
    }

    const headers = new Headers(init?.headers || {})
    if (!headers.has('Accept')) {
      headers.set('Accept', 'application/json')
    }
    if (!headers.has('X-Requested-With')) {
      headers.set('X-Requested-With', 'XMLHttpRequest')
    }
    if (init?.body && !(init.body instanceof FormData) && !headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json')
    }
    if (auth && apiToken.value) {
      headers.set('Authorization', `Bearer ${apiToken.value}`)
    }
    if (auth && !apiToken.value) {
      throw new Error('api-auth-missing')
    }

    const response = await fetch(`${BACKEND_API_URL.replace(/\/$/, '')}${withTenantSlug(path)}`, {
      ...init,
      headers,
    })

    if (!response.ok) {
      let apiMessage = ''
      try {
        const errorPayload = (await response.json()) as { message?: string }
        apiMessage = String(errorPayload?.message || '')
      } catch {
        // noop
      }
      if (auth && response.status === 401) {
        currentUser.value = null
        apiToken.value = ''
        persistAuth()
      }
      throw new Error(`api-error:${response.status}${apiMessage ? `:${apiMessage}` : ''}`)
    }

    return (await response.json()) as T
  }

  const normalizeProducts = (payload: Array<Record<string, unknown>>) => {
    products.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      price: Number(item.base_price || 0),
      prepMin: Number(item.prep_min || 15),
      category: item.category ? String(item.category) : null,
      stockQuantity: Number(item.stock_quantity || 0),
      minStockQuantity: Number(item.min_stock_quantity || 0),
      enabled: Boolean(item.is_active),
      imageUrl: resolveProductImageUrl(item.image_url as string | null, String(item.name || '')),
    }))
  }

  const normalizeIngredients = (payload: Array<Record<string, unknown>>) => {
    ingredients.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      additionalPrice: Number(item.additional_price || 0),
      stockQuantity: Number(item.stock_quantity || 0),
      unitCost: Number(item.unit_cost || 0),
      active: Boolean(item.is_active),
    }))
  }

  const normalizeBundles = (payload: Array<Record<string, unknown>>) => {
    bundles.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      pricingMode: String(item.pricing_mode || 'fixed_price') as 'fixed_price' | 'discount_percentage',
      fixedPrice: Number(item.fixed_price || 0),
      discountPercentage: Number(item.discount_percentage || 0),
      imageUrl: resolveAssetUrl(item.image_url as string | null),
      enabled: Boolean(item.is_active),
    }))
  }

  const normalizeCombos = (payload: Array<Record<string, unknown>>) => {
    combos.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      description: item.description ? String(item.description) : null,
      basePrice: Number(item.base_price || 0),
      imageUrl: resolveAssetUrl(
        (item.image_url as string | null) ||
          (item.imageUrl as string | null) ||
          ((item.media as Array<{ original_url?: string; url?: string }> | undefined)?.[0]?.original_url ?? null) ||
          ((item.media as Array<{ original_url?: string; url?: string }> | undefined)?.[0]?.url ?? null),
      ),
      enabled: Boolean(item.is_active),
      items: (Array.isArray(item.products) ? item.products : [])
        .map((product) => {
          const row = product as { id?: number; product_id?: number; pivot?: { quantity?: number }; quantity?: number }
          return {
            productId: Number(row.id || row.product_id || 0),
            quantity: Number(row.pivot?.quantity || row.quantity || 1),
          }
        })
        .filter((row) => row.productId > 0),
    }))
  }

  const normalizeDailyMenus = (payload: Array<Record<string, unknown>>) => {
    dailyMenus.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      description: item.description ? String(item.description) : null,
      imageUrl: resolveAssetUrl(item.image_url as string | null),
      isActive: Boolean(item.is_active),
      slot: String(item.slot || 'all_day') as 'all_day' | 'lunch' | 'dinner',
      weekdays: Array.isArray(item.weekdays) ? item.weekdays.map((value) => Number(value)).filter((value) => value >= 1 && value <= 7) : [],
      activeFrom: item.active_from ? String(item.active_from) : null,
      activeTo: item.active_to ? String(item.active_to) : null,
      priority: Number(item.priority || 0),
      items: (Array.isArray(item.items) ? item.items : []).map((row) => ({
        id: Number((row as { id?: number }).id || 0),
        itemType: String((row as { item_type?: string }).item_type || 'product') as 'product' | 'combo',
        itemId: Number((row as { item_id?: number }).item_id || 0),
        promoPrice: (row as { promo_price?: number | null }).promo_price === null || (row as { promo_price?: number | null }).promo_price === undefined
          ? null
          : Number((row as { promo_price?: number }).promo_price || 0),
        sortOrder: Number((row as { sort_order?: number }).sort_order || 0),
        name: String((row as { name?: string }).name || ''),
        imageUrl: resolveAssetUrl((row as { image_url?: string | null }).image_url || null),
        basePrice: Number((row as { base_price?: number }).base_price || 0),
      })),
    }))
  }

  const normalizeUsers = (payload: Array<Record<string, unknown>>) => {
    users.value = payload.map((item) => ({
      id: Number(item.id),
      name: String(item.name || ''),
      email: String(item.email || ''),
      role: String(item.role || 'client') as UserRole,
      active: Boolean(item.is_active),
    }))

    employees.value = users.value.filter((user) => user.role === 'employee')
    drivers.value = users.value.filter((user) => user.role === 'driver')
  }

  const normalizeOrders = (payload: Array<Record<string, unknown>>) => {
    orders.value = payload.map((item) => ({
      id: Number(item.id),
      customer: String(item.customer || ''),
      address: String(item.address || ''),
      paymentMethod: String(item.payment_method || 'cash') as PaymentMethod,
      paymentStatus: String(item.payment_status || 'pending') as PaymentStatus,
      cashReceived: item.cash_received === null || item.cash_received === undefined ? null : Number(item.cash_received || 0),
      changeAmount: Number(item.change_amount || 0),
      total: Number(item.total || 0),
      items: (Array.isArray(item.items) ? item.items : []).map((row) => ({
        productId: Number((row as { product_id?: number; productId?: number }).product_id || (row as { productId?: number }).productId || 0),
        qty: Number((row as { qty?: number }).qty || 0),
        name: String((row as { name?: string; snapshot_name?: string }).name || (row as { snapshot_name?: string }).snapshot_name || ''),
        excludedIngredientIds: Array.isArray((row as { excluded_ingredients?: unknown[] }).excluded_ingredients)
          ? ((row as { excluded_ingredients?: unknown[] }).excluded_ingredients || []).map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)
          : [],
        extras: Array.isArray((row as { extras?: unknown[] }).extras)
          ? ((row as { extras?: unknown[] }).extras || []).map((extra) => {
              if (typeof extra === 'object' && extra !== null) {
                const entry = extra as { id?: number; name?: string; additional_price?: number; additionalPrice?: number }
                return {
                  id: entry.id ? Number(entry.id) : undefined,
                  name: String(entry.name || 'Extra'),
                  additionalPrice: Number(entry.additional_price ?? entry.additionalPrice ?? 0),
                }
              }
              return {
                name: 'Extra',
                additionalPrice: 0,
              }
            })
          : [],
      })),
      status: statusFromApi(String(item.status || 'pendiente')),
      employeeId: item.employee_id ? Number(item.employee_id) : null,
      driverId: item.driver_id ? Number(item.driver_id) : null,
      createdAt: new Date(String(item.created_at || Date.now())).getTime(),
      etaMin: Number(item.eta_min || 0),
    }))
  }

  const normalizeAuditLogs = (payload: Array<Record<string, unknown>>) => {
    auditLogs.value = payload.map((item) => ({
      id: Number(item.id),
      action: String(item.action || ''),
      entityType: String(item.entity_type || ''),
      entityId: item.entity_id === null || item.entity_id === undefined ? null : Number(item.entity_id),
      metadata: (item.metadata as Record<string, unknown> | null) || null,
      createdAt: new Date(String(item.created_at || Date.now())).getTime(),
      userName: String((item.user as { name?: string } | undefined)?.name || 'Sistema'),
    }))
  }

  const normalizeCustomerInsights = (payload: Array<Record<string, unknown>>) => {
    customerInsights.value = payload.map((item) => ({
      customerKey: String(item.customer_key || ''),
      customerName: String(item.customer_name || ''),
      lastAddress: String(item.last_address || ''),
      totalOrders: Number(item.total_orders || 0),
      totalSpent: Number(item.total_spent || 0),
      lastOrderAt: new Date(String(item.last_order_at || Date.now())).getTime(),
      isBlocked: Boolean(item.is_blocked),
      notes: item.notes ? String(item.notes) : null,
      rank: Number(item.rank || 0),
    }))
  }

  const initialize = async () => {
    if (!currentUser.value) {
      const pathMatch = window.location.pathname.match(/^\/tienda\/([^/]+)/i)
      if (pathMatch?.[1]) {
        setPublicTenantSlug(decodeURIComponent(pathMatch[1]))
      }
    }
    if (activeTenantSlug.value) {
      await fetchStorefront(activeTenantSlug.value)
    }
    try {
      const productPayload = await apiRequest<Array<Record<string, unknown>>>('/products')
      normalizeProducts(productPayload)
      const ingredientsPayload = await apiRequest<Array<Record<string, unknown>>>('/ingredients')
      normalizeIngredients(ingredientsPayload)
      const bundlesPayload = await apiRequest<Array<Record<string, unknown>>>('/bundles')
      normalizeBundles(bundlesPayload)
      const combosPayload = await apiRequest<Array<Record<string, unknown>>>('/combos')
      normalizeCombos(combosPayload)
      if (currentUser.value?.role === 'admin') {
        const dailyMenusPayload = await apiRequest<Array<Record<string, unknown>>>('/daily-menus', undefined, true)
        normalizeDailyMenus(dailyMenusPayload)
      }
    } catch {
      products.value = []
      ingredients.value = []
      combos.value = []
      bundles.value = []
      dailyMenus.value = []
    }
  }

  const login = async (emailRaw: string, passwordRaw: string, tenantSlugRaw = '') => {
    const tenantSlug = tenantSlugRaw.trim() || DEFAULT_TENANT_SLUG
    try {
      const payload = await apiRequest<{
        token: string
        user: SessionUser & { tenant_id?: number | null; tenant_slug?: string | null; tenant_name?: string | null }
      }>(
        '/auth/login',
        {
          method: 'POST',
          body: JSON.stringify({
            email: emailRaw.trim().toLowerCase(),
            password: passwordRaw.trim(),
            tenant_slug: tenantSlug,
          }),
        },
      )
      apiToken.value = payload.token
      currentUser.value = normalizeSessionUser(payload.user)
      if (currentUser.value.tenantSlug) {
        setPublicTenantSlug(currentUser.value.tenantSlug)
      }
      if (currentUser.value.tenantName) {
        storefrontName.value = currentUser.value.tenantName
      }
      authError.value = ''
      persistAuth()
      await refreshAll()
      return true
    } catch (error) {
      const message = error instanceof Error ? error.message : ''
      if (message.includes('api-error:422')) {
        authError.value = 'Completa email y password para iniciar sesion.'
      } else if (message.includes('api-error:403')) {
        authError.value = 'Usuario inactivo.'
      } else {
        authError.value = 'Credenciales invalidas.'
      }
      return false
    }
  }

  const loginWithGoogle = async (idTokenRaw: string, tenantSlugRaw = '') => {
    const tenantSlug = tenantSlugRaw.trim() || DEFAULT_TENANT_SLUG
    const idToken = idTokenRaw.trim()
    if (!idToken) {
      authError.value = 'Token de Google invalido.'
      return false
    }
    try {
      const payload = await apiRequest<{
        token: string
        user: SessionUser & { tenant_id?: number | null; tenant_slug?: string | null; tenant_name?: string | null }
      }>(
        '/auth/google',
        {
          method: 'POST',
          body: JSON.stringify({
            id_token: idToken,
            tenant_slug: tenantSlug,
          }),
        },
      )
      apiToken.value = payload.token
      currentUser.value = normalizeSessionUser(payload.user)
      if (currentUser.value.tenantSlug) {
        setPublicTenantSlug(currentUser.value.tenantSlug)
      }
      if (currentUser.value.tenantName) {
        storefrontName.value = currentUser.value.tenantName
      }
      authError.value = ''
      persistAuth()
      await refreshAll()
      return true
    } catch (error) {
      const message = error instanceof Error ? error.message : ''
      if (message.includes('otro negocio')) {
        authError.value = 'Tu cuenta de Google pertenece a otro negocio.'
      } else {
        authError.value = 'No se pudo iniciar sesion con Google.'
      }
      return false
    }
  }

  const logout = async () => {
    try {
      if (apiToken.value) {
        await apiRequest('/auth/logout', { method: 'POST' }, true)
      }
    } catch {
      // noop
    }
    currentUser.value = null
    publicTenantSlug.value = ''
    storefrontName.value = 'Dunamis Store'
    authError.value = ''
    apiToken.value = ''
    persistAuth()
  }

  const refreshAll = async () => {
    try {
      const [productPayload, rolePayload, orderPayload, ingredientsPayload, bundlesPayload, combosPayload] = await Promise.all([
        apiRequest<Array<Record<string, unknown>>>('/products'),
        apiRequest<Array<Record<string, unknown>>>('/roles'),
        currentUser.value ? apiRequest<Array<Record<string, unknown>>>('/orders', undefined, true) : Promise.resolve([]),
        apiRequest<Array<Record<string, unknown>>>('/ingredients'),
        apiRequest<Array<Record<string, unknown>>>('/bundles'),
        apiRequest<Array<Record<string, unknown>>>('/combos'),
      ])
      normalizeProducts(productPayload)
      normalizeIngredients(ingredientsPayload)
      normalizeBundles(bundlesPayload)
      normalizeCombos(combosPayload)
      roles.value = rolePayload.map((item) => ({
        id: Number(item.id),
        name: String(item.name || ''),
        label: String(item.label || ''),
      }))
      normalizeOrders(orderPayload)
    } catch {
      // noop
    }

    if (currentUser.value?.role === 'admin') {
      try {
        const [usersPayload, customersPayload, auditPayload, dailyMenusPayload] = await Promise.all([
          apiRequest<Array<Record<string, unknown>>>('/users', undefined, true),
          apiRequest<Array<Record<string, unknown>>>('/customers', undefined, true),
          apiRequest<Array<Record<string, unknown>>>('/audit-logs', undefined, true),
          apiRequest<Array<Record<string, unknown>>>('/daily-menus', undefined, true),
        ])
        normalizeUsers(usersPayload)
        normalizeCustomerInsights(customersPayload)
        normalizeAuditLogs(auditPayload)
        normalizeDailyMenus(dailyMenusPayload)
      } catch {
        users.value = []
        employees.value = []
        drivers.value = []
        customerInsights.value = []
        auditLogs.value = []
        dailyMenus.value = []
      }
    } else {
      // Non-admin roles do not have access to /users; keep their own shift context available.
      if (currentUser.value?.role === 'employee') {
        employees.value = [
          {
            id: currentUser.value.id,
            name: currentUser.value.name,
            email: currentUser.value.email,
            role: 'employee',
            active: true,
          },
        ]
        if (!shiftEmployeeId.value) {
          shiftEmployeeId.value = currentUser.value.id
        }
      } else {
        employees.value = []
        if (shiftEmployeeId.value && currentUser.value?.role !== 'admin') {
          shiftEmployeeId.value = null
        }
      }

      if (currentUser.value?.role === 'driver') {
        drivers.value = [
          {
            id: currentUser.value.id,
            name: currentUser.value.name,
            email: currentUser.value.email,
            role: 'driver',
            active: true,
          },
        ]
        if (!shiftDriverId.value) {
          shiftDriverId.value = currentUser.value.id
        }
      } else {
        drivers.value = []
        if (shiftDriverId.value && currentUser.value?.role !== 'admin') {
          shiftDriverId.value = null
        }
      }

      customerInsights.value = []
      auditLogs.value = []
      dailyMenus.value = []
    }
  }

  const reset = async () => {
    await refreshAll()
    pushFlash('Datos recargados desde backend.')
  }

  const addProduct = async (payload: {
    name: string
    price: number
    prepMin: number
    category?: string
    stockQuantity?: number
    minStockQuantity?: number
    imageFile?: File | null
    ingredients?: Array<{ ingredientId: number; isDefault?: boolean; isRemovable?: boolean; additionalPrice?: number }>
    extras?: Array<{ name: string; additionalPrice: number }>
  }) => {
    try {
      const hasFile = Boolean(payload.imageFile)
      if (hasFile) {
        const formData = new FormData()
        formData.append('_method', 'PUT')
        formData.append('name', payload.name.trim())
        formData.append('base_price', String(payload.price))
        formData.append('prep_min', String(payload.prepMin))
        formData.append('category', payload.category?.trim() || '')
        formData.append('stock_quantity', String(payload.stockQuantity ?? 0))
        formData.append('min_stock_quantity', String(payload.minStockQuantity ?? 0))
        formData.append('is_active', '1')
        if (payload.imageFile) {
          formData.append('image', payload.imageFile)
        }
        ;(payload.ingredients || []).forEach((ingredient, index) => {
          formData.append(`ingredients[${index}][ingredient_id]`, String(ingredient.ingredientId))
          formData.append(`ingredients[${index}][is_default]`, ingredient.isDefault === false ? '0' : '1')
          formData.append(`ingredients[${index}][is_removable]`, ingredient.isRemovable === false ? '0' : '1')
          formData.append(`ingredients[${index}][additional_price]`, String(ingredient.additionalPrice ?? 0))
        })
        ;(payload.extras || []).forEach((extra, index) => {
          formData.append(`extras[${index}][name]`, extra.name)
          formData.append(`extras[${index}][additional_price]`, String(extra.additionalPrice))
          formData.append(`extras[${index}][is_active]`, '1')
        })

        await apiRequest('/products', { method: 'POST', body: formData }, true)
      } else {
        await apiRequest(
          '/products',
          {
            method: 'POST',
            body: JSON.stringify({
              name: payload.name.trim(),
              base_price: payload.price,
              prep_min: payload.prepMin,
              category: payload.category?.trim() || null,
              stock_quantity: payload.stockQuantity ?? 0,
              min_stock_quantity: payload.minStockQuantity ?? 0,
              is_active: true,
              ingredients: (payload.ingredients || []).map((ingredient) => ({
                ingredient_id: ingredient.ingredientId,
                is_default: ingredient.isDefault ?? true,
                is_removable: ingredient.isRemovable ?? true,
                additional_price: ingredient.additionalPrice ?? 0,
              })),
              extras: (payload.extras || []).map((extra) => ({
                name: extra.name,
                additional_price: extra.additionalPrice,
                is_active: true,
              })),
            }),
          },
          true,
        )
      }
      await refreshAll()
      pushFlash('Producto agregado.')
      return true
    } catch {
      return false
    }
  }

  const updateProduct = async (
    id: number,
    payload: {
      name: string
      price: number
      prepMin: number
      category?: string
      stockQuantity?: number
      minStockQuantity?: number
      imageFile?: File | null
      ingredients?: Array<{ ingredientId: number; isDefault?: boolean; isRemovable?: boolean; additionalPrice?: number }>
      extras?: Array<{ id?: number; name: string; additionalPrice: number; isActive?: boolean }>
    },
  ) => {
    try {
      const hasFile = Boolean(payload.imageFile)
      if (hasFile) {
        const formData = new FormData()
        formData.append('name', payload.name.trim())
        formData.append('base_price', String(payload.price))
        formData.append('prep_min', String(payload.prepMin))
        formData.append('category', payload.category?.trim() || '')
        formData.append('stock_quantity', String(payload.stockQuantity ?? 0))
        formData.append('min_stock_quantity', String(payload.minStockQuantity ?? 0))
        if (payload.imageFile) {
          formData.append('image', payload.imageFile)
        }
        ;(payload.ingredients || []).forEach((ingredient, index) => {
          formData.append(`ingredients[${index}][ingredient_id]`, String(ingredient.ingredientId))
          formData.append(`ingredients[${index}][is_default]`, ingredient.isDefault === false ? '0' : '1')
          formData.append(`ingredients[${index}][is_removable]`, ingredient.isRemovable === false ? '0' : '1')
          formData.append(`ingredients[${index}][additional_price]`, String(ingredient.additionalPrice ?? 0))
        })
        ;(payload.extras || []).forEach((extra, index) => {
          if (extra.id) {
            formData.append(`extras[${index}][id]`, String(extra.id))
          }
          formData.append(`extras[${index}][name]`, extra.name)
          formData.append(`extras[${index}][additional_price]`, String(extra.additionalPrice))
          formData.append(`extras[${index}][is_active]`, extra.isActive === false ? '0' : '1')
        })

        await apiRequest(`/products/${id}`, { method: 'POST', body: formData }, true)
      } else {
        await apiRequest(
          `/products/${id}`,
          {
            method: 'PUT',
            body: JSON.stringify({
              name: payload.name.trim(),
              base_price: payload.price,
              prep_min: payload.prepMin,
              category: payload.category?.trim() || null,
              stock_quantity: payload.stockQuantity ?? 0,
              min_stock_quantity: payload.minStockQuantity ?? 0,
              ingredients: (payload.ingredients || []).map((ingredient) => ({
                ingredient_id: ingredient.ingredientId,
                is_default: ingredient.isDefault ?? true,
                is_removable: ingredient.isRemovable ?? true,
                additional_price: ingredient.additionalPrice ?? 0,
              })),
              extras: (payload.extras || []).map((extra) => ({
                id: extra.id,
                name: extra.name,
                additional_price: extra.additionalPrice,
                is_active: extra.isActive ?? true,
              })),
            }),
          },
          true,
        )
      }
      await refreshAll()
      pushFlash('Producto actualizado.')
      return true
    } catch {
      return false
    }
  }

  const addEmployee = async (nameRaw: string, email = '', password = 'demo1234', roleId?: number) => {
    try {
      const employeeRole = roles.value.find((role) => role.name === 'employee')
      await apiRequest(
        '/users',
        {
          method: 'POST',
          body: JSON.stringify({
            name: nameRaw.trim(),
            email: email || `${nameRaw.trim().toLowerCase().replace(/\s+/g, '')}@delivery.local`,
            password,
            role_id: roleId || employeeRole?.id,
            is_active: true,
          }),
        },
        true,
      )
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const addDriver = async (nameRaw: string, email = '', password = 'demo1234', roleId?: number) => {
    try {
      const driverRole = roles.value.find((role) => role.name === 'driver')
      await apiRequest(
        '/users',
        {
          method: 'POST',
          body: JSON.stringify({
            name: nameRaw.trim(),
            email: email || `${nameRaw.trim().toLowerCase().replace(/\s+/g, '')}@delivery.local`,
            password,
            role_id: roleId || driverRole?.id,
            is_active: true,
          }),
        },
        true,
      )
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const createUser = async (payload: { name: string; email: string; password: string; roleId: number; isActive?: boolean }) => {
    await apiRequest(
      '/users',
      {
        method: 'POST',
        body: JSON.stringify({
          name: payload.name,
          email: payload.email,
          password: payload.password,
          role_id: payload.roleId,
          is_active: payload.isActive ?? true,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const createRole = async (payload: { name: string; label: string }) => {
    await apiRequest(
      '/roles',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
      true,
    )
    await refreshAll()
  }

  const createIngredient = async (payload: { name: string; additionalPrice?: number; stockQuantity?: number }) => {
    await apiRequest(
      '/ingredients',
      {
        method: 'POST',
        body: JSON.stringify({
          name: payload.name,
          additional_price: payload.additionalPrice || 0,
          stock_quantity: payload.stockQuantity || 0,
          is_active: true,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const deactivateIngredientGlobally = async (ingredientId: number) => {
    await apiRequest(
      `/ingredients/${ingredientId}/deactivate-global`,
      {
        method: 'POST',
      },
      true,
    )
    await refreshAll()
  }

  const createCombo = async (payload: {
    name: string
    description?: string
    basePrice: number
    imageUrl?: string
    imageFile?: File | null
    items: Array<{ productId: number; quantity: number }>
  }) => {
    try {
      if (payload.imageFile) {
        const formData = new FormData()
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('base_price', String(payload.basePrice))
        formData.append('is_active', '1')
        formData.append('image', payload.imageFile)
        payload.items.forEach((item, index) => {
          formData.append(`products[${index}][product_id]`, String(item.productId))
          formData.append(`products[${index}][quantity]`, String(item.quantity))
        })

        await apiRequest('/combos', { method: 'POST', body: formData }, true)
      } else {
        await apiRequest(
          '/combos',
          {
            method: 'POST',
            body: JSON.stringify({
              name: payload.name,
              description: payload.description || null,
              base_price: payload.basePrice,
              image_url: payload.imageUrl || null,
              is_active: true,
              products: payload.items.map((item) => ({
                product_id: item.productId,
                quantity: item.quantity,
              })),
            }),
          },
          true,
        )
      }
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const createBundle = async (payload: {
    name: string
    description?: string
    pricingMode: 'fixed_price' | 'discount_percentage'
    fixedPrice?: number
    discountPercentage?: number
    imageFile?: File | null
    items: Array<{ productId: number; quantity: number }>
  }) => {
    try {
      const formData = new FormData()
      formData.append('name', payload.name)
      formData.append('description', payload.description || '')
      formData.append('pricing_mode', payload.pricingMode)
      formData.append('fixed_price', String(payload.fixedPrice ?? 0))
      formData.append('discount_percentage', String(payload.discountPercentage ?? 0))
      formData.append('is_active', '1')
      if (payload.imageFile) {
        formData.append('image', payload.imageFile)
      }
      payload.items.forEach((item, index) => {
        formData.append(`products[${index}][product_id]`, String(item.productId))
        formData.append(`products[${index}][quantity]`, String(item.quantity))
      })

      await apiRequest('/bundles', { method: 'POST', body: formData }, true)
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const updateCombo = async (
    id: number,
    payload: {
      name: string
      description?: string
      basePrice: number
      imageUrl?: string
      imageFile?: File | null
      enabled?: boolean
      items: Array<{ productId: number; quantity: number }>
    },
  ) => {
    try {
      if (payload.imageFile) {
        const formData = new FormData()
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('base_price', String(payload.basePrice))
        formData.append('is_active', payload.enabled === false ? '0' : '1')
        formData.append('image', payload.imageFile)
        payload.items.forEach((item, index) => {
          formData.append(`products[${index}][product_id]`, String(item.productId))
          formData.append(`products[${index}][quantity]`, String(item.quantity))
        })
        await apiRequest(`/combos/${id}`, { method: 'PUT', body: formData }, true)
      } else {
        await apiRequest(
          `/combos/${id}`,
          {
            method: 'PUT',
            body: JSON.stringify({
              name: payload.name,
              description: payload.description || null,
              base_price: payload.basePrice,
              image_url: payload.imageUrl || null,
              is_active: payload.enabled ?? true,
              products: payload.items.map((item) => ({
                product_id: item.productId,
                quantity: item.quantity,
              })),
            }),
          },
          true,
        )
      }
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const deleteCombo = async (id: number) => {
    try {
      await apiRequest(`/combos/${id}`, { method: 'DELETE' }, true)
      await refreshAll()
      return true
    } catch {
      return false
    }
  }

  const createDailyMenu = async (payload: {
    name: string
    description?: string
    imageUrl?: string
    isActive?: boolean
    slot?: 'all_day' | 'lunch' | 'dinner'
    weekdays?: number[]
    activeFrom?: string | null
    activeTo?: string | null
    priority?: number
  }) => {
    await apiRequest(
      '/daily-menus',
      {
        method: 'POST',
        body: JSON.stringify({
          name: payload.name,
          description: payload.description || null,
          image_url: payload.imageUrl || null,
          is_active: payload.isActive ?? true,
          slot: payload.slot || 'all_day',
          weekdays: payload.weekdays && payload.weekdays.length ? payload.weekdays : null,
          active_from: payload.activeFrom || null,
          active_to: payload.activeTo || null,
          priority: payload.priority ?? 0,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const updateDailyMenu = async (
    id: number,
    payload: {
      name: string
      description?: string
      imageUrl?: string
      isActive?: boolean
      slot?: 'all_day' | 'lunch' | 'dinner'
      weekdays?: number[]
      activeFrom?: string | null
      activeTo?: string | null
      priority?: number
    },
  ) => {
    await apiRequest(
      `/daily-menus/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify({
          name: payload.name,
          description: payload.description || null,
          image_url: payload.imageUrl || null,
          is_active: payload.isActive ?? true,
          slot: payload.slot || 'all_day',
          weekdays: payload.weekdays && payload.weekdays.length ? payload.weekdays : null,
          active_from: payload.activeFrom || null,
          active_to: payload.activeTo || null,
          priority: payload.priority ?? 0,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const deleteDailyMenu = async (id: number) => {
    await apiRequest(`/daily-menus/${id}`, { method: 'DELETE' }, true)
    await refreshAll()
  }

  const upsertDailyMenuItem = async (
    dailyMenuId: number,
    payload: {
      itemType: 'product' | 'combo'
      itemId: number
      promoPrice?: number | null
      sortOrder?: number
    },
  ) => {
    await apiRequest(
      `/daily-menus/${dailyMenuId}/items`,
      {
        method: 'POST',
        body: JSON.stringify({
          item_type: payload.itemType,
          item_id: payload.itemId,
          promo_price: payload.promoPrice ?? null,
          sort_order: payload.sortOrder ?? 0,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const removeDailyMenuItem = async (dailyMenuId: number, itemId: number) => {
    await apiRequest(`/daily-menus/${dailyMenuId}/items/${itemId}`, { method: 'DELETE' }, true)
    await refreshAll()
  }

  const toggleProduct = async (id: number) => {
    const product = getProduct(id)
    if (!product) return
    try {
      await apiRequest(
        `/products/${id}`,
        {
          method: 'PUT',
          body: JSON.stringify({
            is_active: !product.enabled,
          }),
        },
        true,
      )
      await refreshAll()
    } catch {
      // noop
    }
  }

  const duplicateProduct = async (productId: number) => {
    const detail = await apiRequest<{
      product?: {
        name?: string
        description?: string | null
        category?: string | null
        base_price?: number
        prep_min?: number
        stock_quantity?: number
        min_stock_quantity?: number
        ingredients?: Array<{ id: number }>
        extras?: Array<{ name?: string; additional_price?: number }>
      }
    }>(`/products/${productId}`)

    const p = detail.product
    if (!p) return

    await apiRequest(
      '/products',
      {
        method: 'POST',
        body: JSON.stringify({
          name: `${String(p.name || 'Producto')} (copia)`,
          description: p.description || null,
          category: p.category || null,
          base_price: Number(p.base_price || 0),
          prep_min: Number(p.prep_min || 15),
          stock_quantity: Number(p.stock_quantity || 0),
          min_stock_quantity: Number(p.min_stock_quantity || 0),
          is_active: true,
          ingredient_ids: (p.ingredients || []).map((ingredient) => Number(ingredient.id)),
          extras: (p.extras || []).map((extra) => ({
            name: String(extra.name || 'Extra'),
            additional_price: Number(extra.additional_price || 0),
            is_active: true,
          })),
        }),
      },
      true,
    )
    await refreshAll()
    pushFlash('Producto duplicado.')
  }

  const bulkUpdateProductPrices = async (payload: {
    category?: string
    productIds?: number[]
    mode: 'percentage' | 'fixed_delta'
    value: number
    roundTo?: 1 | 5 | 10 | 50 | 100
  }) => {
    await apiRequest(
      '/products/bulk/price',
      {
        method: 'POST',
        body: JSON.stringify({
          category: payload.category || null,
          product_ids: payload.productIds || [],
          mode: payload.mode,
          value: payload.value,
          round_to: payload.roundTo || 1,
        }),
      },
      true,
    )
    await refreshAll()
    pushFlash('Accion masiva aplicada.')
  }

  const setCustomerBlocked = async (payload: { customerKey: string; customerName?: string; blocked: boolean; notes?: string }) => {
    await apiRequest(
      payload.blocked ? '/customers/block' : '/customers/unblock',
      {
        method: 'POST',
        body: JSON.stringify({
          customer_key: payload.customerKey,
          display_name: payload.customerName,
          notes: payload.notes,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const updateUser = async (payload: { id: number; roleId?: number; isActive?: boolean; name?: string; email?: string }) => {
    await apiRequest(
      `/users/${payload.id}`,
      {
        method: 'PUT',
        body: JSON.stringify({
          role_id: payload.roleId,
          is_active: payload.isActive,
          name: payload.name,
          email: payload.email,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const toggleEmployee = async (id: number) => {
    const employee = getEmployee(id)
    if (!employee) return
    await updateUser({ id, isActive: !employee.active })
  }

  const toggleDriver = async (id: number) => {
    const driver = getDriver(id)
    if (!driver) return
    await updateUser({ id, isActive: !driver.active })
  }

  const assignEmployee = async (orderId: number, employeeId: number | null) => {
    const order = findOrder(orderId)
    if (!order) return
    order.employeeId = employeeId
  }

  const assignDriver = async (orderId: number, driverId: number | null) => {
    const order = findOrder(orderId)
    if (!order) return
    order.driverId = driverId
  }

  const createOrder = (payload: {
    customer: string
    address: string
    items: OrderItem[]
    paymentMethod?: PaymentMethod
    paymentStatus?: PaymentStatus
    cashReceived?: number | null
    changeAmount?: number
  }) => {
    const customer = payload.customer.trim()
    const address = payload.address.trim()
    const items = payload.items.filter((item) => item.qty > 0)
    if (!customer || !address || !items.length) return false

    orders.value.unshift({
      id: Date.now(),
      customer,
      address,
      paymentMethod: payload.paymentMethod || 'cash',
      paymentStatus: payload.paymentStatus || 'pending',
      cashReceived: payload.cashReceived ?? null,
      changeAmount: payload.changeAmount ?? 0,
      total: items.reduce((acc, item) => acc + ((getProduct(item.productId)?.price || 0) * item.qty), 0),
      items,
      status: 'received',
      employeeId: null,
      driverId: null,
      createdAt: Date.now(),
      etaMin: 32,
    })
    clientLookup.value = customer
    pushFlash('Pedido creado correctamente.')
    return true
  }

  const updateOrderStatus = async (orderId: number, status: OrderStatus, employeeId?: number | null, driverId?: number | null) => {
    try {
      await apiRequest(
        `/orders/${orderId}/status`,
        {
          method: 'PUT',
          body: JSON.stringify({
            status: statusToApi(status),
            employee_id: employeeId,
            driver_id: driverId,
          }),
        },
        true,
      )
      await refreshAll()
    } catch {
      const order = findOrder(orderId)
      if (!order) return
      order.status = status
      if (employeeId !== undefined) order.employeeId = employeeId
      if (driverId !== undefined) order.driverId = driverId
    }
  }

  const startPreparing = async (orderId: number, employeeId: number | null) => {
    if (!employeeId) return
    await updateOrderStatus(orderId, 'preparing', employeeId)
    pushFlash(`Pedido #${orderId} tomado por cocina.`)
  }

  const markReady = async (orderId: number) => {
    const order = findOrder(orderId)
    await updateOrderStatus(orderId, 'ready', order?.employeeId || undefined)
    pushFlash(`Pedido #${orderId} listo para despacho.`)
  }

  const takeRouteReadyOrders = async () => {
    if (!shiftDriverId.value) return
    const readyOrders = orders.value.filter((order) => order.status === 'ready')
    for (const order of readyOrders) {
      await updateOrderStatus(order.id, 'onroute', order.employeeId || undefined, shiftDriverId.value)
    }
    if (readyOrders.length) pushFlash(`Ruta cargada con ${readyOrders.length} pedido(s).`)
  }

  const leaveForDelivery = async (orderId: number, driverId: number | null) => {
    if (!driverId) return
    const order = findOrder(orderId)
    await updateOrderStatus(orderId, 'onroute', order?.employeeId || undefined, driverId)
  }

  const markDelivered = async (orderId: number) => {
    const order = findOrder(orderId)
    await updateOrderStatus(orderId, 'delivered', order?.employeeId || undefined, order?.driverId || undefined)
    pushFlash(`Pedido #${orderId} entregado.`)
  }

  const updateOrderPayment = async (orderId: number, paymentStatus: PaymentStatus, cashReceived?: number | null) => {
    await apiRequest(
      `/orders/${orderId}/payment`,
      {
        method: 'PUT',
        body: JSON.stringify({
          payment_status: paymentStatus,
          cash_received: cashReceived,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const updateOrderDetails = async (payload: OrderUpdatePayload) => {
    await apiRequest(
      `/orders/${payload.id}`,
      {
        method: 'PUT',
        body: JSON.stringify({
          customer: payload.customer,
          address: payload.address,
          status: payload.status ? statusToApi(payload.status) : undefined,
          employee_id: payload.employeeId,
          driver_id: payload.driverId,
          payment_method: payload.paymentMethod,
          payment_status: payload.paymentStatus,
          cash_received: payload.cashReceived,
          eta_min: payload.etaMin,
        }),
      },
      true,
    )
    await refreshAll()
  }

  const tickEta = () => {
    orders.value.forEach((order) => {
      if (order.status !== 'delivered' && order.etaMin > 0) {
        order.etaMin -= 1
      }
    })
  }

  const setRealtimeStatus = (payload: { connected: boolean; error?: string }) => {
    realtimeConnected.value = payload.connected
    realtimeError.value = payload.error || ''
  }

  const setOrdersFromRealtime = (incoming: Order[]) => {
    orders.value = incoming
  }

  return {
    products,
    employees,
    drivers,
    users,
    roles,
    orders,
    shiftEmployeeId,
    shiftDriverId,
    clientLookup,
    flashMessage,
    currentUser,
    publicTenantSlug,
    storefrontName,
    authError,
    isAuthenticated,
    activeTenantSlug,
    activeStorefrontName,
    allowedRouteByRole,
    activeOrders,
    kitchenOrders,
    routeOrders,
    avgEta,
    activeProducts,
    activeEmployees,
    activeDrivers,
    sortedOrders,
    clientOrders,
    realtimeConnected,
    realtimeError,
    ingredients,
    combos,
    bundles,
    dailyMenus,
    auditLogs,
    customerInsights,
    getProduct,
    getIngredient,
    getEmployee,
    getDriver,
    initialize,
    initializeAuth,
    setPublicTenantSlug,
    fetchStorefront,
    login,
    loginWithGoogle,
    logout,
    persist: persistAuth,
    reset,
    refreshAll,
    addProduct,
    updateProduct,
    addEmployee,
    addDriver,
    createUser,
    createRole,
    createIngredient,
    deactivateIngredientGlobally,
    createCombo,
    updateCombo,
    deleteCombo,
    createDailyMenu,
    updateDailyMenu,
    deleteDailyMenu,
    upsertDailyMenuItem,
    removeDailyMenuItem,
    createBundle,
    updateUser,
    toggleProduct,
    duplicateProduct,
    bulkUpdateProductPrices,
    setCustomerBlocked,
    toggleEmployee,
    toggleDriver,
    assignEmployee,
    assignDriver,
    createOrder,
    startPreparing,
    markReady,
    takeRouteReadyOrders,
    leaveForDelivery,
    markDelivered,
    updateOrderPayment,
    updateOrderDetails,
    tickEta,
    setRealtimeStatus,
    setOrdersFromRealtime,
  }
})
