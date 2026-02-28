<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Check, ChefHat, CupSoda, Minus, Pizza, Plus, Sandwich, Search, ShoppingCart, Sparkles, Star, Ticket, Vegan, X } from 'lucide-vue-next'
import { useDeliveryStore, type Product } from '../stores/delivery'
import AppButton from '../components/common/AppButton.vue'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import { resolveAssetUrl } from '../utils/media'
import ProfileMenu from '../components/common/ProfileMenu.vue'

interface CartLine {
  type: 'product' | 'combo'
  productId?: number
  comboId?: number
  name: string
  imageUrl?: string | null
  qty: number
  unitPrice: number
  customLabel?: string
  comboSubItems?: Array<{
    product_id: number
    excluded_ingredients?: number[]
    extras?: number[]
  }>
}

interface ComboView {
  id: number
  name: string
  imageUrl: string
  products: Array<{
    id: number
    name: string
    ingredients: Array<{ id: number; name: string; isDefault: boolean; isRemovable: boolean }>
    extras: Array<{ id: number; name: string; price: number }>
  }>
  price: number
}

interface OfferSlide {
  id: string
  title: string
  subtitle: string
  badge: string
  imageUrl: string | null
  priceLabel?: string
  type: 'daily' | 'combo'
  combo?: ComboView
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
  products?: Array<{ id: number; name: string; ingredients?: BackendIngredient[]; extras?: BackendExtra[] }>
}

interface ClientCoupon {
  id: string
  code: string
  kind: 'percentage' | 'fixed' | 'free_shipping'
  value: number
  minOrder: number
  totalUsesLimit: number
  usesPerClient: number
  usedCount: number
  expiresAt?: string
  active: boolean
}

const store = useDeliveryStore()
const route = useRoute()
const router = useRouter()
useOrdersRealtime()

const query = ref('')
const activeCategory = ref('all')
const isCartOpen = ref(false)
const cart = ref<CartLine[]>([])
const checkoutDone = ref(false)
const authPopupOpen = ref(false)
const authPopupError = ref('')
const googleAuthLoading = ref(false)
const googleAuthReady = ref(false)
const googleButtonHost = ref<HTMLDivElement | null>(null)
const recentlyAddedProducts = ref<Record<number, boolean>>({})
const cartPulse = ref(false)
const selectedProduct = ref<Product | null>(null)
const selectedCombo = ref<ComboView | null>(null)
const selectedConfigState = ref<ProductConfig | null>(null)
const detailLoading = ref(false)
const remoteCombos = ref<ComboView[]>([])
const apiBaseUrl = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'
const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID || ''
const orderSyncMessage = ref('')
const headerScrolled = ref(false)
const dailyBannerRef = ref<HTMLElement | null>(null)
const dailyFabOpen = ref(false)
const addFeedbackTimers = new Map<number, number>()
let cartPulseTimer = 0
let dailyFabTimer = 0
let googleScriptPromise: Promise<void> | null = null
const comboCustomization = reactive({
  removedByProduct: {} as Record<number, number[]>,
  extrasByProduct: {} as Record<number, number[]>,
})
const comboTouchedProducts = ref<Record<number, boolean>>({})

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
const coupons = ref<ClientCoupon[]>([])
const couponCode = ref('')
const appliedCoupon = ref<ClientCoupon | null>(null)
const couponFeedback = ref('')
const couponFeedbackTone = ref<'success' | 'error'>('success')
const couponPricePulse = ref(false)
const assistanceMessage = ref('')
const salonSectionOpen = ref<Record<string, boolean>>({})
const comboSuggestionCategory = ['postre', 'helado', 'dessert']
const bottomSheetTranslateY = ref(0)
const bottomSheetStartY = ref(0)
const bottomSheetDragging = ref(false)
const bottomSheetTarget = ref<'product' | 'combo' | null>(null)
const imageLoadedMap = ref<Record<string, boolean>>({})

const normalizeCategory = (value?: string | null) => (value || '').trim() || 'Sin categoria'

const categories = computed(() => {
  const values = new Set(store.activeProducts.map((product) => normalizeCategory(product.category)))
  return ['all', ...Array.from(values).sort((a, b) => a.localeCompare(b))]
})

const tableNumber = computed(() => {
  const raw = route.query.mesa
  const value = Array.isArray(raw) ? raw[0] : raw
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
})

const isSalonMode = computed(() => tableNumber.value !== null)
const salonTableLabel = computed(() => (tableNumber.value ? `Mesa ${String(tableNumber.value).padStart(2, '0')}` : 'Mesa'))
const currentTenantSlug = computed(() => {
  const slugFromRoute = String(route.params.tenantSlug || '').trim()
  if (slugFromRoute) return slugFromRoute
  return String(store.activeTenantSlug || '').trim()
})
const storefrontLabel = computed(() => store.activeStorefrontName || 'Dunamis Store')
const salonHeaderTitle = computed(() => (isSalonMode.value ? `${salonTableLabel.value} - ${storefrontLabel.value}` : storefrontLabel.value))
const storefrontInitials = computed(() =>
  storefrontLabel.value
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('') || 'DS',
)

const comboMatchesCategory = (combo: ComboView) => {
  if (activeCategory.value === 'all') return true
  return combo.products.some((comboProduct) => normalizeCategory(store.getProduct(comboProduct.id)?.category) === activeCategory.value)
}

const combosOfDay = computed<ComboView[]>(() => remoteCombos.value.filter((combo) => comboMatchesCategory(combo)))
const featuredCombo = computed<ComboView | null>(() => combosOfDay.value[0] || null)
const combosStrip = computed<ComboView[]>(() => combosOfDay.value.slice(1))
const hasAnyCombo = computed(() => remoteCombos.value.length > 0)
const offerSlides = computed<OfferSlide[]>(() => {
  const slides: OfferSlide[] = []
  if (dailyHighlight.value) {
    slides.push({
      id: 'daily-highlight',
      title: dailyHighlight.value.title,
      subtitle: dailyHighlight.value.subtitle,
      badge: 'Especial del dia',
      imageUrl: dailyHighlight.value.imageUrl,
      type: 'daily',
    })
  }
  if (featuredCombo.value) {
    slides.push({
      id: `featured-combo-${featuredCombo.value.id}`,
      title: featuredCombo.value.name,
      subtitle: 'Combo recomendado',
      badge: 'Combo Pro',
      imageUrl: featuredCombo.value.imageUrl,
      priceLabel: `$${featuredCombo.value.price.toLocaleString('es-AR')}`,
      type: 'combo',
      combo: featuredCombo.value,
    })
  }
  for (const combo of combosStrip.value.slice(0, 5)) {
    slides.push({
      id: `combo-slide-${combo.id}`,
      title: combo.name,
      subtitle: 'Toca para personalizar',
      badge: 'Promo',
      imageUrl: combo.imageUrl,
      priceLabel: `$${combo.price.toLocaleString('es-AR')}`,
      type: 'combo',
      combo,
    })
  }
  return slides
})

const filteredProducts = computed(() => {
  const q = query.value.trim().toLowerCase()
  return store.activeProducts.filter((product) => {
    if (!isSalonMode.value && activeCategory.value !== 'all' && normalizeCategory(product.category) !== activeCategory.value) return false
    if (!q) return true
    return product.name.toLowerCase().includes(q)
  })
})

const salonSections = computed(() => {
  const map = new Map<string, Product[]>()
  for (const product of filteredProducts.value) {
    const category = normalizeCategory(product.category)
    const current = map.get(category) || []
    current.push(product)
    map.set(category, current)
  }
  return Array.from(map.entries())
    .sort((a, b) => a[0].localeCompare(b[0]))
    .map(([category, products]) => ({ category, products }))
})

const activeDailyPromoByProduct = computed(() => {
  const map = new Map<number, number>()
  for (const menu of store.dailyMenus) {
    if (!menu.isActive) continue
    for (const item of menu.items) {
      if (item.itemType !== 'product' || item.promoPrice === null || item.promoPrice === undefined) continue
      map.set(item.itemId, Number(item.promoPrice))
    }
  }
  return map
})

const productPromoPrice = (productId: number) => activeDailyPromoByProduct.value.get(productId) ?? null
const productDisplayPrice = (product: Product) => Number(productPromoPrice(product.id) ?? product.price)
const cartCount = computed(() => cart.value.reduce((acc, line) => acc + line.qty, 0))

const cartView = computed(() =>
  cart.value.map((line) => ({
    ...line,
    subtotal: line.unitPrice * line.qty,
  })),
)

const total = computed(() => cartView.value.reduce((acc, line) => acc + line.subtotal, 0))
const totalLabel = computed(() => `$${Number(total.value || 0).toLocaleString('es-AR')}`)
const couponDiscount = computed(() => {
  const coupon = appliedCoupon.value
  if (!coupon) return 0
  const currentTotal = Number(total.value || 0)
  if (currentTotal < Number(coupon.minOrder || 0)) return 0
  if (coupon.kind === 'percentage') {
    return Math.min(currentTotal, (currentTotal * Number(coupon.value || 0)) / 100)
  }
  if (coupon.kind === 'fixed') {
    return Math.min(currentTotal, Number(coupon.value || 0))
  }
  return 0
})
const totalAfterCoupon = computed(() => Math.max(0, Number(total.value || 0) - couponDiscount.value))
const totalAfterCouponLabel = computed(() => `$${Number(totalAfterCoupon.value || 0).toLocaleString('es-AR')}`)
const savingsLabel = computed(() => `$${Number(couponDiscount.value || 0).toLocaleString('es-AR')}`)
const FREE_SHIPPING_GOAL = 30000
const freeShippingProgress = computed(() => Math.min(100, Math.round((Number(total.value || 0) / FREE_SHIPPING_GOAL) * 100)))
const freeShippingRemaining = computed(() => Math.max(0, FREE_SHIPPING_GOAL - Number(total.value || 0)))
const dailyHighlight = computed(() => {
  const now = new Date()
  const activeMenus = store.dailyMenus
    .filter((menu) => {
      if (!menu.isActive) return false
      if (menu.activeFrom) {
        const from = new Date(menu.activeFrom)
        if (!Number.isNaN(from.getTime()) && from > now) return false
      }
      if (menu.activeTo) {
        const to = new Date(menu.activeTo)
        if (!Number.isNaN(to.getTime()) && to < now) return false
      }
      return true
    })
    .sort((a, b) => b.priority - a.priority)
  const menu = activeMenus[0]
  if (!menu) return null

  const firstItem = menu.items[0]
  const imageUrl = firstItem?.imageUrl || menu.imageUrl || null
  const title = menu.name || (firstItem?.name ? `Menu del dia: ${firstItem.name}` : 'Menu del dia')
  const subtitle = menu.description || (firstItem?.name ? `${firstItem.name}` : 'Seleccion especial del chef')

  let remainingText = 'Solo por hoy'
  if (menu.activeTo) {
    const to = new Date(menu.activeTo)
    if (!Number.isNaN(to.getTime()) && to > now) {
      const diffMs = to.getTime() - now.getTime()
      const hours = Math.floor(diffMs / 3600000)
      const minutes = Math.floor((diffMs % 3600000) / 60000)
      if (hours > 0) {
        remainingText = `Quedan ${hours}h ${String(minutes).padStart(2, '0')}m`
      } else {
        remainingText = `Quedan ${Math.max(1, minutes)} min`
      }
    }
  }

  return {
    title,
    subtitle,
    imageUrl,
    remainingText,
    availableText: `${Math.max(1, menu.items.length * 5)} disponibles`,
  }
})
const showDailyFab = computed(() => headerScrolled.value && Boolean(dailyHighlight.value) && !isCartOpen.value)

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

const categoryIcon = (category: string) => {
  const normalized = category.toLowerCase()
  if (category === 'all') return ChefHat
  if (normalized.includes('pizza')) return Pizza
  if (normalized.includes('beb') || normalized.includes('drink')) return CupSoda
  if (normalized.includes('veg') || normalized.includes('ensalada')) return Vegan
  return Sandwich
}

const openOfferSlide = (slide: OfferSlide) => {
  if (slide.type === 'combo' && slide.combo) {
    openComboDetail(slide.combo)
    return
  }
  focusDailyBanner()
}

const imageKeyForProduct = (product: Product) => `product-${product.id}`
const imageKeyForCombo = (combo: ComboView) => `combo-${combo.id}`
const imageKeyForOffer = (slide: OfferSlide) => `offer-${slide.id}`
const isImageLoaded = (key: string) => Boolean(imageLoadedMap.value[key])
const onImageLoaded = (key: string) => {
  imageLoadedMap.value = { ...imageLoadedMap.value, [key]: true }
}

const initials = (name: string) =>
  name
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('')

const addToCart = (line: CartLine) => {
  const existing = cart.value.find((item) => {
    if (item.type !== line.type) return false
    if (item.type === 'product') {
      return item.productId === line.productId && item.unitPrice === line.unitPrice && item.customLabel === line.customLabel
    }
    return item.comboId === line.comboId && item.unitPrice === line.unitPrice && item.customLabel === line.customLabel
  })

  if (existing) {
    existing.qty += 1
  } else {
    cart.value.push({ ...line, qty: 1 })
  }
  cartPulse.value = true
  if (cartPulseTimer) window.clearTimeout(cartPulseTimer)
  cartPulseTimer = window.setTimeout(() => {
    cartPulse.value = false
    cartPulseTimer = 0
  }, 280)
}

const markRecentlyAdded = (productId: number) => {
  recentlyAddedProducts.value = { ...recentlyAddedProducts.value, [productId]: true }
  const previous = addFeedbackTimers.get(productId)
  if (previous) window.clearTimeout(previous)
  const timeoutId = window.setTimeout(() => {
    const next = { ...recentlyAddedProducts.value }
    delete next[productId]
    recentlyAddedProducts.value = next
    addFeedbackTimers.delete(productId)
  }, 700)
  addFeedbackTimers.set(productId, timeoutId)
}

const quickAddProduct = (product: Product) => {
  addToCart({
    type: 'product',
    productId: product.id,
    name: product.name,
    imageUrl: product.imageUrl,
    unitPrice: productDisplayPrice(product),
    qty: 1,
  })
  markRecentlyAdded(product.id)
}

const focusDailyBanner = () => {
  if (!dailyHighlight.value) return
  dailyFabOpen.value = true
  if (dailyFabTimer) window.clearTimeout(dailyFabTimer)
  dailyFabTimer = window.setTimeout(() => {
    dailyFabOpen.value = false
    dailyFabTimer = 0
  }, 2400)
  dailyBannerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const toggleSalonSection = (category: string) => {
  salonSectionOpen.value = {
    ...salonSectionOpen.value,
    [category]: !salonSectionOpen.value[category],
  }
}

const isSalonSectionOpen = (category: string, index: number) => {
  if (typeof salonSectionOpen.value[category] === 'boolean') return salonSectionOpen.value[category]
  return index === 0
}

const buildApiUrl = (path: string) => {
  const base = `${apiBaseUrl.replace(/\/$/, '')}${path}`
  const slug = currentTenantSlug.value
  if (!slug) return base
  const separator = base.includes('?') ? '&' : '?'
  return `${base}${separator}tenant_slug=${encodeURIComponent(slug)}`
}

const removeFromCart = (line: CartLine) => {
  const target = cart.value.find(
    (item) =>
      item.type === line.type &&
      item.productId === line.productId &&
      item.comboId === line.comboId &&
      item.unitPrice === line.unitPrice &&
      item.customLabel === line.customLabel,
  )
  if (!target) return
  target.qty -= 1
  if (target.qty <= 0) {
    cart.value = cart.value.filter(
      (item) =>
        !(
          item.type === target.type &&
          item.productId === target.productId &&
          item.comboId === target.comboId &&
          item.unitPrice === target.unitPrice &&
          item.customLabel === target.customLabel
        ),
    )
  }
}

const isCouponExpired = (coupon: ClientCoupon) => {
  if (!coupon.expiresAt) return false
  const end = new Date(`${coupon.expiresAt}T23:59:59`).getTime()
  return Date.now() > end
}

const applyCoupon = () => {
  const code = couponCode.value.trim().toUpperCase()
  if (!code) {
    couponFeedback.value = 'Ingresa un codigo de cupon.'
    couponFeedbackTone.value = 'error'
    return
  }
  const coupon = coupons.value.find((item) => item.code.toUpperCase() === code)
  if (!coupon || !coupon.active || isCouponExpired(coupon)) {
    couponFeedback.value = 'Cupon invalido o expirado.'
    couponFeedbackTone.value = 'error'
    return
  }
  if (Number(coupon.usedCount || 0) >= Number(coupon.totalUsesLimit || 0)) {
    couponFeedback.value = 'Este cupon ya alcanzo su limite de usos.'
    couponFeedbackTone.value = 'error'
    return
  }
  if (Number(total.value || 0) < Number(coupon.minOrder || 0)) {
    couponFeedback.value = `Necesitas un minimo de $${Number(coupon.minOrder || 0).toLocaleString('es-AR')}.`
    couponFeedbackTone.value = 'error'
    return
  }
  appliedCoupon.value = coupon
  couponFeedback.value = `Cupon ${coupon.code} aplicado.`
  couponFeedbackTone.value = 'success'
  couponPricePulse.value = true
  window.setTimeout(() => {
    couponPricePulse.value = false
  }, 520)
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

const toggleIngredient = (ingredient: string) => {
  if (customization.removedIngredients.includes(ingredient)) {
    customization.removedIngredients = customization.removedIngredients.filter((value) => value !== ingredient)
    return
  }
  customization.removedIngredients.push(ingredient)
}

const isIngredientIncluded = (ingredient: string) => !customization.removedIngredients.includes(ingredient)

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
  addToCart({
    type: 'product',
    productId: selectedProduct.value.id,
    name: selectedProduct.value.name,
    imageUrl: selectedProduct.value.imageUrl,
    unitPrice: detailTotal.value,
    qty: 1,
    customLabel: label || undefined,
  })
  selectedProduct.value = null
}

const openComboDetail = async (comboBase: ComboView) => {
  comboCustomization.removedByProduct = {}
  comboCustomization.extrasByProduct = {}
  comboTouchedProducts.value = {}
  selectedCombo.value = {
    ...comboBase,
    products: comboBase.products.map((item) => ({
      ...item,
      ingredients: item.ingredients.map((ingredient) => ({ ...ingredient })),
      extras: item.extras.map((extra) => ({ ...extra })),
    })),
  }

  try {
    const response = await fetch(buildApiUrl(`/combos/${comboBase.id}`))
    if (!response.ok) throw new Error('combo-detail-error')
    const payload = (await response.json()) as {
      products?: Array<{
        id: number
        name: string
        ingredients?: Array<{ id: number; name: string; pivot?: { is_default?: boolean; is_removable?: boolean } }>
        extras?: Array<{ id: number; name: string; additional_price: number }>
      }>
    }

    const products = (payload.products || []).map((product) => ({
      id: Number(product.id),
      name: String(product.name || 'Producto'),
      ingredients: (product.ingredients || [])
        .map((ingredient) => ({
          id: Number(ingredient.id),
          name: String(ingredient.name || ''),
          isDefault: Boolean(ingredient.pivot?.is_default ?? true),
          isRemovable: Boolean(ingredient.pivot?.is_removable ?? true),
        }))
        .filter((ingredient) => ingredient.id > 0 && ingredient.name),
      extras: (product.extras || [])
        .map((extra) => ({
          id: Number(extra.id),
          name: String(extra.name || ''),
          price: Number(extra.additional_price || 0),
        }))
        .filter((extra) => extra.id > 0 && extra.name),
    }))

    selectedCombo.value = {
      ...comboBase,
      products,
    }
  } catch {
    // keep basic detail if API fails
  }
}

const comboRemovedForProduct = (productId: number) => comboCustomization.removedByProduct[productId] || []
const comboExtrasForProduct = (productId: number) => comboCustomization.extrasByProduct[productId] || []
const markComboTouched = (productId: number) => {
  comboTouchedProducts.value = {
    ...comboTouchedProducts.value,
    [productId]: true,
  }
}

const isComboIngredientIncluded = (productId: number, ingredientId: number) => !comboRemovedForProduct(productId).includes(ingredientId)
const isComboExtraSelected = (productId: number, extraId: number) => comboExtrasForProduct(productId).includes(extraId)

const toggleComboIngredient = (productId: number, ingredientId: number) => {
  const current = [...comboRemovedForProduct(productId)]
  const exists = current.includes(ingredientId)
  comboCustomization.removedByProduct[productId] = exists
    ? current.filter((id) => id !== ingredientId)
    : [...current, ingredientId]
  markComboTouched(productId)
}

const toggleComboExtra = (productId: number, extraId: number) => {
  const current = [...comboExtrasForProduct(productId)]
  const exists = current.includes(extraId)
  comboCustomization.extrasByProduct[productId] = exists
    ? current.filter((id) => id !== extraId)
    : [...current, extraId]
  markComboTouched(productId)
}

const selectedComboExtrasTotal = computed(() => {
  if (!selectedCombo.value) return 0
  return selectedCombo.value.products.reduce((acc, product) => {
    const selectedIds = comboExtrasForProduct(product.id)
    const extrasTotal = product.extras
      .filter((extra) => selectedIds.includes(extra.id))
      .reduce((sum, extra) => sum + extra.price, 0)
    return acc + extrasTotal
  }, 0)
})

const selectedComboTotal = computed(() => (selectedCombo.value ? selectedCombo.value.price + selectedComboExtrasTotal.value : 0))
const comboSuggestions = computed(() =>
  store.activeProducts
    .filter((product) => {
      const category = normalizeCategory(product.category).toLowerCase()
      return comboSuggestionCategory.some((term) => category.includes(term))
    })
    .slice(0, 3),
)

const comboStepTitle = (productId: number, index: number) => {
  const category = normalizeCategory(store.getProduct(productId)?.category).toLowerCase()
  if (category.includes('burg')) return 'Elegi tu Burger'
  if (category.includes('beb') || category.includes('drink') || category.includes('gaseosa')) return 'Elegi tu Bebida'
  if (category.includes('acompa') || category.includes('papa') || category.includes('guarn')) return 'Elegi tu Acompanamiento'
  return `Paso ${index + 1}`
}

const comboStepDone = (product: ComboView['products'][number]) => {
  if (!product.ingredients.length && !product.extras.length) return true
  return Boolean(comboTouchedProducts.value[product.id])
}

const comboPreviewStyle = (product: ComboView['products'][number]) => {
  const selectedExtras = comboExtrasForProduct(product.id)
  const cheddarSelected = product.extras.some(
    (extra) => selectedExtras.includes(extra.id) && extra.name.toLowerCase().includes('cheddar'),
  )
  if (cheddarSelected) return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-500'
}

const addComboSuggestion = (product: Product) => {
  addToCart({
    type: 'product',
    productId: product.id,
    name: product.name,
    imageUrl: product.imageUrl,
    unitPrice: productDisplayPrice(product),
    qty: 1,
  })
  markRecentlyAdded(product.id)
}

const closeBottomSheet = (target: 'product' | 'combo') => {
  if (target === 'product') {
    selectedProduct.value = null
    return
  }
  selectedCombo.value = null
}

const onBottomSheetTouchStart = (event: TouchEvent, target: 'product' | 'combo') => {
  if (!event.touches.length) return
  bottomSheetTarget.value = target
  bottomSheetStartY.value = event.touches[0].clientY
  bottomSheetDragging.value = true
}

const onBottomSheetTouchMove = (event: TouchEvent) => {
  if (!bottomSheetDragging.value || !event.touches.length) return
  const delta = event.touches[0].clientY - bottomSheetStartY.value
  bottomSheetTranslateY.value = Math.max(0, delta)
}

const onBottomSheetTouchEnd = () => {
  if (!bottomSheetDragging.value) return
  const threshold = 120
  const target = bottomSheetTarget.value
  const shouldClose = bottomSheetTranslateY.value > threshold
  bottomSheetDragging.value = false
  bottomSheetTarget.value = null
  if (shouldClose && target) {
    closeBottomSheet(target)
  }
  bottomSheetTranslateY.value = 0
}

const addComboToCart = () => {
  if (!selectedCombo.value) return
  const comboSubItems = selectedCombo.value.products.map((product) => ({
    product_id: product.id,
    excluded_ingredients: comboRemovedForProduct(product.id),
    extras: comboExtrasForProduct(product.id),
  }))

  const summary = selectedCombo.value.products
    .map((product) => {
      const removed = comboRemovedForProduct(product.id)
      const extras = comboExtrasForProduct(product.id)
      const removedText = removed.length ? `sin ${removed.length}` : ''
      const extrasText = extras.length ? `+${extras.length} extra` : ''
      const details = [removedText, extrasText].filter(Boolean).join(', ')
      return details ? `${product.name}: ${details}` : ''
    })
    .filter(Boolean)
    .join(' | ')

  addToCart({
    type: 'combo',
    comboId: selectedCombo.value.id,
    name: selectedCombo.value.name,
    imageUrl: selectedCombo.value.imageUrl,
    unitPrice: selectedComboTotal.value,
    qty: 1,
    customLabel: summary || undefined,
    comboSubItems,
  })
  selectedCombo.value = null
}

const buildCheckoutRedirect = () => route.fullPath || `/tienda/${currentTenantSlug.value || 'demo-delivery'}`

const goToLoginForCheckout = () => {
  authPopupOpen.value = false
  router.push({
    path: '/login',
    query: {
      redirect: buildCheckoutRedirect(),
      tenant: currentTenantSlug.value || 'demo-delivery',
      intent: 'checkout',
    },
  })
}

const loadGoogleScript = () => {
  if (googleScriptPromise) return googleScriptPromise
  googleScriptPromise = new Promise<void>((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>('script[data-google-identity="1"]')
    if (existing) {
      resolve()
      return
    }
    const script = document.createElement('script')
    script.src = 'https://accounts.google.com/gsi/client'
    script.async = true
    script.defer = true
    script.dataset.googleIdentity = '1'
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('google-script-error'))
    document.head.appendChild(script)
  })
  return googleScriptPromise
}

const handleGoogleCredential = async (credential: string) => {
  if (!credential) {
    authPopupError.value = 'No se pudo obtener credencial de Google.'
    return
  }
  googleAuthLoading.value = true
  authPopupError.value = ''
  const ok = await store.loginWithGoogle(credential, currentTenantSlug.value || 'demo-delivery')
  googleAuthLoading.value = false
  if (!ok) {
    authPopupError.value = store.authError || 'No se pudo iniciar sesion con Google.'
    return
  }
  authPopupOpen.value = false
  await confirmOrder()
}

const initGoogleButton = async () => {
  if (!authPopupOpen.value || !googleClientId || !googleButtonHost.value) return
  try {
    await loadGoogleScript()
    const googleApi = (window as Window & { google?: any }).google
    if (!googleApi?.accounts?.id) {
      authPopupError.value = 'Google no esta disponible en este navegador.'
      return
    }
    googleApi.accounts.id.initialize({
      client_id: googleClientId,
      callback: (response: { credential?: string }) => {
        handleGoogleCredential(String(response?.credential || ''))
      },
    })
    googleButtonHost.value.innerHTML = ''
    googleApi.accounts.id.renderButton(googleButtonHost.value, {
      theme: 'outline',
      size: 'large',
      text: 'continue_with',
      shape: 'pill',
      width: 280,
    })
    googleAuthReady.value = true
  } catch {
    authPopupError.value = 'No se pudo cargar Google Sign-In.'
  }
}

const confirmOrder = async () => {
  if (!cart.value.length) return
  if (!store.isAuthenticated) {
    authPopupOpen.value = true
    authPopupError.value = ''
    orderSyncMessage.value = 'Inicia sesion para confirmar el pedido.'
    return
  }
  const payload = {
    customer: checkoutForm.customer.trim(),
    address: isSalonMode.value ? `${salonTableLabel.value} - Salon` : checkoutForm.address.trim(),
    paymentMethod: checkoutForm.paymentMethod,
    cashReceived: checkoutForm.paymentMethod === 'cash' && checkoutForm.cashReceived > 0 ? checkoutForm.cashReceived : null,
    items: cart.value.map((line) => ({
      qty: line.qty,
      ...(line.type === 'combo'
        ? { combo_id: line.comboId, sub_items: line.comboSubItems || [] }
        : { product_id: line.productId }),
    })),
  }
  const localTotal = totalAfterCoupon.value
  const localChange =
    payload.paymentMethod === 'cash' && payload.cashReceived !== null && payload.cashReceived > localTotal
      ? Number((payload.cashReceived - localTotal).toFixed(2))
      : 0
  const authToken = (() => {
    try {
      const raw = localStorage.getItem('delivery-vue-auth-v2')
      if (!raw) return ''
      const parsed = JSON.parse(raw) as { token?: string }
      return parsed.token || ''
    } catch {
      return ''
    }
  })()
  fetch(buildApiUrl('/orders'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
    },
    body: JSON.stringify({
      customer: payload.customer,
      address: payload.address,
      payment_method: payload.paymentMethod,
      cash_received: payload.cashReceived,
      coupon_code: appliedCoupon.value?.code || null,
      items: payload.items,
    }),
  })
    .then(async (response) => {
      if (!response.ok) throw new Error('sync-error')
      await response.json()
      await store.refreshAll()
      orderSyncMessage.value = 'Pedido sincronizado con backend.'
      window.setTimeout(() => {
        if (orderSyncMessage.value === 'Pedido sincronizado con backend.') orderSyncMessage.value = ''
      }, 2400)
    })
    .catch(() => {
      orderSyncMessage.value = 'Pedido guardado en modo local demo.'
      window.setTimeout(() => {
        if (orderSyncMessage.value === 'Pedido guardado en modo local demo.') orderSyncMessage.value = ''
      }, 2400)
    })

  cart.value = []
  if (appliedCoupon.value) {
    coupons.value = coupons.value.map((coupon) =>
      coupon.id === appliedCoupon.value?.id
        ? { ...coupon, usedCount: Number(coupon.usedCount || 0) + 1 }
        : coupon,
    )
    localStorage.setItem('delivery-admin-coupons-v1', JSON.stringify(coupons.value))
  }
  appliedCoupon.value = null
  couponCode.value = ''
  couponFeedback.value = ''
  isCartOpen.value = false
  checkoutDone.value = true
}

const restart = () => {
  checkoutDone.value = false
  query.value = ''
  activeCategory.value = categories.value[0] || 'all'
}

const requestWaiter = () => {
  if (!isSalonMode.value || !tableNumber.value) return
  let queue: Array<{ id: string; table: number; createdAt: number; status: string }> = []
  try {
    const queueRaw = localStorage.getItem('delivery-salon-assistance-v1')
    queue = queueRaw ? JSON.parse(queueRaw) : []
  } catch {
    queue = []
  }
  queue.unshift({
    id: `assist-${Date.now()}`,
    table: tableNumber.value,
    createdAt: Date.now(),
    status: 'pending',
  })
  localStorage.setItem('delivery-salon-assistance-v1', JSON.stringify(queue))
  assistanceMessage.value = `Solicitud enviada para ${salonTableLabel.value}.`
  window.setTimeout(() => {
    if (assistanceMessage.value.startsWith('Solicitud enviada')) assistanceMessage.value = ''
  }, 2200)
}

const applyRepeatOrderFromRoute = () => {
  const repeatRaw = route.query.repetir
  const repeatId = Number(Array.isArray(repeatRaw) ? repeatRaw[0] : repeatRaw)
  if (!Number.isFinite(repeatId) || repeatId <= 0) return
  const alreadyAppliedKey = `delivery-repeat-applied-${repeatId}`
  if (sessionStorage.getItem(alreadyAppliedKey) === '1') return
  const order = store.sortedOrders.find((item) => item.id === repeatId)
  if (!order) return
  for (const item of order.items) {
    const product = store.getProduct(item.productId)
    if (!product || !product.enabled) continue
    const qty = Math.max(1, Number(item.qty || 1))
    for (let index = 0; index < qty; index += 1) {
      addToCart({
        type: 'product',
        productId: product.id,
        name: product.name,
        imageUrl: product.imageUrl,
        unitPrice: productDisplayPrice(product),
        qty: 1,
      })
    }
  }
  sessionStorage.setItem(alreadyAppliedKey, '1')
  if (cart.value.length) {
    isCartOpen.value = true
    orderSyncMessage.value = `Pedido #ORD-${String(order.id).padStart(4, '0')} cargado para repetir.`
    window.setTimeout(() => {
      if (orderSyncMessage.value.includes('cargado para repetir')) orderSyncMessage.value = ''
    }, 2400)
  }
}

const syncBodyScrollLock = () => {
  const shouldLock = Boolean(isCartOpen.value || selectedProduct.value || selectedCombo.value)
  document.body.style.overflow = shouldLock ? 'hidden' : ''
}

const handleWindowScroll = () => {
  headerScrolled.value = window.scrollY > 12
}

watch([isCartOpen, selectedProduct, selectedCombo], syncBodyScrollLock)
watch(
  authPopupOpen,
  (open) => {
    if (!open) return
    window.setTimeout(() => {
      initGoogleButton()
    }, 0)
  },
)
watch(total, (nextTotal) => {
  if (!appliedCoupon.value) return
  if (nextTotal <= 0) {
    appliedCoupon.value = null
    couponFeedback.value = ''
    return
  }
  if (nextTotal < Number(appliedCoupon.value.minOrder || 0)) {
    couponFeedback.value = 'El cupon se removio porque no cumples el monto minimo.'
    couponFeedbackTone.value = 'error'
    appliedCoupon.value = null
  }
})
watch(
  categories,
  (nextCategories) => {
    if (!nextCategories.includes(activeCategory.value)) {
      activeCategory.value = nextCategories[0] || 'all'
    }
  },
  { immediate: true },
)

watch(
  salonSections,
  (sections) => {
    if (!sections.length) return
    const next: Record<string, boolean> = {}
    sections.forEach((section, index) => {
      next[section.category] = salonSectionOpen.value[section.category] ?? index === 0
    })
    salonSectionOpen.value = next
  },
  { immediate: true },
)

watch(
  () => route.query.repetir,
  () => {
    applyRepeatOrderFromRoute()
  },
)

watch(
  () => route.params.tenantSlug,
  async (nextSlug) => {
    const slug = String(nextSlug || '').trim()
    if (!slug) return
    store.setPublicTenantSlug(slug)
    await store.fetchStorefront(slug)
    await store.initialize()
  },
)

watch(
  () => store.sortedOrders.length,
  () => {
    applyRepeatOrderFromRoute()
  },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  addFeedbackTimers.forEach((timeoutId) => window.clearTimeout(timeoutId))
  addFeedbackTimers.clear()
  if (cartPulseTimer) {
    window.clearTimeout(cartPulseTimer)
    cartPulseTimer = 0
  }
  if (dailyFabTimer) {
    window.clearTimeout(dailyFabTimer)
    dailyFabTimer = 0
  }
  window.removeEventListener('scroll', handleWindowScroll)
})

onMounted(async () => {
  if (currentTenantSlug.value) {
    store.setPublicTenantSlug(currentTenantSlug.value)
    await store.fetchStorefront(currentTenantSlug.value)
  }
  if (store.currentUser?.name) {
    checkoutForm.customer = store.currentUser.name
  }
  try {
    const rawCoupons = localStorage.getItem('delivery-admin-coupons-v1')
    const parsedCoupons = rawCoupons ? JSON.parse(rawCoupons) : []
    coupons.value = Array.isArray(parsedCoupons) ? parsedCoupons : []
  } catch {
    coupons.value = []
  }

  try {
    const response = await fetch(buildApiUrl('/combos'))
    if (!response.ok) throw new Error('No combos')
    const payload = (await response.json()) as BackendComboResponse[]
    remoteCombos.value = payload.map((combo) => ({
      id: combo.id,
      name: combo.name,
      imageUrl:
        resolveAssetUrl(combo.image_url) ||
        'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=80',
      products: (combo.products || []).map((item) => ({
        id: Number(item.id),
        name: String(item.name || 'Producto'),
        ingredients: [],
        extras: [],
      })),
      price: Number(combo.base_price || 0),
    }))
  } catch {
    remoteCombos.value = []
  }
  handleWindowScroll()
  window.addEventListener('scroll', handleWindowScroll, { passive: true })
  applyRepeatOrderFromRoute()
})
</script>

<template>
  <section class="rounded-[24px] bg-[#F9FAFB] p-4 md:p-6">
    <Transition name="fade" mode="out-in">
      <div v-if="!checkoutDone" key="shop" class="space-y-6 md:space-y-7">
        <header
          class="sticky top-2 z-50 overflow-hidden rounded-[20px] border border-white/70 p-3 transition-all duration-300"
          :class="headerScrolled ? 'bg-white/78 shadow-[0_12px_28px_rgba(15,23,42,0.12)] backdrop-blur-[10px]' : 'bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]'"
        >
          <div class="absolute inset-x-0 top-0 h-14 bg-gradient-to-r from-emerald-100/60 via-emerald-50/55 to-sky-100/60"></div>
          <div class="relative flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="grid h-9 w-9 place-items-center rounded-2xl bg-white text-xs font-extrabold text-emerald-700 ring-1 ring-emerald-200/70">
                {{ storefrontInitials }}
              </span>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ isSalonMode ? salonTableLabel : 'Tienda online' }}</p>
                <p class="text-base font-extrabold leading-tight tracking-tight text-slate-900">{{ salonHeaderTitle }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm ring-1 ring-slate-200 transition active:scale-[0.98]"
                :class="cartPulse ? 'cart-pop' : ''"
                @click="isCartOpen = true"
              >
                <ShoppingCart class="h-4 w-4" />
                <span
                  v-if="cartCount > 0"
                  class="absolute -right-1 -top-1 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow"
                >
                  {{ cartCount }}
                </span>
              </button>
              <ProfileMenu compact :orders-route="'/cliente/pedidos'" :profile-route="'/cliente/perfil'" />
            </div>
          </div>
          <div v-if="!isSalonMode" class="relative mt-2">
            <div class="h-[3px] w-full overflow-hidden rounded-full bg-stone-200/90">
              <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300" :style="{ width: `${freeShippingProgress}%` }"></div>
            </div>
            <p class="mt-1 text-[11px] font-medium text-slate-500">
              {{ freeShippingRemaining > 0 ? `Te faltan $${freeShippingRemaining.toLocaleString('es-AR')} para envio gratis` : 'Ya tenes envio gratis' }}
            </p>
          </div>
        </header>

        <div
          v-if="orderSyncMessage"
          class="rounded-[18px] bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-slate-200/45"
        >
          {{ orderSyncMessage }}
        </div>

        <div v-if="!isSalonMode" class="sticky top-[90px] z-40 mt-2 rounded-[24px] bg-white/95 p-3 shadow-sm ring-1 ring-white/70 backdrop-blur-xl md:top-[96px]">
          <div class="flex flex-col gap-2.5 md:flex-row md:items-center">
            <div class="relative md:w-[320px] md:shrink-0">
              <span class="pointer-events-none absolute inset-y-0 left-3 inline-flex items-center">
                <Search class="h-4 w-4 text-slate-400" />
              </span>
              <input
                v-model="query"
                type="text"
                placeholder="Buscar producto, combo o categoria..."
                class="w-full rounded-[32px] bg-[#F3F4F6] py-2.5 pl-10 pr-3 text-sm text-slate-700"
              />
            </div>
            <div class="category-strip flex gap-2 overflow-x-auto pb-1 md:flex-1">
              <button
                v-for="category in categories"
                :key="category"
                type="button"
                class="shrink-0 rounded-full px-3 py-2 text-xs font-semibold transition-all duration-200 active:scale-[0.98]"
                :class="activeCategory === category ? 'bg-emerald-500 text-white shadow-[0_6px_16px_rgba(16,185,129,0.3)] ring-1 ring-emerald-400/60' : 'bg-slate-100 text-slate-500 hover:bg-slate-200/70 hover:text-slate-700'"
                @click="activeCategory = category"
              >
                <span class="inline-flex items-center gap-1.5">
                  <component :is="categoryIcon(category)" class="h-3.5 w-3.5" />
                  {{ category === 'all' ? 'Todas' : category }}
                </span>
              </button>
            </div>
          </div>
        </div>

        <section class="space-y-3">
          <div ref="dailyBannerRef" class="offer-carousel overflow-x-auto pb-1">
            <div class="flex min-w-max snap-x snap-mandatory gap-3">
              <article
                v-for="slide in offerSlides"
                :key="slide.id"
                class="group relative w-[86vw] max-w-[420px] shrink-0 snap-start overflow-hidden rounded-[24px] bg-white shadow-[0_10px_24px_-16px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/60"
                @click="openOfferSlide(slide)"
              >
                <div class="absolute left-3 top-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-slate-700">{{ slide.badge }}</div>
                <div class="relative h-48 w-full overflow-hidden sm:h-56">
                  <div v-if="!isImageLoaded(imageKeyForOffer(slide))" class="skeleton-shimmer absolute inset-0"></div>
                  <img
                    v-if="slide.imageUrl"
                    :src="slide.imageUrl"
                    :alt="slide.title"
                    class="img-fade-in h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    loading="lazy"
                    @load="onImageLoaded(imageKeyForOffer(slide))"
                  />
                  <div v-else class="grid h-full place-items-center bg-slate-100 text-slate-400">
                    <Sparkles class="h-8 w-8" />
                  </div>
                  <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/20 to-transparent"></div>
                </div>
                <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                  <p class="text-xs uppercase tracking-[0.14em] text-slate-100/90">{{ slide.subtitle }}</p>
                  <h3 class="mt-1 text-2xl font-bold leading-tight">{{ slide.title }}</h3>
                  <p v-if="slide.priceLabel" class="mt-1 text-lg font-extrabold text-amber-200">{{ slide.priceLabel }}</p>
                </div>
              </article>
            </div>
          </div>
        </section>

        <div v-if="!hasAnyCombo && !dailyHighlight" class="rounded-[18px] bg-white p-4 text-sm text-slate-500 ring-1 ring-slate-200/45">
          No hay ofertas activas por ahora.
        </div>

        <div v-if="!isSalonMode" class="grid grid-cols-2 gap-4">
          <article
            v-for="product in filteredProducts"
            :key="product.id"
            class="group relative overflow-hidden rounded-[20px] bg-white shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)] transition hover:-translate-y-0.5"
          >
            <button type="button" class="w-full text-left" @click="openProductDetail(product)">
              <div class="aspect-[4/3] overflow-hidden rounded-t-[20px] bg-stone-200/40">
                <div v-if="!isImageLoaded(imageKeyForProduct(product))" class="skeleton-shimmer h-full w-full"></div>
                <img
                  v-if="product.imageUrl"
                  :src="product.imageUrl"
                  :alt="product.name"
                  class="img-fade-in h-full w-full object-cover"
                  loading="lazy"
                  @load="onImageLoaded(imageKeyForProduct(product))"
                />
                <div v-else class="grid h-full place-items-center text-3xl font-bold text-slate-400">{{ initials(product.name) }}</div>
              </div>

              <div class="p-4 pr-16">
                <h3 class="text-base font-semibold text-slate-900 capitalize">{{ product.name }}</h3>
                <p class="mt-1 text-xs text-[#6B7280]">Seleccion premium con preparacion optimizada.</p>
                <div class="mt-3 flex items-end gap-2">
                  <p class="text-xl font-bold text-emerald-700">${{ productDisplayPrice(product).toFixed(2) }}</p>
                  <p v-if="productPromoPrice(product.id) !== null" class="text-xs font-medium text-slate-400 line-through">
                    ${{ Number(product.price).toFixed(2) }}
                  </p>
                </div>
              </div>
            </button>
            <button
              type="button"
              class="absolute bottom-4 right-4 grid h-11 w-11 place-items-center rounded-full bg-emerald-600 text-white shadow-md transition active:scale-[0.98]"
              @click.stop="quickAddProduct(product)"
            >
              <Check v-if="recentlyAddedProducts[product.id]" class="h-4 w-4" />
              <Plus v-else class="h-5 w-5" />
            </button>

          </article>
        </div>

        <section v-else class="space-y-3">
          <article
            v-for="(section, index) in salonSections"
            :key="`salon-section-${section.category}`"
            class="overflow-hidden rounded-[20px] bg-white shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]"
          >
            <button
              type="button"
              class="flex w-full items-center justify-between px-4 py-3 text-left"
              @click="toggleSalonSection(section.category)"
            >
              <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                <component :is="categoryIcon(section.category)" class="h-4 w-4 text-emerald-600" />
                {{ section.category }}
              </span>
              <span class="text-xs font-semibold text-slate-500">{{ section.products.length }} items</span>
            </button>
            <div v-if="isSalonSectionOpen(section.category, index)" class="grid grid-cols-2 gap-4 px-3 pb-3">
              <article
                v-for="product in section.products"
                :key="`salon-product-${product.id}`"
                class="group relative overflow-hidden rounded-[20px] bg-white shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)] transition hover:-translate-y-0.5"
              >
                <button type="button" class="w-full text-left" @click="openProductDetail(product)">
                  <div class="aspect-[4/3] overflow-hidden rounded-t-[20px] bg-stone-200/40">
                    <div v-if="!isImageLoaded(imageKeyForProduct(product))" class="skeleton-shimmer h-full w-full"></div>
                    <img
                      v-if="product.imageUrl"
                      :src="product.imageUrl"
                      :alt="product.name"
                      class="img-fade-in h-full w-full object-cover"
                      loading="lazy"
                      @load="onImageLoaded(imageKeyForProduct(product))"
                    />
                    <div v-else class="grid h-full place-items-center text-3xl font-bold text-slate-400">{{ initials(product.name) }}</div>
                  </div>
                  <div class="p-4 pr-16">
                    <h3 class="text-base font-semibold text-slate-900 capitalize">{{ product.name }}</h3>
                    <p class="mt-1 text-xs text-[#6B7280]">Seleccion premium con preparacion optimizada.</p>
                    <div class="mt-3 flex items-end gap-2">
                      <p class="text-xl font-bold text-emerald-700">${{ productDisplayPrice(product).toFixed(2) }}</p>
                      <p v-if="productPromoPrice(product.id) !== null" class="text-xs font-medium text-slate-400 line-through">
                        ${{ Number(product.price).toFixed(2) }}
                      </p>
                    </div>
                  </div>
                </button>
                <button
                  type="button"
                  class="absolute bottom-4 right-4 grid h-11 w-11 place-items-center rounded-full bg-emerald-600 text-white shadow-md transition active:scale-[0.98]"
                  @click.stop="quickAddProduct(product)"
                >
                  <Check v-if="recentlyAddedProducts[product.id]" class="h-4 w-4" />
                  <Plus v-else class="h-5 w-5" />
                </button>
              </article>
            </div>
          </article>
          <article v-if="!salonSections.length" class="rounded-[18px] bg-white p-4 text-sm text-slate-500 ring-1 ring-slate-200/45">
            No hay productos para mostrar en el menu del salon.
          </article>
        </section>

        <Transition name="fade">
          <div v-if="showDailyFab" class="fixed bottom-24 right-5 z-40">
            <div v-if="dailyFabOpen" class="mb-2 max-w-[200px] rounded-2xl bg-white px-3 py-2 text-xs text-slate-600 shadow-lg ring-1 ring-slate-200/70">
              No te pierdas el menu del dia de hoy.
            </div>
            <button
              type="button"
              class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 transition active:scale-[0.98]"
              @click="focusDailyBanner"
            >
              <Star class="h-5 w-5" />
            </button>
          </div>
        </Transition>
        <Transition name="fade">
          <div v-if="isSalonMode" class="fixed bottom-24 left-5 z-40">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full bg-sky-600 px-4 py-3 text-xs font-semibold text-white shadow-lg shadow-sky-600/30 transition active:scale-[0.98]"
              @click="requestWaiter"
            >
              Llamar al mozo
            </button>
            <p v-if="assistanceMessage" class="mt-2 rounded-xl bg-white px-3 py-1.5 text-[11px] text-slate-600 shadow ring-1 ring-slate-200">
              {{ assistanceMessage }}
            </p>
          </div>
        </Transition>
      </div>

      <div v-else key="done" class="grid min-h-[420px] place-items-center rounded-[24px] bg-emerald-50/40 p-8 text-center ring-1 ring-emerald-200/60">
        <div>
          <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
            <Check class="h-7 w-7" />
          </span>
          <h3 class="mt-4 text-2xl font-semibold text-slate-900">Pedido en Camino</h3>
          <p class="mt-2 text-sm text-slate-600">Tu compra fue confirmada y el equipo ya la esta preparando. Podes seguirlo desde la pestana Pedidos.</p>
          <AppButton variant="soft" class="mt-5 rounded-full" @click="restart">Volver a la tienda</AppButton>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="isCartOpen" class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm" @click="isCartOpen = false"></div>
    </Transition>

    <Transition name="drawer">
      <aside
        v-if="isCartOpen"
        class="fixed inset-x-0 bottom-0 z-50 flex h-[80vh] w-full max-w-none flex-col rounded-t-3xl bg-white p-4 shadow-2xl ring-1 ring-slate-200/60 md:inset-y-0 md:left-auto md:right-0 md:h-full md:max-w-md md:rounded-none"
      >
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
            :key="`${line.type}-${line.productId || line.comboId}-${line.unitPrice}-${line.customLabel || ''}`"
            class="flex items-center gap-3 rounded-[18px] bg-white p-3 ring-1 ring-slate-200/50"
          >
            <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-lg bg-slate-100 text-xs font-bold text-slate-500">
              <img
                v-if="line.imageUrl"
                :src="line.imageUrl"
                :alt="line.name"
                class="h-full w-full object-cover"
                loading="lazy"
              />
              <span v-else>{{ initials(line.name) }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">{{ line.name }}</p>
              <p v-if="line.customLabel" class="truncate text-[11px] text-slate-500">{{ line.customLabel }}</p>
              <p class="text-xs text-slate-500">Subtotal: ${{ line.subtotal }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="grid h-7 w-7 place-items-center rounded-full bg-white text-sm text-slate-600 ring-1 ring-slate-200 active:scale-95"
                @click="removeFromCart(line)"
              >
                <Minus class="h-3.5 w-3.5" />
              </button>
              <span class="w-5 text-center text-sm font-semibold text-slate-700">{{ line.qty }}</span>
              <button
                type="button"
                class="grid h-7 w-7 place-items-center rounded-full bg-white text-sm text-slate-600 ring-1 ring-slate-200 active:scale-95"
                @click="addToCart(line)"
              >
                <Plus class="h-3.5 w-3.5" />
              </button>
            </div>
          </article>

          <div v-if="!cartView.length" class="rounded-[18px] p-4 text-sm text-slate-500 ring-1 ring-slate-200/50">
            Tu carrito esta vacio.
          </div>
        </div>

        <footer class="mt-4 space-y-3 border-t border-slate-200/70 pt-4">
          <div class="space-y-2">
            <input
              v-model="checkoutForm.customer"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
              placeholder="Nombre"
            />
            <input
              v-if="!isSalonMode"
              v-model="checkoutForm.address"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
              placeholder="Direccion de entrega"
            />
            <div v-else class="w-full rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700">
              Pedido para {{ salonTableLabel }}
            </div>
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

            <div class="rounded-xl border border-dashed border-emerald-300 bg-emerald-50/70 p-2">
              <div class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-white text-emerald-600 ring-1 ring-emerald-200">
                  <Ticket class="h-4 w-4" />
                </span>
                <input
                  v-model="couponCode"
                  type="text"
                  class="min-w-0 flex-1 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-slate-700"
                  placeholder="Codigo de cupon"
                />
                <button type="button" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700" @click="applyCoupon">
                  Aplicar
                </button>
              </div>
              <p v-if="couponFeedback" class="mt-2 text-xs" :class="couponFeedbackTone === 'success' ? 'text-emerald-700' : 'text-rose-700'">
                {{ couponFeedback }}
              </p>
            </div>
          </div>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Total</span>
            <div class="text-right" :class="couponPricePulse ? 'price-shift' : ''">
              <p v-if="appliedCoupon && couponDiscount > 0" class="text-sm font-semibold text-rose-500 line-through">${{ total }}</p>
              <p class="text-lg font-bold" :class="appliedCoupon && couponDiscount > 0 ? 'text-emerald-700' : 'text-slate-900'">
                {{ appliedCoupon && couponDiscount > 0 ? totalAfterCouponLabel : `$${total}` }}
              </p>
              <p v-if="appliedCoupon && couponDiscount > 0" class="text-xs font-semibold text-emerald-700">Ahorraste {{ savingsLabel }}</p>
            </div>
          </div>

          <AppButton variant="primary" :full="true" py="py-3" :disabled="!cartView.length" @click="confirmOrder">
            <span class="inline-flex items-center gap-2">
              <Sparkles class="h-4 w-4" />
              Confirmar Pedido
            </span>
          </AppButton>
        </footer>
      </aside>
    </Transition>

    <Transition name="fade">
      <div v-if="authPopupOpen" class="fixed inset-0 z-[60] grid place-items-center bg-slate-900/40 p-4 backdrop-blur-sm">
        <article class="w-full max-w-md rounded-2xl bg-white p-5 shadow-[0_20px_40px_rgba(15,23,42,0.22)]">
          <div class="flex items-start justify-between gap-2">
            <div>
              <h3 class="text-base font-semibold text-slate-900">Inicia sesion para confirmar tu pedido</h3>
              <p class="mt-1 text-xs text-slate-500">Tu carrito queda guardado y seguimos desde aqui.</p>
            </div>
            <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-600 active:scale-[0.98]" @click="authPopupOpen = false">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="mt-4 space-y-3">
            <button
              type="button"
              class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]"
              @click="goToLoginForCheckout"
            >
              Ingresar con email y password
            </button>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <p class="mb-2 text-xs font-semibold text-slate-600">O continuar con Google</p>
              <div v-if="googleClientId" ref="googleButtonHost" class="min-h-[42px]"></div>
              <p v-else class="text-xs text-amber-700">
                Configura `VITE_GOOGLE_CLIENT_ID` para habilitar Google Sign-In.
              </p>
              <p v-if="googleAuthLoading" class="mt-2 text-xs text-slate-500">Validando cuenta de Google...</p>
            </div>

            <p v-if="authPopupError" class="text-xs font-semibold text-rose-600">{{ authPopupError }}</p>
            <p v-else-if="!googleAuthReady && googleClientId" class="text-xs text-slate-500">Cargando Google Sign-In...</p>
          </div>
        </article>
      </div>
    </Transition>

    <Transition name="expand-card">
      <div v-if="selectedProduct" class="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-[4px]">
        <div class="flex h-full items-end overflow-y-auto md:grid md:place-items-center md:p-6">
          <article
            class="bottom-sheet-card h-[90vh] w-full overflow-hidden bg-white md:h-auto md:w-full md:max-w-2xl md:rounded-2xl"
            :style="{ transform: `translateY(${bottomSheetTarget === 'product' ? bottomSheetTranslateY : 0}px)` }"
            @touchstart="onBottomSheetTouchStart($event, 'product')"
            @touchmove="onBottomSheetTouchMove"
            @touchend="onBottomSheetTouchEnd"
          >
            <div class="sheet-handle-wrap">
              <span class="sheet-handle"></span>
            </div>
            <div class="relative">
              <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-50 p-6">
                <div class="grid h-full place-items-center overflow-hidden rounded-xl bg-white shadow-sm">
                  <img
                    v-if="selectedProduct.imageUrl"
                    :src="selectedProduct.imageUrl"
                    :alt="selectedProduct.name"
                    class="img-fade-in h-full w-full object-cover"
                  />
                  <span v-else class="text-5xl font-bold text-slate-300">{{ initials(selectedProduct.name) }}</span>
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
                <h3 class="text-2xl font-bold text-slate-900">{{ selectedProduct.name }}</h3>
                <p class="mt-1 text-2xl font-extrabold text-emerald-700">${{ detailTotal }}</p>
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
                      :checked="isIngredientIncluded(ingredient)"
                      @change="toggleIngredient(ingredient)"
                    />
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-400">Desmarca para quitar (ej: sin cebolla).</p>
              </section>

              <section>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Extras disponibles</h4>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    type="button"
                    v-for="extra in selectedConfig?.extras || []"
                    :key="extra.id"
                    class="rounded-full px-3 py-2 text-xs font-semibold transition active:scale-[0.98]"
                    :class="customization.selectedExtras.includes(extra.id) ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="toggleExtra(extra.id)"
                  >
                    + {{ extra.name }} ${{ extra.price }}
                  </button>
                </div>
              </section>
            </div>

            <footer class="sticky bottom-0 border-t border-slate-200/70 bg-white p-4 md:rounded-b-2xl">
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

    <Transition name="expand-card">
      <div v-if="selectedCombo" class="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-[4px]">
        <div class="flex h-full items-end overflow-y-auto md:grid md:place-items-center md:p-6">
          <article
            class="bottom-sheet-card h-[90vh] w-full overflow-hidden bg-white md:h-auto md:w-full md:max-w-2xl md:rounded-2xl"
            :style="{ transform: `translateY(${bottomSheetTarget === 'combo' ? bottomSheetTranslateY : 0}px)` }"
            @touchstart="onBottomSheetTouchStart($event, 'combo')"
            @touchmove="onBottomSheetTouchMove"
            @touchend="onBottomSheetTouchEnd"
          >
            <div class="sheet-handle-wrap">
              <span class="sheet-handle"></span>
            </div>
            <div class="relative">
              <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-50 p-6">
                <div class="grid h-full place-items-center overflow-hidden rounded-xl bg-white shadow-sm">
                  <img :src="selectedCombo.imageUrl" :alt="selectedCombo.name" class="img-fade-in h-full w-full object-cover" />
                </div>
              </div>
              <button
                type="button"
                class="absolute right-3 top-3 rounded-full bg-white p-2 text-slate-600 shadow-md active:scale-95"
                @click="selectedCombo = null"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div class="space-y-5 p-4 md:p-6">
              <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-900">Arma tu {{ selectedCombo.name }}</h3>
                <span class="text-xl font-extrabold text-amber-700">${{ selectedComboTotal }}</span>
              </div>

              <div class="max-h-[45vh] overflow-y-auto pr-1">
                <article
                  v-for="(product, productIndex) in selectedCombo.products"
                  :key="`combo-product-${product.id}`"
                  class="mb-2 rounded-xl border border-slate-200 bg-slate-50 p-3"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ comboStepTitle(product.id, productIndex) }}</p>
                      <p class="text-sm font-semibold text-slate-900">{{ product.name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="grid h-9 w-9 place-items-center rounded-lg text-[10px] font-bold" :class="comboPreviewStyle(product)">
                        {{ product.name.slice(0, 2).toUpperCase() }}
                      </span>
                      <span
                        class="grid h-6 w-6 place-items-center rounded-full text-[11px] font-black"
                        :class="comboStepDone(product) ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'"
                      >
                        {{ comboStepDone(product) ? 'âœ“' : 'â—‹' }}
                      </span>
                    </div>
                  </div>
                  <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Ingredientes incluidos</p>
                  <div class="mt-1 space-y-1.5">
                    <div
                      v-for="ingredient in product.ingredients"
                      :key="`${product.id}-ing-${ingredient.id}`"
                      class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs"
                    >
                      <span class="text-slate-700">{{ ingredient.name }}</span>
                      <button
                        type="button"
                        class="grid h-8 w-8 place-items-center rounded-full text-white transition active:scale-[0.98]"
                        :class="isComboIngredientIncluded(product.id, ingredient.id) ? 'bg-rose-500' : 'bg-emerald-500'"
                        :disabled="!ingredient.isRemovable"
                        @click="toggleComboIngredient(product.id, ingredient.id)"
                      >
                        <Minus v-if="isComboIngredientIncluded(product.id, ingredient.id)" class="h-4 w-4" />
                        <Plus v-else class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <p class="mt-1 text-[11px] text-slate-400">Desmarca para quitar ingredientes removibles.</p>

                  <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Extras</p>
                  <div class="mt-1 flex flex-wrap gap-2">
                    <button
                      type="button"
                      v-for="extra in product.extras"
                      :key="`${product.id}-extra-${extra.id}`"
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-1.5 text-xs font-semibold transition active:scale-[0.98]"
                      :class="isComboExtraSelected(product.id, extra.id) ? 'bg-emerald-500 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'"
                      @click="toggleComboExtra(product.id, extra.id)"
                    >
                      <span class="grid h-5 w-5 place-items-center rounded-full" :class="isComboExtraSelected(product.id, extra.id) ? 'bg-white/20' : 'bg-emerald-100 text-emerald-700'">
                        <Minus v-if="isComboExtraSelected(product.id, extra.id)" class="h-3 w-3" />
                        <Plus v-else class="h-3 w-3" />
                      </span>
                      {{ extra.name }} ${{ extra.price }}
                    </button>
                  </div>
                  <p v-if="!product.ingredients.length && !product.extras.length" class="mt-1 text-xs text-slate-400">Sin detalle cargado.</p>
                </article>
              </div>
              <section v-if="comboSuggestions.length" class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sugerencias para sumar</p>
                <div class="mt-2 space-y-2">
                  <article
                    v-for="product in comboSuggestions"
                    :key="`combo-suggest-${product.id}`"
                    class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                  >
                    <p class="text-xs font-semibold text-slate-700">{{ product.name }}</p>
                    <button
                      type="button"
                      class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 active:scale-[0.98]"
                      @click="addComboSuggestion(product)"
                    >
                      Sumar por ${{ productDisplayPrice(product).toFixed(2) }}
                    </button>
                  </article>
                </div>
              </section>
            </div>

            <footer class="sticky bottom-0 border-t border-slate-200 bg-white p-4 md:rounded-b-2xl">
              <div class="mb-2 flex items-center justify-between text-sm">
                <span class="text-slate-500">Total personalizado</span>
                <span class="text-lg font-bold text-slate-900">${{ selectedComboTotal }}</span>
              </div>
              <AppButton variant="primary" :full="true" py="py-3" @click="addComboToCart">
                Confirmar y agregar al carrito | ${{ selectedComboTotal }}
              </AppButton>
            </footer>
          </article>
        </div>
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

.expand-card-enter-active,
.expand-card-leave-active {
  transition: opacity 220ms ease, transform 260ms ease;
}

.expand-card-enter-from,
.expand-card-leave-to {
  opacity: 0;
  transform: translateY(22px);
}

.bottom-sheet-card {
  border-top-left-radius: 24px;
  border-top-right-radius: 24px;
  box-shadow: 0 4px 20px rgb(0 0 0 / 0.03);
  transition: transform 180ms ease;
}

.sheet-handle-wrap {
  display: grid;
  place-items: center;
  padding-top: 10px;
  padding-bottom: 6px;
}

.sheet-handle {
  width: 44px;
  height: 5px;
  border-radius: 9999px;
  background: rgb(203 213 225);
}

@media (min-width: 768px) {
  .bottom-sheet-card {
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
  }

  .sheet-handle-wrap {
    display: none;
  }

  .expand-card-enter-from,
  .expand-card-leave-to {
    transform: scale(0.98);
  }
}

.drawer-enter-active,
.drawer-leave-active {
  transition: transform 220ms ease;
}

.drawer-enter-from,
.drawer-leave-to {
  transform: translateY(100%);
}

@media (min-width: 768px) {
  .drawer-enter-from,
  .drawer-leave-to {
    transform: translateX(100%);
  }
}

.combo-strip {
  scrollbar-width: thin;
}

.offer-carousel {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.offer-carousel::-webkit-scrollbar {
  display: none;
}

.category-strip {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.category-strip::-webkit-scrollbar {
  display: none;
}

.hero-serif {
  font-family: "Cormorant Garamond", "Times New Roman", serif;
}

.badge-handwritten {
  font-family: "Caveat", "Comic Sans MS", cursive;
}

.daily-highlight-border {
  background: linear-gradient(120deg, rgb(16 185 129 / 0.78), rgb(52 211 153 / 0.7), rgb(134 239 172 / 0.66), rgb(16 185 129 / 0.78));
  background-size: 220% 220%;
  animation: dailyBorderFlow 8s linear infinite;
}

.cart-pop {
  animation: cartPop 260ms ease;
}

.skeleton-shimmer {
  background: linear-gradient(90deg, rgb(226 232 240) 0%, rgb(241 245 249) 50%, rgb(226 232 240) 100%);
  background-size: 200% 100%;
  animation: skeletonShift 1.25s ease-in-out infinite;
}

.price-shift {
  animation: priceShift 420ms ease;
}

@keyframes dailyBorderFlow {
  0% {
    background-position: 0% 50%;
  }
  100% {
    background-position: 100% 50%;
  }
}

@keyframes cartPop {
  0% {
    transform: scale(1);
  }
  45% {
    transform: scale(1.12);
  }
  100% {
    transform: scale(1);
  }
}

@keyframes priceShift {
  0% {
    transform: translateY(2px);
    opacity: 0.82;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes skeletonShift {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

</style>

