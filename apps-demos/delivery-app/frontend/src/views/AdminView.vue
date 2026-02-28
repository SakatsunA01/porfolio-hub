<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Activity,
  ArrowDownRight,
  ArrowUpRight,
  BarChart3,
  Banknote,
  BookOpen,
  Bike,
  CalendarDays,
  ChartNoAxesColumnIncreasing,
  Check,
  CreditCard,
  ChevronLeft,
  ChevronRight,
  CircleDollarSign,
  ClipboardList,
  Copy,
  DoorClosed,
  DoorOpen,
  Download,
  Eye,
  Flame,
  House,
  Layers,
  LifeBuoy,
  LogOut,
  Menu,
  PackagePlus,
  Pencil,
  Play,
  Plus,
  QrCode,
  Server,
  Settings,
  ShieldCheck,
  ShoppingCart,
  ShoppingBag,
  Lock,
  Trash2,
  UserCog,
  Truck,
  TriangleAlert,
  UserCheck,
  Database,
  Users,
  Wallet,
  X,
  MapPin,
} from 'lucide-vue-next'
import AppButton from '../components/common/AppButton.vue'
import AppModal from '../components/common/AppModal.vue'
import PlanGuard from '../components/common/PlanGuard.vue'
import ProfitabilityCalculator from '../components/admin/ProfitabilityCalculator.vue'
import { useDeliveryStore, type ComboItem, type DailyMenu, type DailyMenuItem, type IngredientItem, type Order } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import { resolveAssetUrl } from '../utils/media'

const store = useDeliveryStore()
const route = useRoute()
const router = useRouter()
useOrdersRealtime()

type AdminTab =
  | 'home'
  | 'orders'
  | 'products'
  | 'inventory'
  | 'team'
  | 'roles'
  | 'combos'
  | 'categories'
  | 'dailymenus'
  | 'cashbox'
  | 'customers'
  | 'audit'
  | 'health'
  | 'help'
  | 'expenses'
  | 'coupons'
  | 'loyalty'
  | 'tables'
  | 'qr'
  | 'billing'
type CashFilterPreset = 'today' | 'yesterday' | 'week' | 'custom'
type CashShiftFilter = 'all' | 'morning' | 'afternoon' | 'night'
type CashMovementType = 'income' | 'expense'

type CashMovement = {
  id: string
  type: CashMovementType
  source: 'order' | 'manual' | 'admin_expense'
  concept: string
  amount: number
  createdAt: number
  orderId?: number
  customer?: string
}
type PermissionDefinition = {
  key: string
  title: string
  description: string
}
type CouponKind = 'percentage' | 'fixed' | 'free_shipping'
type CouponItem = {
  id: string
  code: string
  kind: CouponKind
  value: number
  minOrder: number
  totalUsesLimit: number
  usesPerClient: number
  usedCount: number
  expiresAt: string
  active: boolean
}
type RecipeLine = { ingredientId: number; qty: number }
type TableItem = { id: number; label: string; code: string }
type IngredientMeta = {
  unit: string
  supplier: string
  purchasePrice: number
  minStock: number
  previousPurchasePrice: number
  priceTrend: 'up' | 'down' | 'same'
}
type AdminExpenseItem = {
  id: string
  category: string
  amount: number
  frequency: 'once' | 'monthly'
  kind: 'fixed' | 'variable'
  createdAt: number
}
type BillingInvoice = { id: string; periodLabel: string; amount: number; status: 'paid' | 'pending'; issuedAt: number }
type AuditFilterChip = 'all' | 'cancelations' | 'price_changes' | 'security' | 'created' | 'edited' | 'deleted'
type LegalTopic = 'terms' | 'privacy' | 'sla'
type RbacRoleKey = 'admin' | 'employee' | 'driver' | 'cashier'
type RbacActionKey = 'view' | 'edit' | 'delete'
type RbacModuleKey =
  | 'dashboard'
  | 'orders'
  | 'cashbox'
  | 'refunds'
  | 'kitchen'
  | 'tables'
  | 'catalog'
  | 'inventory'
  | 'team'
  | 'audit'
  | 'customers'
  | 'marketing'
  | 'billing'
type RbacMatrixState = Record<RbacRoleKey, Record<RbacModuleKey, Record<RbacActionKey, boolean>>>

const routeToTab: Record<string, AdminTab> = {
  '/admin/home': 'home',
  '/admin/orders': 'orders',
  '/admin/products': 'products',
  '/admin/inventory': 'inventory',
  '/admin/catalog': 'products',
  '/admin/team': 'team',
  '/admin/roles': 'team',
  '/admin/combos': 'combos',
  '/admin/categories': 'categories',
  '/admin/daily-menus': 'dailymenus',
  '/admin/cashbox': 'cashbox',
  '/admin/customers': 'customers',
  '/admin/audit': 'audit',
  '/admin/health': 'health',
  '/admin/help': 'help',
  '/admin/expenses': 'expenses',
  '/admin/coupons': 'coupons',
  '/admin/loyalty': 'loyalty',
  '/admin/tables': 'tables',
  '/admin/qr': 'qr',
  '/admin/billing': 'billing',
}

const tabToRoute: Record<AdminTab, string> = {
  home: '/admin/home',
  orders: '/admin/orders',
  products: '/admin/products',
  inventory: '/admin/inventory',
  team: '/admin/team',
  roles: '/admin/team',
  combos: '/admin/combos',
  categories: '/admin/categories',
  dailymenus: '/admin/daily-menus',
  cashbox: '/admin/cashbox',
  customers: '/admin/customers',
  audit: '/admin/audit',
  health: '/admin/health',
  help: '/admin/help',
  expenses: '/admin/expenses',
  coupons: '/admin/coupons',
  loyalty: '/admin/loyalty',
  tables: '/admin/tables',
  qr: '/admin/qr',
  billing: '/admin/billing',
}

const resolveTabFromPath = (path: string): AdminTab => routeToTab[path] || 'home'

const activeTab = ref<AdminTab>(resolveTabFromPath(route.path))
const inventoryView = ref<'list' | 'new'>('list')
const statusBanner = ref('')
const knownOrderIds = ref<number[]>([])
const orderEditorOpen = ref(false)
const orderFlowFilter = ref<'all' | 'waiting_driver' | 'delayed' | 'in_kitchen'>('all')
const productFormTab = ref<'basic' | 'recipe' | 'ops' | 'media'>('basic')
const sidebarCollapsed = ref(false)
const mobileSidebarOpen = ref(false)
const cashFilterPreset = ref<CashFilterPreset>('today')
const cashShiftFilter = ref<CashShiftFilter>('all')
const cashDateFrom = ref('')
const cashDateTo = ref('')
const auditFilterChip = ref<AuditFilterChip>('all')
const auditSearchTerm = ref('')
const auditUserFilter = ref('all')
const auditDateFrom = ref('')
const auditDateTo = ref('')
const cashDrawerOpen = ref(false)
const selectedCashMovement = ref<CashMovement | null>(null)
const cashSessionOpen = ref(true)
const cashMovementModalOpen = ref(false)
const cashMovementDraft = reactive({
  type: 'income' as CashMovementType,
  concept: '',
  amount: 0,
})
const cashManualMovements = ref<CashMovement[]>([])
const rolePermissionState = ref<Record<string, Record<string, boolean>>>({})
const permissionSaveMessage = ref('')
const activeRoleEditorId = ref<number | null>(null)
const coupons = ref<CouponItem[]>([])
const couponModalOpen = ref(false)
const supportModalOpen = ref(false)
const supportIssueDraft = ref('')
const helpFaqOpenId = ref<number | null>(1)
const helpLegalFocus = ref<LegalTopic | null>(null)
const couponForm = reactive({
  code: '',
  kind: 'percentage' as CouponKind,
  value: 10,
  minOrder: 0,
  totalUsesLimit: 50,
  usesPerClient: 1,
  expiresAt: '',
})
const productRecipeMap = ref<Record<string, RecipeLine[]>>({})
const selectedRecipeProductId = ref(0)
const recipeIngredientDraft = reactive({
  ingredientId: 0,
  qty: 1,
})
const ingredientMetaMap = ref<Record<string, IngredientMeta>>({})
const tables = ref<TableItem[]>([])
const tablesCountDraft = ref(20)
const shareTenantSlug = computed(() => {
  const sessionSlug = String(store.currentUser?.tenantSlug || '').trim()
  if (sessionSlug) return sessionSlug
  const storeSlug = String(store.activeTenantSlug || '').trim()
  if (storeSlug) return storeSlug
  return 'demo-delivery'
})
const qrBaseUrl = computed(() => `${window.location.origin}/tienda/${encodeURIComponent(shareTenantSlug.value)}`)
const businessDisplayName = computed(() => {
  const fromSession = String(store.currentUser?.tenantName || '').trim()
  if (fromSession) return fromSession
  const fromStore = String(store.activeStorefrontName || '').trim()
  if (fromStore) return fromStore
  return 'Dunamis Store'
})
const businessPublicUrl = computed(() => qrBaseUrl.value)
const selectedTableId = ref<number | null>(null)
const dragTableId = ref<number | null>(null)
const qrPrintModalOpen = ref(false)
const selectedPrintTableIds = ref<number[]>([])
const qrDestinationMode = ref<'general' | 'table'>('table')
const qrLogoDataUrl = ref('')
const SUBSCRIPTION_PLAN_STORAGE_KEY = 'delivery-admin-subscription-plan-v1'
const SUBSCRIPTION_RENEW_STORAGE_KEY = 'delivery-admin-subscription-renew-v1'
const ONBOARDING_STORAGE_KEY = 'delivery-admin-onboarding-v1'
const currentPlan = ref<'takeaway' | 'full' | 'bi'>('full')
const subscriptionRenewAt = ref<number>(Date.now() + (12 * 24 * 60 * 60 * 1000))
const upgradeModalOpen = ref(false)
const blockedTabAttempt = ref<AdminTab | null>(null)
const upgradeRequiredPlan = ref<'takeaway' | 'full' | 'bi'>('full')
const upgradeContextLabel = ref('')
const billingCard = reactive({
  brand: 'Visa',
  last4: '1234',
  holder: 'Dunamis Store',
  expiresAt: '10/28',
})
const billingInvoices = ref<BillingInvoice[]>([
  { id: 'inv-2026-03', periodLabel: 'Marzo 2026', amount: 160000, status: 'pending', issuedAt: new Date('2026-03-01T10:30:00').getTime() },
  { id: 'inv-2026-02', periodLabel: 'Febrero 2026', amount: 160000, status: 'paid', issuedAt: new Date('2026-02-01T10:30:00').getTime() },
  { id: 'inv-2026-01', periodLabel: 'Enero 2026', amount: 160000, status: 'paid', issuedAt: new Date('2026-01-01T10:30:00').getTime() },
  { id: 'inv-2025-12', periodLabel: 'Diciembre 2025', amount: 160000, status: 'paid', issuedAt: new Date('2025-12-01T10:30:00').getTime() },
])
const onboardingOpen = ref(false)
const onboardingStep = ref<1 | 2 | 3 | 4 | 5>(1)
const onboardingAiLoading = ref(false)
const onboardingColorOptions = [
  { key: 'orange', label: 'Naranja Burguer', value: '#F97316' },
  { key: 'green', label: 'Verde Saludable', value: '#10B981' },
  { key: 'red', label: 'Rojo Pizza', value: '#EF4444' },
  { key: 'dark', label: 'Negro Elegante', value: '#111827' },
]
const onboardingCategoryQuick = ['Hamburguesas', 'Pizzas', 'Bebidas', 'Postres', 'Combos']
const onboardingWeekdays = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom']
const onboarding = reactive({
  storeName: '',
  slug: '',
  logoDataUrl: '',
  brandColor: '#10B981',
  phone: '',
  address: '',
  categories: [] as string[],
  hours: Array.from({ length: 7 }, () => ({ enabled: true, from: '09:00', to: '23:00' })),
})
const adminExpenses = ref<AdminExpenseItem[]>([])
const adminExpenseForm = reactive({
  category: 'Servicios',
  amount: 0,
  frequency: 'once' as 'once' | 'monthly',
})
const adminExpenseFilter = ref<'all' | 'fixed' | 'variable'>('all')
const bulkPriceIncreasePct = ref(10)
const productAnalysisModalOpen = ref(false)
const productAnalysisProductId = ref<number | null>(null)
const productAnalysisSalePrice = ref(0)

const tabLabels: Record<typeof activeTab.value, string> = {
  home: 'Inicio del panel',
  orders: 'Gestion de pedidos',
  products: 'Productos',
  inventory: 'Inventario e insumos',
  categories: 'Categorias del menu',
  dailymenus: 'Menus del dia',
  combos: 'Ofertas y combos',
  cashbox: 'Caja y cobranzas',
  customers: 'Clientes',
  audit: 'Auditoria y trazabilidad',
  health: 'Calidad y operacion',
  help: 'Ayuda y legal',
  team: 'Equipo y usuarios',
  roles: 'Equipo y permisos',
  expenses: 'Gastos e insumos',
  coupons: 'Cupones',
  loyalty: 'Fidelizacion',
  tables: 'Gestion de mesas',
  qr: 'Generador de QR',
  billing: 'Suscripcion y planes',
}

const headerTitle = computed(() => (activeTab.value === 'home' ? 'Acciones agrupadas' : 'Panel Administrativo'))
const headerSubtitle = computed(() =>
  activeTab.value === 'home'
    ? 'Navega por tipo de accion desde este modulo.'
    : tabLabels[activeTab.value],
)

const adminHomeButtons: Array<{ key: AdminTab; label: string; hint: string }> = [
  { key: 'home', label: 'Inicio del panel', hint: 'Resumen general del panel.' },
  { key: 'orders', label: 'Gestion de pedidos', hint: 'Seguimiento del flujo de pedidos.' },
  { key: 'products', label: 'Productos', hint: 'Alta, edicion y estado de productos.' },
  { key: 'inventory', label: 'Inventario', hint: 'Insumos, stock y acciones masivas.' },
  { key: 'categories', label: 'Categorias', hint: 'Organizacion del menu y filtros.' },
  { key: 'dailymenus', label: 'Menus del dia', hint: 'Promociones por franja y horario.' },
  { key: 'combos', label: 'Ofertas y combos', hint: 'Gestion de combos y bundles.' },
  { key: 'cashbox', label: 'Caja y cobranzas', hint: 'Cobros, pendientes y cierres.' },
  { key: 'team', label: 'Equipo y usuarios', hint: 'Gestion de usuarios internos.' },
  { key: 'customers', label: 'Clientes', hint: 'CRM y bloqueos.' },
  { key: 'audit', label: 'Auditoria', hint: 'Historial y trazabilidad de acciones.' },
  { key: 'health', label: 'Calidad y operacion', hint: 'Latencia, uptime y errores del sistema.' },
  { key: 'help', label: 'Ayuda y legal', hint: 'FAQ, privacidad y centro de soporte.' },
  { key: 'expenses', label: 'Gastos e insumos', hint: 'Costos y rentabilidad por producto.' },
  { key: 'coupons', label: 'Cupones', hint: 'Descuentos con restricciones y limites.' },
  { key: 'loyalty', label: 'Fidelizacion', hint: 'Retencion y recompensas.' },
  { key: 'tables', label: 'Mesas', hint: 'Configuracion de salon y mesas.' },
  { key: 'qr', label: 'QR salon', hint: 'Enlaces dinamicos por mesa.' },
  { key: 'billing', label: 'Suscripcion y planes', hint: 'Planes, limites y estado de cuenta.' },
]

const sidebarSections: Array<{ title: string; items: Array<{ key: AdminTab; label: string; icon: any }> }> = [
  {
    title: 'Operativo',
    items: [
      { key: 'home', label: 'Inicio', icon: House },
      { key: 'orders', label: 'Pedidos', icon: ShoppingBag },
    ],
  },
  {
    title: 'Gestion',
    items: [
      { key: 'products', label: 'Catalogo', icon: BookOpen },
      { key: 'categories', label: 'Categorias', icon: Layers },
      { key: 'combos', label: 'Combos', icon: PackagePlus },
      { key: 'inventory', label: 'Inventario', icon: BookOpen },
      { key: 'dailymenus', label: 'Menus del dia', icon: ChartNoAxesColumnIncreasing },
    ],
  },
  {
    title: 'Finanzas',
    items: [
      { key: 'cashbox', label: 'Caja', icon: Banknote },
      { key: 'expenses', label: 'Gastos e insumos', icon: CircleDollarSign },
    ],
  },
  {
    title: 'Marketing & Ventas',
    items: [
      { key: 'coupons', label: 'Cupones', icon: Copy },
      { key: 'loyalty', label: 'Fidelizacion', icon: Activity },
    ],
  },
  {
    title: 'Gestion de local',
    items: [
      { key: 'tables', label: 'Gestion de mesas', icon: Users },
      { key: 'qr', label: 'Generador de QR', icon: ShoppingCart },
      { key: 'team', label: 'Equipo', icon: UserCheck },
    ],
  },
  {
    title: 'Seguridad y CRM',
    items: [
      { key: 'customers', label: 'Clientes', icon: Users },
      { key: 'audit', label: 'Auditoria', icon: Activity },
      { key: 'health', label: 'Calidad y operacion', icon: Activity },
    ],
  },
  {
    title: 'Cuenta',
    items: [
      { key: 'billing', label: 'Suscripcion y planes', icon: Wallet },
      { key: 'help', label: 'Ayuda y legal', icon: LifeBuoy },
    ],
  },
]

const planNameMap: Record<'takeaway' | 'full' | 'bi', string> = {
  takeaway: 'Takeaway',
  full: 'Full Operativo',
  bi: 'BI & Marketing',
}

const planAmountByPlan: Record<'takeaway' | 'full' | 'bi', number> = {
  takeaway: 110000,
  full: 160000,
  bi: 230000,
}

const planRankMap: Record<'takeaway' | 'full' | 'bi', number> = {
  takeaway: 1,
  full: 2,
  bi: 3,
}

const tabRequiredPlan: Partial<Record<AdminTab, 'takeaway' | 'full' | 'bi'>> = {
  expenses: 'full',
  audit: 'bi',
  health: 'bi',
  loyalty: 'bi',
}

const isTabLockedByPlan = (tab: AdminTab) => {
  const required = tabRequiredPlan[tab]
  if (!required) return false
  return planRankMap[currentPlan.value] < planRankMap[required]
}

const tabRequiredPlanLabel = (tab: AdminTab) => {
  const required = tabRequiredPlan[tab]
  if (!required) return ''
  return planNameMap[required]
}

const openUpgradeModalForTab = (tab: AdminTab) => {
  blockedTabAttempt.value = tab
  upgradeRequiredPlan.value = tabRequiredPlan[tab] || 'full'
  upgradeContextLabel.value = tabLabels[tab]
  upgradeModalOpen.value = true
}

const openUpgradeModalForPlanGuard = (requiredPlan: 'takeaway' | 'full' | 'bi', contextLabel: string) => {
  blockedTabAttempt.value = null
  upgradeRequiredPlan.value = requiredPlan
  upgradeContextLabel.value = contextLabel
  upgradeModalOpen.value = true
}

const upgradePrimaryCta = computed(() => (upgradeRequiredPlan.value === 'full' ? 'Probar gratis por 7 dias' : 'Mejorar mi plan'))

const switchSubscriptionPlan = (nextPlan: 'takeaway' | 'full' | 'bi') => {
  currentPlan.value = nextPlan
  statusBanner.value = `Plan actualizado: ${planNameMap[nextPlan]}.`
}

const openChangePaymentMethod = () => {
  statusBanner.value = 'Proximamente podras cambiar tu metodo de pago desde Mercado Pago.'
}

const linkMercadoPagoAccount = () => {
  statusBanner.value = 'Cuenta de Mercado Pago vinculada correctamente.'
}

const payNowWithMercadoPago = () => {
  statusBanner.value = `Pago iniciado por $${planAmountByPlan[currentPlan.value].toFixed(0)} con Mercado Pago.`
}

const downloadInvoicePdf = (invoice: BillingInvoice) => {
  statusBanner.value = `Factura ${invoice.periodLabel} lista para descargar (simulado).`
}

function createDefaultRolePermissions(role: RbacRoleKey): Record<RbacModuleKey, Record<RbacActionKey, boolean>> {
  return {
    dashboard: { view: true, edit: role === 'admin', delete: false },
    orders: { view: ['admin', 'employee', 'driver', 'cashier'].includes(role), edit: ['admin', 'employee', 'cashier'].includes(role), delete: role === 'admin' },
    cashbox: { view: ['admin', 'cashier'].includes(role), edit: ['admin', 'cashier'].includes(role), delete: role === 'admin' },
    refunds: { view: ['admin', 'cashier'].includes(role), edit: ['admin', 'cashier'].includes(role), delete: role === 'admin' },
    kitchen: { view: ['admin', 'employee'].includes(role), edit: ['admin', 'employee'].includes(role), delete: role === 'admin' },
    tables: { view: ['admin', 'employee', 'cashier'].includes(role), edit: ['admin', 'employee', 'cashier'].includes(role), delete: role === 'admin' },
    catalog: { view: ['admin', 'employee'].includes(role), edit: ['admin', 'employee'].includes(role), delete: role === 'admin' },
    inventory: { view: ['admin', 'employee'].includes(role), edit: ['admin', 'employee'].includes(role), delete: role === 'admin' },
    team: { view: role === 'admin', edit: role === 'admin', delete: role === 'admin' },
    audit: { view: role === 'admin', edit: role === 'admin', delete: role === 'admin' },
    customers: { view: ['admin', 'cashier'].includes(role), edit: ['admin', 'cashier'].includes(role), delete: role === 'admin' },
    marketing: { view: role === 'admin', edit: role === 'admin', delete: role === 'admin' },
    billing: { view: role === 'admin', edit: role === 'admin', delete: role === 'admin' },
  }
}

function createDefaultRbacMatrix(): RbacMatrixState {
  return {
    admin: createDefaultRolePermissions('admin'),
    employee: createDefaultRolePermissions('employee'),
    driver: createDefaultRolePermissions('driver'),
    cashier: createDefaultRolePermissions('cashier'),
  }
}

const normalizeRbacMatrix = (raw?: Partial<RbacMatrixState> | null): RbacMatrixState => {
  const base = createDefaultRbacMatrix()
  if (!raw || typeof raw !== 'object') return base
  const next = { ...base } as RbacMatrixState
  for (const role of rbacRoles) {
    const roleData = raw[role.key]
    for (const group of rbacMatrixGroups) {
      for (const module of group.modules) {
        for (const action of rbacActions) {
          const value = roleData?.[module.key]?.[action.key]
          if (typeof value === 'boolean') {
            next[role.key][module.key][action.key] = value
          }
        }
      }
    }
    for (const moduleKey of ['dashboard', 'customers', 'marketing', 'billing'] as RbacModuleKey[]) {
      for (const action of rbacActions) {
        const value = roleData?.[moduleKey]?.[action.key]
        if (typeof value === 'boolean') {
          next[role.key][moduleKey][action.key] = value
        }
      }
    }
  }
  return next
}

const resolvedSessionRole = computed<RbacRoleKey>(() => {
  const role = (store.currentUser?.role || 'admin') as string
  if (role === 'employee' || role === 'driver' || role === 'cashier') return role
  return 'admin'
})

const canRoleAccessModule = (role: RbacRoleKey, module: RbacModuleKey, action: RbacActionKey = 'view') =>
  Boolean(rbacMatrixState.value[role]?.[module]?.[action])

const isTabBlockedByPermission = (tab: AdminTab) => {
  const module = tabPermissionModule[tab]
  if (!module) return false
  return !canRoleAccessModule(resolvedSessionRole.value, module, 'view')
}

const visibleSidebarSections = computed(() =>
  sidebarSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => !isTabBlockedByPermission(item.key)),
    }))
    .filter((section) => section.items.length > 0),
)

const visibleAdminHomeButtons = computed(() =>
  adminHomeButtons.filter((item) => !isTabBlockedByPermission(item.key)),
)

const showRbacToast = (message = 'Permiso actualizado correctamente') => {
  rbacToastMessage.value = message
  window.setTimeout(() => {
    if (rbacToastMessage.value === message) {
      rbacToastMessage.value = ''
    }
  }, 1800)
}

const toggleRbacPermission = (role: RbacRoleKey, module: RbacModuleKey, action: RbacActionKey) => {
  if (role === 'admin') return
  rbacMatrixState.value = {
    ...rbacMatrixState.value,
    [role]: {
      ...rbacMatrixState.value[role],
      [module]: {
        ...rbacMatrixState.value[role][module],
        [action]: !rbacMatrixState.value[role][module][action],
      },
    },
  }
  showRbacToast()
}

const quickLogout = async () => {
  await store.logout()
  router.push('/login')
}

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
const editingProductId = ref<number | null>(null)
const isEditingProduct = computed(() => editingProductId.value !== null)
const productModalOpen = ref(false)
const categoryModalOpen = ref(false)
const comboModalOpen = ref(false)
const bundleModalOpen = ref(false)
const categoryDraft = ref('')
const customCategories = ref<string[]>([])
const selectedIngredientIds = ref<number[]>([])
const productExtras = ref<Array<{ id?: number; name: string; additionalPrice: number }>>([])
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
  unit: 'unidad',
  supplier: '',
  additionalPrice: 0,
  stockQuantity: 0,
  minStock: 5,
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
const editingComboId = ref<number | null>(null)
const isEditingCombo = computed(() => editingComboId.value !== null)
const comboSearch = ref('')
const comboImageFile = ref<File | null>(null)
const comboImagePreview = ref('')
const comboItems = ref<Array<{ productId: number; quantity: number }>>([])
const comboProductId = ref(0)
const comboQuantity = ref(1)
const comboCategoryFilter = ref('all')
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
const bundleCategoryFilter = ref('all')
const bundleImageFile = ref<File | null>(null)
const bundleImagePreview = ref('')
const dailyMenuModalOpen = ref(false)
const dailyMenuEditingId = ref<number | null>(null)
const isEditingDailyMenu = computed(() => dailyMenuEditingId.value !== null)
const dailyMenuItemModalOpen = ref(false)
const dailyMenuForm = reactive({
  name: '',
  description: '',
  imageUrl: '',
  isActive: true,
  slot: 'all_day' as 'all_day' | 'lunch' | 'dinner',
  weekdays: [] as number[],
  activeFrom: '',
  activeTo: '',
  priority: 0,
})
const dailyMenuItemForm = reactive({
  dailyMenuId: 0,
  itemType: 'product' as 'product' | 'combo',
  itemId: 0,
  discountPercent: '' as string | number,
  sortOrder: 0,
})
const apiBaseUrl = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'
const CATEGORY_STORAGE_KEY = 'delivery-admin-categories-v1'
const CASHBOX_MOVEMENTS_STORAGE_KEY = 'delivery-admin-cashbox-movements-v1'
const CASHBOX_SESSION_STORAGE_KEY = 'delivery-admin-cashbox-session-v1'
const ROLE_PERMISSIONS_STORAGE_KEY = 'delivery-admin-role-permissions-v1'
const RBAC_MATRIX_STORAGE_KEY = 'delivery-admin-rbac-matrix-v1'
const COUPONS_STORAGE_KEY = 'delivery-admin-coupons-v1'
const PRODUCT_RECIPE_STORAGE_KEY = 'delivery-admin-product-recipes-v1'
const TABLES_STORAGE_KEY = 'delivery-admin-tables-v1'
const INGREDIENT_META_STORAGE_KEY = 'delivery-admin-ingredient-meta-v1'
const ADMIN_EXPENSES_STORAGE_KEY = 'delivery-admin-expenses-v1'

const rbacRoles: Array<{ key: RbacRoleKey; label: string }> = [
  { key: 'admin', label: 'Admin' },
  { key: 'employee', label: 'Cocina' },
  { key: 'driver', label: 'Repartidor' },
  { key: 'cashier', label: 'Encargado' },
]

const rbacActions: Array<{ key: RbacActionKey; label: string }> = [
  { key: 'view', label: 'Ver' },
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Borrar' },
]

const rbacMatrixGroups: Array<{
  area: string
  modules: Array<{ key: RbacModuleKey; label: string; description: string }>
}> = [
  {
    area: 'Ventas',
    modules: [
      { key: 'cashbox', label: 'Caja', description: 'Control operativo de caja y cobranzas.' },
      { key: 'orders', label: 'Historial de pedidos', description: 'Consulta y gestion de pedidos.' },
      { key: 'refunds', label: 'Reembolsos', description: 'Autorizaciones de devolucion de pagos.' },
    ],
  },
  {
    area: 'Operativa',
    modules: [
      { key: 'kitchen', label: 'Panel de cocina', description: 'Flujo de preparacion y despacho.' },
      { key: 'tables', label: 'Gestion de mesas', description: 'Salon, estados y QR por mesa.' },
    ],
  },
  {
    area: 'Configuracion',
    modules: [
      { key: 'catalog', label: 'Precios y catalogo', description: 'Productos, categorias y combos.' },
      { key: 'inventory', label: 'Insumos', description: 'Stock, costos y abastecimiento.' },
      { key: 'team', label: 'Personal', description: 'Equipo, roles y accesos.' },
      { key: 'audit', label: 'Auditoria', description: 'Trazabilidad y actividad del sistema.' },
    ],
  },
]

const tabPermissionModule: Partial<Record<AdminTab, RbacModuleKey>> = {
  home: 'dashboard',
  orders: 'orders',
  products: 'catalog',
  categories: 'catalog',
  combos: 'catalog',
  dailymenus: 'catalog',
  inventory: 'inventory',
  expenses: 'inventory',
  cashbox: 'cashbox',
  team: 'team',
  roles: 'team',
  audit: 'audit',
  health: 'audit',
  help: 'billing',
  customers: 'customers',
  coupons: 'marketing',
  loyalty: 'marketing',
  tables: 'tables',
  qr: 'tables',
  billing: 'billing',
}

const rbacMatrixState = ref<RbacMatrixState>(createDefaultRbacMatrix())
const rbacToastMessage = ref('')

const permissionCatalog: Array<{ category: string; items: PermissionDefinition[] }> = [
  {
    category: 'Ventas',
    items: [
      { key: 'sales.view', title: 'Ver ventas', description: 'Permite consultar pedidos, ventas y detalle de tickets.' },
      { key: 'sales.refund', title: 'Procesar reintegros', description: 'Autoriza cancelar o reintegrar cobros de pedidos.' },
      { key: 'sales.close_shift', title: 'Cerrar caja', description: 'Permite al usuario realizar el arqueo final.' },
    ],
  },
  {
    category: 'Inventario',
    items: [
      { key: 'inventory.view', title: 'Ver inventario', description: 'Consulta stock, alertas y consumos del dia.' },
      { key: 'inventory.edit', title: 'Editar stock', description: 'Habilita ajustar cantidades y costos de insumos.' },
      { key: 'inventory.bulk', title: 'Acciones masivas', description: 'Permite aplicar cambios masivos en productos.' },
    ],
  },
  {
    category: 'Caja',
    items: [
      { key: 'cashbox.open_close', title: 'Apertura y cierre', description: 'Permite abrir o cerrar caja operativa.' },
      { key: 'cashbox.manual_moves', title: 'Nuevo movimiento', description: 'Habilita registrar ingresos y egresos manuales.' },
      { key: 'cashbox.audit_detail', title: 'Detalle de ventas', description: 'Autoriza revisar lineas de venta en el slide-over.' },
    ],
  },
]

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
const orderCashMovements = computed<CashMovement[]>(() =>
  store.orders
    .flatMap((order) => {
      if (order.paymentStatus === 'paid') {
        return [
          {
            id: `order-income-${order.id}`,
            type: 'income' as const,
            source: 'order' as const,
            concept: `Venta ${orderCode(order.id)} - ${order.customer}`,
            amount: Number(order.total || 0),
            createdAt: order.createdAt,
            orderId: order.id,
            customer: order.customer,
          },
        ]
      }
      if (order.paymentStatus === 'refunded') {
        return [
          {
            id: `order-refund-${order.id}`,
            type: 'expense' as const,
            source: 'order' as const,
            concept: `Reintegro ${orderCode(order.id)} - ${order.customer}`,
            amount: Number(order.total || 0),
            createdAt: order.createdAt,
            orderId: order.id,
            customer: order.customer,
          },
        ]
      }
      return []
    })
    .filter((movement) => movement.amount > 0),
)
const allCashMovements = computed<CashMovement[]>(() =>
  [...orderCashMovements.value, ...cashManualMovements.value].sort((a, b) => b.createdAt - a.createdAt),
)
const selectedCashOrder = computed(() => {
  if (!selectedCashMovement.value?.orderId) return null
  return store.orders.find((order) => order.id === selectedCashMovement.value?.orderId) || null
})
const filteredCashMovements = computed(() => {
  const now = new Date()
  let from = 0
  let to = Number.MAX_SAFE_INTEGER
  if (cashFilterPreset.value === 'today') {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
    from = start
    to = now.getTime()
  } else if (cashFilterPreset.value === 'yesterday') {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1).getTime()
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - 1
    from = start
    to = end
  } else if (cashFilterPreset.value === 'week') {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6).getTime()
    from = start
    to = now.getTime()
  } else if (cashFilterPreset.value === 'custom') {
    const fromRaw = cashDateFrom.value ? new Date(`${cashDateFrom.value}T00:00:00`).getTime() : 0
    const toRaw = cashDateTo.value ? new Date(`${cashDateTo.value}T23:59:59`).getTime() : Number.MAX_SAFE_INTEGER
    from = Number.isFinite(fromRaw) ? fromRaw : 0
    to = Number.isFinite(toRaw) ? toRaw : Number.MAX_SAFE_INTEGER
  }

  return allCashMovements.value.filter((movement) => {
    const byDate = movement.createdAt >= from && movement.createdAt <= to
    if (!byDate) return false
    if (cashShiftFilter.value === 'all') return true
    const hour = new Date(movement.createdAt).getHours()
    if (cashShiftFilter.value === 'morning') return hour >= 6 && hour < 12
    if (cashShiftFilter.value === 'afternoon') return hour >= 12 && hour < 18
    return hour >= 18 || hour < 6
  })
})
const cashBalanceStats = computed(() => {
  const income = filteredCashMovements.value
    .filter((movement) => movement.type === 'income')
    .reduce((acc, movement) => acc + movement.amount, 0)
  const expense = filteredCashMovements.value
    .filter((movement) => movement.type === 'expense')
    .reduce((acc, movement) => acc + movement.amount, 0)
  return {
    income,
    expense,
    total: income - expense,
  }
})
const adminFixedExpensesTotal = computed(() => adminExpenses.value.reduce((acc, item) => acc + Number(item.amount || 0), 0))
const netCashBalance = computed(() => cashBalanceStats.value.total - adminFixedExpensesTotal.value)
const ingredientUnitCost = (ingredient: IngredientItem) => {
  const meta = ingredientMetaFor(ingredient.id)
  return Number(meta.purchasePrice || ingredient.unitCost || ingredient.additionalPrice || 0)
}
const ingredientMinimumStock = (ingredient: IngredientItem) => Math.max(0, Number(ingredientMetaFor(ingredient.id).minStock || 0))
const ingredientSuggestedPrice = (ingredient: IngredientItem) => ingredientUnitCost(ingredient) * 2.85
const ingredientStockPct = (ingredient: IngredientItem) => {
  const stock = Number(ingredient.stockQuantity || 0)
  const min = ingredientMinimumStock(ingredient)
  const target = Math.max(10, min > 0 ? min * 2 : stock > 0 ? stock : 10)
  if (target <= 0) return 0
  return Math.max(0, Math.min(100, (stock / target) * 100))
}
const ingredientStockBarClass = (ingredient: IngredientItem) => {
  const stock = Number(ingredient.stockQuantity || 0)
  const min = ingredientMinimumStock(ingredient)
  if (stock <= min) return 'bg-rose-500'
  if (stock <= min * 1.4) return 'bg-amber-500'
  return 'bg-emerald-500'
}
const ingredientStockDotClass = (ingredient: IngredientItem) => {
  const stock = Number(ingredient.stockQuantity || 0)
  const min = ingredientMinimumStock(ingredient)
  if (stock <= min) return 'bg-rose-500'
  if (stock <= min * 1.4) return 'bg-amber-500'
  return 'bg-emerald-500'
}
const ingredientPriceTrendLabel = (ingredient: IngredientItem) => {
  const meta = ingredientMetaFor(ingredient.id)
  if (meta.priceTrend === 'up') return `Subio desde ${moneyLabel(meta.previousPurchasePrice)}`
  if (meta.priceTrend === 'down') return `Bajo desde ${moneyLabel(meta.previousPurchasePrice)}`
  return 'Sin cambios en la ultima carga'
}
const ingredientPriceTrendClass = (ingredient: IngredientItem) => {
  const meta = ingredientMetaFor(ingredient.id)
  if (meta.priceTrend === 'up') return 'text-rose-600'
  if (meta.priceTrend === 'down') return 'text-emerald-600'
  return 'text-slate-400'
}
const ingredientProfitPct = (ingredient: IngredientItem) => {
  const cost = ingredientUnitCost(ingredient)
  const suggested = ingredientSuggestedPrice(ingredient)
  if (suggested <= 0) return 0
  return ((suggested - cost) / suggested) * 100
}
const ingredientCards = computed(() =>
  store.ingredients.map((ingredient) => {
    const cost = ingredientUnitCost(ingredient)
    const suggested = ingredientSuggestedPrice(ingredient)
    const basePrice = Math.max(1, Number(ingredient.additionalPrice || ingredient.unitCost || 0))
    const inflation = ((cost - basePrice) / basePrice) * 100
    return {
      ...ingredient,
      cost,
      suggested,
      inflation,
      profitPct: ingredientProfitPct(ingredient),
      stockPct: ingredientStockPct(ingredient),
    }
  }),
)
const ingredientInflationAlerts = computed(() =>
  ingredientCards.value
    .filter((ingredient) => ingredient.inflation > 10)
    .sort((a, b) => b.inflation - a.inflation)
    .slice(0, 4),
)
const marginAvgStore = computed(() => {
  if (!productMarginHealth.value.length) return 0
  const total = productMarginHealth.value.reduce((acc, row) => acc + row.margin, 0)
  return total / productMarginHealth.value.length
})
const marginAvgStorePieStyle = computed(() => {
  const clamped = Math.max(0, Math.min(100, marginAvgStore.value))
  return {
    background: `conic-gradient(rgb(16 185 129) 0% ${clamped}%, rgb(226 232 240) ${clamped}% 100%)`,
  }
})
const ingredientInventoryMonthlyCost = computed(() =>
  ingredientCards.value.reduce((acc, ingredient) => acc + (ingredient.cost * Number(ingredient.stockQuantity || 0)), 0),
)
const ingredientCriticalStockCount = computed(() =>
  ingredientCards.value.filter((ingredient) => Number(ingredient.stockQuantity || 0) <= ingredientMinimumStock(ingredient)).length,
)
const selectedRecipeLines = computed(() => productRecipeMap.value[String(selectedRecipeProductId.value)] || [])
const selectedRecipeCostTotal = computed(() => productRecipeCost(selectedRecipeProductId.value))
const selectedRecipeMargin = computed(() => {
  const product = store.getProduct(selectedRecipeProductId.value)
  const price = Number(product?.price || 0)
  if (!price) return 0
  return ((price - selectedRecipeCostTotal.value) / price) * 100
})
const selectedRecipeBreakdown = computed(() => {
  const lines = selectedRecipeLines.value
  const rows = lines.map((line) => {
    const total = recipeIngredientCost(line.ingredientId) * Number(line.qty || 0)
    return {
      ingredientId: line.ingredientId,
      total,
    }
  })
  return rows.sort((a, b) => b.total - a.total)
})
const selectedRecipeDominantShare = computed(() => {
  if (!selectedRecipeBreakdown.value.length || selectedRecipeCostTotal.value <= 0) return 0
  return (selectedRecipeBreakdown.value[0].total / selectedRecipeCostTotal.value) * 100
})
const selectedRecipePieStyle = computed(() => ({
  background: `conic-gradient(rgb(14 116 144) 0% ${Math.max(0, Math.min(100, selectedRecipeDominantShare.value))}%, rgb(226 232 240) ${Math.max(0, Math.min(100, selectedRecipeDominantShare.value))}% 100%)`,
}))
const profitabilityIngredients = computed(() =>
  store.ingredients.map((ingredient) => {
    const meta = ingredientMetaFor(ingredient.id)
    return {
      id: ingredient.id,
      name: ingredient.name,
      unit: meta.unit || 'unidad',
      purchasePrice: Number(meta.purchasePrice || 0),
    }
  }),
)
const recipeIngredientCost = (ingredientId: number) => {
  const meta = ingredientMetaMap.value[String(ingredientId)]
  if (meta) return Number(meta.purchasePrice || 0)
  const ingredient = store.ingredients.find((item) => item.id === ingredientId)
  if (!ingredient) return 0
  return Number(ingredient.unitCost ?? ingredient.additionalPrice ?? 0)
}
const productRecipeCost = (productId: number) => {
  const lines = productRecipeMap.value[String(productId)] || []
  return lines.reduce((acc, line) => acc + (recipeIngredientCost(line.ingredientId) * Number(line.qty || 0)), 0)
}
const productMarginHealth = computed(() =>
  store.products.map((product) => {
    const cost = productRecipeCost(product.id)
    const price = Number(product.price || 0)
    const margin = price > 0 ? ((price - cost) / price) * 100 : 0
    return {
      id: product.id,
      name: product.name,
      price,
      cost,
      margin,
    }
  }),
)
const productAnalysisItem = computed(() => {
  if (!productAnalysisProductId.value) return null
  return store.getProduct(productAnalysisProductId.value) || null
})
const productAnalysisLines = computed(() => {
  if (!productAnalysisProductId.value) return []
  return productRecipeMap.value[String(productAnalysisProductId.value)] || []
})
const productAnalysisCost = computed(() =>
  productAnalysisLines.value.reduce((acc, line) => acc + (recipeIngredientCost(line.ingredientId) * Number(line.qty || 0)), 0),
)
const productAnalysisMargin = computed(() => {
  const sale = Number(productAnalysisSalePrice.value || 0)
  if (!sale) return 0
  return ((sale - productAnalysisCost.value) / sale) * 100
})
const productAnalysisBreakdownText = computed(() => {
  if (!productAnalysisLines.value.length) return 'Sin insumos asociados todavía.'
  return productAnalysisLines.value
    .map((line) => {
      const name = store.getIngredient(line.ingredientId)?.name || `Insumo #${line.ingredientId}`
      const total = recipeIngredientCost(line.ingredientId) * Number(line.qty || 0)
      return `${name}: ${moneyLabel(total)}`
    })
    .join(' | ')
})
const productAnalysisPriceMin = computed(() => Math.max(1000, Math.round(productAnalysisCost.value * 1.15)))
const productAnalysisPriceMax = computed(() => {
  const base = Number(productAnalysisItem.value?.price || 0)
  return Math.max(productAnalysisPriceMin.value + 500, Math.round(base * 2.2), 12000)
})
const selectedRecipeSuggestedPrice = computed(() => {
  const targetMargin = 0.6
  if (selectedRecipeCostTotal.value <= 0) return 0
  return selectedRecipeCostTotal.value / (1 - targetMargin)
})
const filteredAdminExpenses = computed(() => {
  if (adminExpenseFilter.value === 'all') return adminExpenses.value
  return adminExpenses.value.filter((item) => item.kind === adminExpenseFilter.value)
})
const expensePieSegments = computed(() => {
  const month = new Date().getMonth()
  const year = new Date().getFullYear()
  const rows = adminExpenses.value.filter((item) => {
    const date = new Date(item.createdAt)
    return date.getMonth() === month && date.getFullYear() === year
  })
  const total = rows.reduce((acc, item) => acc + Number(item.amount || 0), 0)
  const byCategory = rows.reduce<Record<string, number>>((acc, item) => {
    acc[item.category] = (acc[item.category] || 0) + Number(item.amount || 0)
    return acc
  }, {})
  return Object.entries(byCategory).map(([category, amount]) => ({
    category,
    amount,
    pct: total > 0 ? (amount / total) * 100 : 0,
  }))
})
const expensePieStyle = computed(() => {
  if (!expensePieSegments.value.length) {
    return { background: 'conic-gradient(rgb(226 232 240) 0% 100%)' }
  }
  const palette = ['#10B981', '#F59E0B', '#0EA5E9', '#6366F1', '#EF4444']
  let cursor = 0
  const slices = expensePieSegments.value.map((segment, index) => {
    const from = cursor
    const to = cursor + segment.pct
    cursor = to
    return `${palette[index % palette.length]} ${from}% ${to}%`
  })
  return { background: `conic-gradient(${slices.join(', ')})` }
})
const tablesPreviewLink = (tableId: number) => `${qrBaseUrl.value}?mesa=${tableId}`
const generalMenuLink = computed(() => qrBaseUrl.value)
const qrTargetLink = computed(() => {
  if (qrDestinationMode.value === 'general') return generalMenuLink.value
  if (!selectedTableId.value) return ''
  return tablesPreviewLink(selectedTableId.value)
})
const tableQrPreviewUrl = computed(() => {
  if (!qrTargetLink.value) return ''
  return `https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(qrTargetLink.value)}`
})

const extractOrderTableId = (order: Order) => {
  const text = `${order.address || ''} ${order.customer || ''}`.toLowerCase()
  const match = text.match(/mesa\s*#?\s*(\d{1,3})/)
  if (!match?.[1]) return null
  return Number(match[1])
}

const orderTableLabel = (order: Order) => {
  const tableId = extractOrderTableId(order)
  if (!tableId) return ''
  return `MESA ${tableId}`
}

const tableActiveOrders = (tableId: number) =>
  store.orders
    .filter((order) => {
      if (['delivered', 'canceled', 'rejected'].includes(order.status)) return false
      return extractOrderTableId(order) === tableId
    })
    .sort((a, b) => a.createdAt - b.createdAt)

const hasActiveTableOrder = (tableId: number) => tableActiveOrders(tableId).length > 0

const tableElapsedText = (tableId: number) => {
  const first = tableActiveOrders(tableId)[0]
  if (!first) return 'Sin consumo activo'
  const minutes = Math.max(1, Math.round((Date.now() - first.createdAt) / 60000))
  return `${minutes} min`
}

const tableOpenTotal = (tableId: number) =>
  tableActiveOrders(tableId).reduce((acc, order) => acc + Number(order.total || 0), 0)

const tableHasKitchenPending = (tableId: number) =>
  tableActiveOrders(tableId).some((order) => ['received', 'preparing'].includes(order.status))

const tableNeedsCheck = (tableId: number) =>
  tableActiveOrders(tableId).some((order) => order.status === 'ready')

const tableStatusLabel = (tableId: number) => {
  if (!hasActiveTableOrder(tableId)) return 'Libre'
  if (tableNeedsCheck(tableId)) return 'Cuenta pedida'
  return 'Ocupada'
}

const tableStatusClass = (tableId: number) => {
  if (!hasActiveTableOrder(tableId)) return 'bg-emerald-100 text-emerald-700'
  if (tableNeedsCheck(tableId)) return 'bg-amber-100 text-amber-800'
  return 'bg-sky-100 text-sky-700'
}

const couponIsExpired = (coupon: CouponItem) => {
  if (!coupon.expiresAt) return false
  const end = new Date(`${coupon.expiresAt}T23:59:59`).getTime()
  return Date.now() > end
}

const couponStatusLabel = (coupon: CouponItem) => {
  if (couponIsExpired(coupon)) return 'Expirado'
  return coupon.active ? 'Activo' : 'Pausado'
}

const couponStatusClass = (coupon: CouponItem) => {
  if (couponIsExpired(coupon)) return 'bg-slate-100 text-slate-600'
  return coupon.active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
}

const couponUsagePercent = (coupon: CouponItem) => {
  const max = Math.max(1, Number(coupon.totalUsesLimit || 1))
  return Math.max(0, Math.min(100, (Number(coupon.usedCount || 0) / max) * 100))
}

const couponTypeCardClass = (coupon: CouponItem) => {
  if (coupon.kind === 'fixed') return 'bg-emerald-50 border-emerald-200 border-r-emerald-400'
  if (coupon.kind === 'free_shipping') return 'bg-amber-50 border-amber-200 border-r-amber-400'
  return 'bg-sky-50 border-sky-200 border-r-sky-400'
}

const couponTypeValueClass = (coupon: CouponItem) => {
  if (coupon.kind === 'fixed') return 'text-emerald-700'
  if (coupon.kind === 'free_shipping') return 'text-amber-700'
  return 'text-sky-700'
}

const couponValueLabel = (coupon: CouponItem) => {
  if (coupon.kind === 'percentage') return `${coupon.value}% OFF`
  if (coupon.kind === 'fixed') return `$${coupon.value} OFF`
  return 'Envio gratis'
}
const ingredientMetaFor = (ingredientId: number): IngredientMeta => {
  const key = String(ingredientId)
  const current = ingredientMetaMap.value[key]
  if (current) return current
  const basePrice = recipeIngredientCost(ingredientId)
  return {
    unit: 'unidad',
    supplier: '',
    purchasePrice: basePrice,
    minStock: 5,
    previousPurchasePrice: basePrice,
    priceTrend: 'same',
  }
}
const productCategories = computed(() => {
  const persistedCategories = Array.isArray(customCategories.value) ? customCategories.value : []
  const categories = new Set([
    ...store.products.map((product) => (product.category || '').trim()).filter(Boolean),
    ...persistedCategories.map((value) => value.trim()).filter(Boolean),
  ])
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
const monthlyOrdersCount = computed(() => {
  const now = new Date()
  return store.orders.filter((order) => {
    const created = new Date(order.createdAt)
    return created.getFullYear() === now.getFullYear() && created.getMonth() === now.getMonth()
  }).length
})
const planLimits = computed(() => {
  if (currentPlan.value === 'takeaway') {
    return { drivers: 2, orders: 250, tables: 20 }
  }
  if (currentPlan.value === 'full') {
    return { drivers: 5, orders: 1200, tables: 80 }
  }
  return { drivers: 20, orders: 5000, tables: 250 }
})
const usageRows = computed(() => ([
  { key: 'Repartidores', used: store.activeDrivers.length, limit: planLimits.value.drivers },
  { key: 'Pedidos del mes', used: monthlyOrdersCount.value, limit: planLimits.value.orders },
  { key: 'Mesas QR', used: tables.value.length, limit: planLimits.value.tables },
]))
const daysToRenew = computed(() => {
  const diff = subscriptionRenewAt.value - Date.now()
  return Math.max(0, Math.ceil(diff / (24 * 60 * 60 * 1000)))
})
const recentOrders24h = computed(() => {
  const windowStart = Date.now() - (24 * 60 * 60 * 1000)
  return store.orders.filter((order) => order.createdAt >= windowStart)
})
const paymentIssues24h = computed(() => {
  const now = Date.now()
  return recentOrders24h.value.filter((order) => {
    if (order.paymentStatus === 'refunded') return true
    if (order.paymentStatus === 'pending' && now - order.createdAt > 30 * 60 * 1000) return true
    return false
  }).length
})
const reportedErrors24h = computed(() => {
  const from = Date.now() - (24 * 60 * 60 * 1000)
  const auditErrors = store.auditLogs.filter((log) => {
    if (log.createdAt < from) return false
    const action = log.action.toLowerCase()
    return action.includes('error') || action.includes('failed') || action.includes('rejected')
  }).length
  return paymentIssues24h.value + auditErrors
})
const serverLatencyMs = computed(() => {
  const base = store.realtimeConnected ? 68 : 190
  const trafficPenalty = Math.min(180, activeOrders.value * 6)
  const paymentPenalty = paymentIssues24h.value * 7
  return Math.round(base + trafficPenalty + paymentPenalty)
})
const uptimePercent7d = computed(() => {
  const penalty = (store.realtimeConnected ? 0 : 0.6) + Math.min(2.8, reportedErrors24h.value * 0.12)
  const uptime = 99.95 - penalty
  return Math.max(94, Number(uptime.toFixed(2)))
})
const latencyStatus = computed<'ok' | 'warn' | 'critical'>(() => {
  if (serverLatencyMs.value <= 120) return 'ok'
  if (serverLatencyMs.value <= 220) return 'warn'
  return 'critical'
})
const uptimeStatus = computed<'ok' | 'warn' | 'critical'>(() => {
  if (uptimePercent7d.value >= 99.5) return 'ok'
  if (uptimePercent7d.value >= 98) return 'warn'
  return 'critical'
})
const errorStatus = computed<'ok' | 'warn' | 'critical'>(() => {
  if (reportedErrors24h.value <= 1) return 'ok'
  if (reportedErrors24h.value <= 4) return 'warn'
  return 'critical'
})
const metricToneClass = (status: 'ok' | 'warn' | 'critical') => {
  if (status === 'ok') return 'text-emerald-700'
  if (status === 'warn') return 'text-amber-700'
  return 'text-rose-700'
}
const metricPillClass = (status: 'ok' | 'warn' | 'critical') => {
  if (status === 'ok') return 'bg-emerald-100 text-emerald-700'
  if (status === 'warn') return 'bg-amber-100 text-amber-700'
  return 'bg-rose-100 text-rose-700'
}
const incidentsFeed = computed(() => {
  const rows: Array<{ id: string; title: string; detail: string; severity: 'ok' | 'warn' | 'critical'; when: string }> = []
  if (!store.realtimeConnected) {
    rows.push({
      id: 'rt-disconnected',
      title: 'Canal realtime inestable',
      detail: store.realtimeError || 'No se pudo confirmar sincronizacion en vivo.',
      severity: 'critical',
      when: 'Ahora',
    })
  }
  if (paymentIssues24h.value > 0) {
    rows.push({
      id: 'payments-issues',
      title: 'Incidencias de pago detectadas',
      detail: `${paymentIssues24h.value} pedidos con cobro pendiente >30min o reintegro en 24h.`,
      severity: paymentIssues24h.value > 4 ? 'critical' : 'warn',
      when: 'Ultimas 24h',
    })
  }
  if (!rows.length) {
    rows.push({
      id: 'ops-stable',
      title: 'Operacion estable',
      detail: 'No se detectaron incidentes criticos en las ultimas 24h.',
      severity: 'ok',
      when: 'Ultimas 24h',
    })
  }
  return rows
})
const heartbeatLatencyLabel = computed(() => `${serverLatencyMs.value}ms`)
const heartbeatLatencyTone = computed<'ok' | 'warn' | 'critical'>(() => {
  if (serverLatencyMs.value <= 100) return 'ok'
  if (serverLatencyMs.value <= 180) return 'warn'
  return 'critical'
})
const heartbeatPaymentsTone = computed<'ok' | 'warn' | 'critical'>(() => {
  if (paymentIssues24h.value === 0) return 'ok'
  if (paymentIssues24h.value <= 2) return 'warn'
  return 'critical'
})
const statusDotClass = (tone: 'ok' | 'warn' | 'critical') => {
  if (tone === 'ok') return 'bg-emerald-500'
  if (tone === 'warn') return 'bg-amber-500'
  return 'bg-rose-500'
}
const uptime30dSegments = computed(() => {
  const rows: Array<{ date: string; availability: number; tone: 'ok' | 'warn' | 'critical'; tooltip: string }> = []
  const now = new Date()
  for (let i = 29; i >= 0; i -= 1) {
    const d = new Date(now)
    d.setDate(now.getDate() - i)
    const dayIndex = 29 - i
    const baseline = uptimePercent7d.value
    const wave = Math.sin((dayIndex + 2) / 4) * 0.22
    const penaltyFromErrors = reportedErrors24h.value * 0.03
    const availability = Math.max(94, Math.min(100, Number((baseline + wave - penaltyFromErrors).toFixed(2))))
    const tone: 'ok' | 'warn' | 'critical' = availability >= 99.5 ? 'ok' : availability >= 98.5 ? 'warn' : 'critical'
    const dateLabel = d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' })
    rows.push({
      date: dateLabel,
      availability,
      tone,
      tooltip: `${dateLabel} - ${availability}% ${tone === 'ok' ? 'Sin incidentes' : tone === 'warn' ? 'Con alertas' : 'Con incidentes'}`,
    })
  }
  return rows
})
const browserSnapshot = computed(() => ({
  userAgent: navigator.userAgent,
  language: navigator.language || 'es-AR',
  platform: navigator.platform || 'web',
  appVersion: import.meta.env.VITE_APP_VERSION || 'dunamis-demo',
  route: route.path,
  realtime: store.realtimeConnected ? 'online' : `offline:${store.realtimeError || 'sin-detalle'}`,
}))

const submitSupportIssue = () => {
  statusBanner.value = 'Reporte enviado al Centro de Ayuda con diagnostico tecnico.'
  supportIssueDraft.value = ''
  supportModalOpen.value = false
}

const legalHighlights = [
  {
    key: 'privacy-owner',
    title: 'Tus datos son tuyos',
    description: 'No vendemos informacion de tu negocio ni de tus clientes.',
  },
  {
    key: 'cancel-anytime',
    title: 'Cancelas cuando quieras',
    description: 'No hay permanencia forzada ni penalidades ocultas.',
  },
  {
    key: 'service-uptime',
    title: 'Compromiso de disponibilidad',
    description: 'Publicamos estado tecnico y tiempos de respuesta del soporte.',
  },
] as const

const faqEntries = [
  {
    id: 1,
    question: 'Como cambio el precio de un combo?',
    answer: 'Ingresa a Catalogo > Combos, selecciona el combo y edita el precio base. El cambio impacta de inmediato en tienda y QR.',
  },
  {
    id: 2,
    question: 'Como pauso productos sin borrarlos?',
    answer: 'En Productos puedes desactivar temporalmente un item para ocultarlo del menu sin perder historial ni configuracion.',
  },
  {
    id: 3,
    question: 'Como reviso cobros rechazados?',
    answer: 'Desde Caja y en Calidad/Operacion veras incidencias de pago pendientes o reintegradas para seguimiento rapido.',
  },
  {
    id: 4,
    question: 'Como agrego un nuevo usuario al equipo?',
    answer: 'Ve a Equipo y usuarios, crea el integrante y asigna rol. El RBAC controla automaticamente sus permisos visibles.',
  },
] as const

const helpVideoCards = [
  { id: 'vid-1', title: 'Actualizar precios en 30s', duration: '00:30', description: 'Ajusta precios por producto o categoria.' },
  { id: 'vid-2', title: 'Crear combos y ofertas', duration: '00:34', description: 'Arma promociones sin tocar codigo.' },
  { id: 'vid-3', title: 'Gestion de caja diaria', duration: '00:28', description: 'Apertura, cierre y conciliacion rapida.' },
  { id: 'vid-4', title: 'Configurar permisos por rol', duration: '00:32', description: 'Control de accesos para equipo.' },
] as const

const toggleHelpFaq = (id: number) => {
  helpFaqOpenId.value = helpFaqOpenId.value === id ? null : id
}

const exportMyInformation = (format: 'json' | 'csv' = 'json') => {
  const payload = {
    exportedAt: new Date().toISOString(),
    tenant: onboarding.storeName || 'Dunamis Store',
    user: store.currentUser?.name || 'admin',
    products: store.products,
    orders: store.orders,
    customers: store.customerInsights,
    roles: store.roles,
    users: store.users,
  }
  const dateTag = new Date().toISOString().slice(0, 10)
  if (format === 'csv') {
    const lines = [
      'tipo,id,nombre,detalle',
      ...store.products.map((p) => `producto,${p.id},"${p.name}",${p.price}`),
      ...store.orders.map((o) => `pedido,${o.id},"${o.customer}",${o.total}`),
    ]
    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `dunamis-export-${dateTag}.csv`
    link.click()
    URL.revokeObjectURL(url)
    statusBanner.value = 'Exportacion CSV iniciada.'
    return
  }
  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `dunamis-export-${dateTag}.json`
  link.click()
  URL.revokeObjectURL(url)
  statusBanner.value = 'Exportacion JSON iniciada.'
}

const openLegalTopic = (topic: LegalTopic) => {
  helpLegalFocus.value = topic
  goToTab('help')
}
const nextDebitDateLabel = computed(() =>
  new Date(subscriptionRenewAt.value).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }),
)
const billingCardStatus = computed<'active' | 'expiring'>(() => (daysToRenew.value <= 10 ? 'expiring' : 'active'))
const billingCardStatusLabel = computed(() => (billingCardStatus.value === 'active' ? 'ACTIVA' : `PROXIMO VENCIMIENTO: ${nextDebitDateLabel.value}`))
const usagePercent = (used: number, limit: number) => {
  if (limit <= 0) return 0
  return Math.max(0, Math.min(100, (used / limit) * 100))
}

const invoiceStatusLabel = (status: BillingInvoice['status']) => (status === 'paid' ? 'Pagado' : 'Pendiente')
const invoiceStatusClass = (status: BillingInvoice['status']) =>
  status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
const invoiceIssueDate = (timestamp: number) =>
  new Date(timestamp).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
const onboardingProgress = computed(() => {
  if (onboardingStep.value >= 5) return 100
  return Math.round((onboardingStep.value / 4) * 100)
})
const onboardingMapEmbedUrl = computed(() => {
  const query = onboarding.address.trim()
  if (!query) return ''
  return `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`
})
const onboardingPreviewSlug = computed(() => {
  const base = (onboarding.slug || '').trim().toLowerCase()
  return base || 'mi-local'
})
const onboardingCanContinue = computed(() => {
  if (onboardingStep.value === 1) return onboarding.storeName.trim().length >= 3
  if (onboardingStep.value === 2) return onboarding.address.trim().length >= 5
  if (onboardingStep.value === 3) return onboarding.categories.length > 0
  return true
})

const onOnboardingStoreNameInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  onboarding.storeName = target.value
  if (!onboarding.slug.trim()) {
    onboarding.slug = target.value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }
}

const onOnboardingLogoInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    onboarding.logoDataUrl = typeof reader.result === 'string' ? reader.result : ''
  }
  reader.readAsDataURL(file)
}

const toggleOnboardingCategory = (category: string) => {
  if (onboarding.categories.includes(category)) {
    onboarding.categories = onboarding.categories.filter((item) => item !== category)
    return
  }
  onboarding.categories = [...onboarding.categories, category]
}

const copyHoursToAll = () => {
  const first = onboarding.hours[0]
  onboarding.hours = onboarding.hours.map(() => ({ ...first }))
}

const runMenuAiImport = () => {
  onboardingAiLoading.value = true
  window.setTimeout(() => {
    onboardingAiLoading.value = false
    statusBanner.value = 'Lectura de carta simulada: se detectaron categorias base.'
    if (!onboarding.categories.length) {
      onboarding.categories = ['Hamburguesas', 'Bebidas']
    }
  }, 1200)
}

const nextOnboardingStep = () => {
  if (!onboardingCanContinue.value) return
  if (onboardingStep.value >= 5) return
  onboardingStep.value = (onboardingStep.value + 1) as 1 | 2 | 3 | 4 | 5
}

const prevOnboardingStep = () => {
  if (onboardingStep.value <= 1) return
  onboardingStep.value = (onboardingStep.value - 1) as 1 | 2 | 3 | 4 | 5
}

const completeOnboarding = () => {
  onboardingStep.value = 5
  localStorage.setItem(ONBOARDING_STORAGE_KEY, 'completed')
}

const openStorePreview = () => {
  window.open(qrBaseUrl.value, '_blank', 'noopener,noreferrer')
}

const openBusinessSettings = () => {
  onboardingOpen.value = true
  onboardingStep.value = 1
}

const copyBusinessUrl = async () => {
  try {
    await navigator.clipboard.writeText(businessPublicUrl.value)
    statusBanner.value = 'URL publica del negocio copiada.'
  } catch {
    statusBanner.value = 'No se pudo copiar la URL del negocio.'
  }
}

const finishOnboardingToPanel = () => {
  onboardingOpen.value = false
  goToTab('home')
  statusBanner.value = 'Configuracion inicial completada. Tu tienda ya esta online.'
}

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

const comboSelectableProducts = computed(() =>
  store.products.filter((product) => {
    if (comboCategoryFilter.value === 'all') return true
    return (product.category || 'Sin categoria') === comboCategoryFilter.value
  }),
)

const bundleSelectableProducts = computed(() =>
  store.products.filter((product) => {
    if (bundleCategoryFilter.value === 'all') return true
    return (product.category || 'Sin categoria') === bundleCategoryFilter.value
  }),
)

const filteredCombos = computed(() => {
  const query = comboSearch.value.trim().toLowerCase()
  const list = [...store.combos].sort((a, b) => b.id - a.id)
  if (!query) return list
  return list.filter((combo) => combo.name.toLowerCase().includes(query) || String(combo.id).includes(query))
})

const weekdayOptions = [
  { value: 1, label: 'Lun' },
  { value: 2, label: 'Mar' },
  { value: 3, label: 'Mie' },
  { value: 4, label: 'Jue' },
  { value: 5, label: 'Vie' },
  { value: 6, label: 'Sab' },
  { value: 7, label: 'Dom' },
]

const selectedDailyItemOptions = computed(() => {
  if (dailyMenuItemForm.itemType === 'combo') {
    return store.combos.map((item) => ({ id: item.id, name: item.name }))
  }
  return store.products.map((item) => ({ id: item.id, name: item.name }))
})

const selectedDailyItemBasePrice = computed(() => {
  if (!dailyMenuItemForm.itemId) return 0
  if (dailyMenuItemForm.itemType === 'combo') {
    return Number(store.combos.find((item) => item.id === dailyMenuItemForm.itemId)?.basePrice || 0)
  }
  return Number(store.getProduct(dailyMenuItemForm.itemId)?.price || 0)
})

const computedDailyPromoPrice = computed(() => {
  const base = selectedDailyItemBasePrice.value
  const rawDiscount = Number(dailyMenuItemForm.discountPercent || 0)
  const discount = Math.max(0, Math.min(100, rawDiscount))
  if (!base || discount <= 0) return null
  return Number((base * (1 - (discount / 100))).toFixed(2))
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
  if (minutes >= 20) return 'ring-1 ring-rose-200 kds-critical-hard'
  if (minutes >= 15) return 'ring-1 ring-rose-200 kds-critical'
  if (minutes >= 8) return 'ring-1 ring-amber-200'
  return 'ring-1 ring-slate-200/60'
}

const itemRemovedIngredients = (item: Order['items'][number]) => {
  return (item.excludedIngredientIds || [])
    .map((id) => store.getIngredient(id)?.name || `Ingrediente #${id}`)
    .filter(Boolean)
}

const itemExtras = (item: Order['items'][number]) => item.extras || []

const orderPillClass = (status: Order['status']) => {
  if (status === 'received') return 'bg-amber-100 text-amber-900'
  if (status === 'preparing') return 'bg-amber-100 text-amber-900'
  if (status === 'ready') return 'bg-amber-100 text-amber-900'
  if (status === 'delivered') return 'bg-emerald-100 text-emerald-800'
  if (status === 'onroute') return 'bg-sky-100 text-sky-800'
  if (status === 'canceled' || status === 'rejected') return 'bg-rose-100 text-rose-800'
  return 'bg-slate-100 text-slate-700'
}

const formatOrderCreatedAt = (createdAt: number) => {
  const date = new Date(createdAt)
  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
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

const formatCashTime = (value: number) =>
  new Date(value).toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  })

const formatCashDate = (value: number) =>
  new Date(value).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })

const openCashDetail = (movement: CashMovement) => {
  if (movement.source !== 'order') return
  selectedCashMovement.value = movement
  cashDrawerOpen.value = true
}

const closeCashDetail = () => {
  cashDrawerOpen.value = false
  selectedCashMovement.value = null
}

const toggleCashSession = () => {
  cashSessionOpen.value = !cashSessionOpen.value
  statusBanner.value = cashSessionOpen.value ? 'Caja abierta para operar.' : 'Caja cerrada.'
}

const submitCashMovement = () => {
  const concept = cashMovementDraft.concept.trim()
  const amount = Number(cashMovementDraft.amount || 0)
  if (!concept || amount <= 0) {
    statusBanner.value = 'Completa concepto y monto valido para registrar movimiento.'
    return
  }
  const movement: CashMovement = {
    id: `manual-${Date.now()}`,
    source: 'manual',
    type: cashMovementDraft.type,
    concept,
    amount,
    createdAt: Date.now(),
  }
  cashManualMovements.value = [movement, ...cashManualMovements.value]
  cashMovementDraft.concept = ''
  cashMovementDraft.amount = 0
  cashMovementDraft.type = 'income'
  cashMovementModalOpen.value = false
  statusBanner.value = 'Movimiento registrado en caja.'
}

const submitCoupon = () => {
  const code = couponForm.code.trim().toUpperCase()
  const value = Number(couponForm.value || 0)
  const normalizedValue = couponForm.kind === 'free_shipping' ? 0 : value
  if (!code || (normalizedValue <= 0 && couponForm.kind !== 'free_shipping') || !couponForm.expiresAt) {
    statusBanner.value = 'Completa codigo, valor y fecha de expiracion.'
    return
  }
  coupons.value = [
    {
      id: `coupon-${Date.now()}`,
      code,
      kind: couponForm.kind,
      value: normalizedValue,
      minOrder: Number(couponForm.minOrder || 0),
      totalUsesLimit: Number(couponForm.totalUsesLimit || 0),
      usesPerClient: Number(couponForm.usesPerClient || 1),
      usedCount: 0,
      expiresAt: couponForm.expiresAt,
      active: true,
    },
    ...coupons.value,
  ]
  couponForm.code = ''
  couponForm.kind = 'percentage'
  couponForm.value = 10
  couponForm.minOrder = 0
  couponForm.totalUsesLimit = 50
  couponForm.usesPerClient = 1
  couponForm.expiresAt = ''
  couponModalOpen.value = false
  statusBanner.value = 'Cupon creado correctamente.'
}

const toggleCoupon = (id: string) => {
  coupons.value = coupons.value.map((coupon) => (coupon.id === id ? { ...coupon, active: !coupon.active } : coupon))
}

const removeCoupon = (id: string) => {
  coupons.value = coupons.value.filter((coupon) => coupon.id !== id)
}

const addRecipeLine = () => {
  if (!selectedRecipeProductId.value || !recipeIngredientDraft.ingredientId || recipeIngredientDraft.qty <= 0) return
  const productKey = String(selectedRecipeProductId.value)
  const lines = productRecipeMap.value[productKey] || []
  const existing = lines.find((line) => line.ingredientId === recipeIngredientDraft.ingredientId)
  let next: RecipeLine[]
  if (existing) {
    next = lines.map((line) =>
      line.ingredientId === recipeIngredientDraft.ingredientId
        ? { ...line, qty: Number(line.qty) + Number(recipeIngredientDraft.qty) }
        : line,
    )
  } else {
    next = [...lines, { ingredientId: recipeIngredientDraft.ingredientId, qty: Number(recipeIngredientDraft.qty) }]
  }
  productRecipeMap.value = {
    ...productRecipeMap.value,
    [productKey]: next,
  }
  recipeIngredientDraft.ingredientId = 0
  recipeIngredientDraft.qty = 1
}

const removeRecipeLine = (productId: number, ingredientId: number) => {
  const productKey = String(productId)
  const lines = productRecipeMap.value[productKey] || []
  productRecipeMap.value = {
    ...productRecipeMap.value,
    [productKey]: lines.filter((line) => line.ingredientId !== ingredientId),
  }
}

const addRecipeLineFromCalculator = (payload: { ingredientId: number; qty: number }) => {
  if (!selectedRecipeProductId.value) return
  recipeIngredientDraft.ingredientId = payload.ingredientId
  recipeIngredientDraft.qty = payload.qty
  addRecipeLine()
}

const removeRecipeLineFromCalculator = (ingredientId: number) => {
  if (!selectedRecipeProductId.value) return
  removeRecipeLine(selectedRecipeProductId.value, ingredientId)
}

const updateSalePriceFromCalculator = (value: number) => {
  productForm.price = Number(value || 0)
}

const marginClass = (margin: number) => {
  if (margin < 15) return 'text-rose-700'
  if (margin < 30) return 'text-orange-600'
  if (margin > 60) return 'text-emerald-700'
  return 'text-amber-700'
}

const marginAlert = (margin: number) => {
  if (margin < 15) return 'Critico: margen muy bajo, revisar precio o receta.'
  if (margin < 30) return 'Alerta: margen bajo, conviene ajustar precio.'
  if (margin > 60) return 'Margen saludable y competitivo.'
  return 'Margen medio, revisa costos periodicamente.'
}

const openProductAnalysis = (productId: number) => {
  const product = store.getProduct(productId)
  if (!product) return
  productAnalysisProductId.value = productId
  productAnalysisSalePrice.value = Number(product.price || 0)
  productAnalysisModalOpen.value = true
}

const runMassPriceUpdate = () => {
  const factor = 1 + (Number(bulkPriceIncreasePct.value || 0) / 100)
  if (factor <= 0) return
  store.ingredients.forEach((ingredient) => {
    const current = ingredientMetaFor(ingredient.id)
    const next = Number((current.purchasePrice * factor).toFixed(2))
    updateIngredientMeta(ingredient.id, {
      purchasePrice: next,
      previousPurchasePrice: current.purchasePrice,
      priceTrend: next > current.purchasePrice ? 'up' : next < current.purchasePrice ? 'down' : 'same',
    })
  })
  statusBanner.value = `Actualizacion masiva aplicada (${bulkPriceIncreasePct.value}% sobre costos).`
}

const generateTables = () => {
  const count = Math.max(1, Math.min(300, Number(tablesCountDraft.value || 1)))
  const generated: TableItem[] = Array.from({ length: count }, (_, index) => ({
    id: index + 1,
    label: `Mesa ${index + 1}`,
    code: `MESA-${String(index + 1).padStart(3, '0')}`,
  }))
  tables.value = generated
  selectedTableId.value = generated[0]?.id || null
  statusBanner.value = `${count} mesas generadas correctamente.`
}

const copyTableLink = async (tableId: number) => {
  try {
    await navigator.clipboard.writeText(tablesPreviewLink(tableId))
    statusBanner.value = `Link de mesa ${tableId} copiado.`
  } catch {
    statusBanner.value = 'No se pudo copiar el link.'
  }
}

const copyQrTargetLink = async () => {
  if (!qrTargetLink.value) return
  try {
    await navigator.clipboard.writeText(qrTargetLink.value)
    statusBanner.value =
      qrDestinationMode.value === 'general'
        ? 'Link de menu general copiado.'
        : `Link de mesa ${selectedTableId.value} copiado.`
  } catch {
    statusBanner.value = 'No se pudo copiar el link.'
  }
}

const selectTable = (tableId: number) => {
  selectedTableId.value = tableId
}

const onTableDragStart = (tableId: number) => {
  dragTableId.value = tableId
}

const onTableDrop = (targetTableId: number) => {
  const sourceId = dragTableId.value
  dragTableId.value = null
  if (!sourceId || sourceId === targetTableId) return
  const sourceIndex = tables.value.findIndex((table) => table.id === sourceId)
  const targetIndex = tables.value.findIndex((table) => table.id === targetTableId)
  if (sourceIndex < 0 || targetIndex < 0) return
  const next = [...tables.value]
  const [moved] = next.splice(sourceIndex, 1)
  next.splice(targetIndex, 0, moved)
  tables.value = next
}

const openQrPrintModal = () => {
  if (!tables.value.length) {
    statusBanner.value = 'No hay mesas para generar QR.'
    return
  }
  selectedPrintTableIds.value = selectedTableId.value ? [selectedTableId.value] : tables.value.map((table) => table.id)
  qrPrintModalOpen.value = true
}

const printAllTableQrs = (tableIds?: number[]) => {
  const selectedTables = (tableIds?.length ? tables.value.filter((table) => tableIds.includes(table.id)) : tables.value)
  if (!selectedTables.length) {
    statusBanner.value = 'No hay mesas para generar QR.'
    return
  }
  const popup = window.open('', '_blank', 'noopener,noreferrer,width=960,height=720')
  if (!popup) {
    statusBanner.value = 'No se pudo abrir la ventana de impresion.'
    return
  }
  const rows = selectedTables
    .map((table) => {
      const link = tablesPreviewLink(table.id)
      const qr = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(link)}`
      return `
        <article style="border:1px solid #111827;border-radius:16px;padding:12px;text-align:center;background:#fff;">
          <p style="margin:0;font:700 13px Inter,Arial,sans-serif;letter-spacing:.02em;">DUNAMIS STORE</p>
          <h3 style="margin:4px 0 10px 0;font:600 14px Inter,Arial,sans-serif;">${table.label}</h3>
          <img alt="QR ${table.label}" src="${qr}" style="width:168px;height:168px;object-fit:contain;filter:grayscale(100%);" />
          <p style="font:12px Inter,Arial,sans-serif;color:#0f172a;margin:8px 0 0;">Escanea para ver el menu y pedir desde tu mesa</p>
          <p style="font:10px Inter,Arial,sans-serif;color:#64748b;margin:6px 0 0;">${link}</p>
        </article>
      `
    })
    .join('')
  popup.document.write(`
    <html>
      <head>
        <title>QR Mesas</title>
        <meta charset="utf-8" />
      </head>
      <body style="font-family:Inter,Arial,sans-serif;padding:20px;background:#f8fafc;">
        <h2 style="margin:0 0 14px 0;">QR de Mesas</h2>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">${rows}</div>
      </body>
    </html>
  `)
  popup.document.close()
  popup.focus()
  popup.print()
}

const printSelectedTableQrs = () => {
  if (!selectedPrintTableIds.value.length) {
    statusBanner.value = 'Selecciona al menos una mesa para imprimir.'
    return
  }
  printAllTableQrs(selectedPrintTableIds.value)
  qrPrintModalOpen.value = false
}

const onQrLogoInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) {
    qrLogoDataUrl.value = ''
    return
  }
  const reader = new FileReader()
  reader.onload = () => {
    qrLogoDataUrl.value = typeof reader.result === 'string' ? reader.result : ''
  }
  reader.readAsDataURL(file)
}

const printCurrentQrCard = () => {
  if (!qrTargetLink.value || !tableQrPreviewUrl.value) {
    statusBanner.value = 'Selecciona un destino para generar el QR.'
    return
  }
  const popup = window.open('', '_blank', 'noopener,noreferrer,width=900,height=720')
  if (!popup) {
    statusBanner.value = 'No se pudo abrir la ventana de impresion.'
    return
  }
  const tableTitle =
    qrDestinationMode.value === 'general'
      ? 'Menu General'
      : `Mesa ${String(selectedTableId.value || 0).padStart(2, '0')}`
  const logoHtml = qrLogoDataUrl.value
    ? `<img src="${qrLogoDataUrl.value}" alt="Logo" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:56px;height:56px;border-radius:12px;background:#fff;padding:6px;object-fit:contain;border:1px solid #d1d5db;" />`
    : ''
  popup.document.write(`
    <html>
      <head>
        <title>QR ${tableTitle}</title>
        <meta charset="utf-8" />
      </head>
      <body style="font-family:Inter,Arial,sans-serif;background:#f8fafc;padding:24px;">
        <article style="max-width:420px;margin:0 auto;background:#fff;border:1px solid #111827;border-radius:18px;padding:16px;text-align:center;">
          <p style="margin:0;font-size:11px;letter-spacing:.18em;color:#475569;text-transform:uppercase;">Dunamis Store</p>
          <h2 style="margin:6px 0 10px 0;font-size:30px;line-height:1.1;color:#0f172a;">${tableTitle}</h2>
          <div style="position:relative;display:inline-block;">
            <img alt="QR ${tableTitle}" src="${tableQrPreviewUrl.value}" style="width:280px;height:280px;object-fit:contain;filter:grayscale(100%);" />
            ${logoHtml}
          </div>
          <p style="margin:14px 0 0;font-size:12px;color:#0f172a;font-weight:600;">1. Escanea | 2. Elegi | 3. Disfruta</p>
          <p style="margin:8px 0 0;font-size:10px;color:#64748b;">${qrTargetLink.value}</p>
        </article>
      </body>
    </html>
  `)
  popup.document.close()
  popup.focus()
  popup.print()
}

const updateIngredientMeta = (ingredientId: number, patch: Partial<IngredientMeta>) => {
  const key = String(ingredientId)
  const current = ingredientMetaFor(ingredientId)
  ingredientMetaMap.value = {
    ...ingredientMetaMap.value,
    [key]: {
      ...current,
      ...patch,
    },
  }
}

const onIngredientMetaUnitInput = (ingredientId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  updateIngredientMeta(ingredientId, { unit: (target.value || 'unidad').trim() || 'unidad' })
}

const onIngredientMetaSupplierInput = (ingredientId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  updateIngredientMeta(ingredientId, { supplier: target.value })
}

const onIngredientMetaPriceInput = (ingredientId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  const next = Number(target.value || 0)
  const current = ingredientMetaFor(ingredientId)
  if (next === current.purchasePrice) return
  updateIngredientMeta(ingredientId, {
    purchasePrice: next,
    previousPurchasePrice: current.purchasePrice,
    priceTrend: next > current.purchasePrice ? 'up' : next < current.purchasePrice ? 'down' : 'same',
  })
}

const onIngredientMetaMinStockInput = (ingredientId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  updateIngredientMeta(ingredientId, { minStock: Math.max(0, Number(target.value || 0)) })
}

const submitAdminExpense = () => {
  const amount = Number(adminExpenseForm.amount || 0)
  if (amount <= 0) {
    statusBanner.value = 'Ingresa un monto valido para el gasto administrativo.'
    return
  }
  const kind: 'fixed' | 'variable' = ['Alquiler', 'Personal', 'Servicios'].includes(adminExpenseForm.category) ? 'fixed' : 'variable'
  adminExpenses.value = [
    {
      id: `expense-${Date.now()}`,
      category: adminExpenseForm.category,
      amount,
      frequency: adminExpenseForm.frequency,
      kind,
      createdAt: Date.now(),
    },
    ...adminExpenses.value,
  ]
  adminExpenseForm.category = 'Servicios'
  adminExpenseForm.amount = 0
  adminExpenseForm.frequency = 'once'
  statusBanner.value = 'Gasto administrativo registrado.'
}

const removeAdminExpense = (id: string) => {
  adminExpenses.value = adminExpenses.value.filter((item) => item.id !== id)
}

const teamRoleBadgeClass = (role: string) => {
  if (role === 'admin') return 'bg-violet-100 text-violet-700'
  if (role === 'encargado') return 'bg-indigo-100 text-indigo-700'
  if (role === 'cashier') return 'bg-indigo-100 text-indigo-700'
  if (role === 'employee') return 'bg-sky-100 text-sky-700'
  if (role === 'driver') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}

const teamRoleLabel = (role: string) => {
  if (role === 'admin') return 'Admin'
  if (role === 'encargado') return 'Encargado'
  if (role === 'cashier') return 'Encargado'
  if (role === 'employee') return 'Cocina'
  if (role === 'driver') return 'Reparto'
  return role
}

const teamStatusDotClass = (active: boolean) => (active ? 'bg-emerald-500 ring-emerald-200' : 'bg-slate-400 ring-slate-200')

const normalizeRolePermissions = (role: { id: number; name: string }) => {
  const roleKey = String(role.id)
  const current = rolePermissionState.value[roleKey] || {}
  const next: Record<string, boolean> = {}
  for (const group of permissionCatalog) {
    for (const permission of group.items) {
      if (typeof current[permission.key] === 'boolean') {
        next[permission.key] = current[permission.key]
      } else {
        next[permission.key] = role.name === 'admin'
      }
    }
  }
  rolePermissionState.value = {
    ...rolePermissionState.value,
    [roleKey]: next,
  }
}

const hasRolePermission = (roleId: number, key: string) => {
  const roleKey = String(roleId)
  return Boolean(rolePermissionState.value[roleKey]?.[key])
}

const toggleRolePermission = (roleId: number, key: string) => {
  const roleKey = String(roleId)
  const current = rolePermissionState.value[roleKey] || {}
  rolePermissionState.value = {
    ...rolePermissionState.value,
    [roleKey]: {
      ...current,
      [key]: !current[key],
    },
  }
  permissionSaveMessage.value = 'Permiso actualizado correctamente'
  showRbacToast('Permiso actualizado correctamente')
  window.setTimeout(() => {
    if (permissionSaveMessage.value === 'Permiso actualizado correctamente') {
      permissionSaveMessage.value = ''
    }
  }, 1400)
}

const openRolePermissionsForUser = (roleName: string) => {
  const targetRole = store.roles.find((role) => role.name === roleName)
  if (targetRole) {
    activeRoleEditorId.value = targetRole.id
    normalizeRolePermissions(targetRole)
  }
  goToTab('team')
}

const auditUsers = computed(() => {
  const unique = new Set(store.auditLogs.map((log) => log.userName).filter(Boolean))
  return Array.from(unique).sort((a, b) => a.localeCompare(b, 'es'))
})

const asRecord = (value: unknown): Record<string, unknown> | null => {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null
  return value as Record<string, unknown>
}

const asNumber = (value: unknown): number | null => {
  if (typeof value === 'number' && Number.isFinite(value)) return value
  if (typeof value === 'string') {
    const normalized = Number(value.replace(',', '.'))
    if (Number.isFinite(normalized)) return normalized
  }
  return null
}

const extractNumericField = (source: Record<string, unknown> | null, keys: string[]) => {
  if (!source) return null
  for (const key of keys) {
    const value = asNumber(source[key])
    if (value !== null) return value
  }
  return null
}

const extractAuditDiff = (metadata: Record<string, unknown> | null, keys: string[]) => {
  if (!metadata) return null
  const beforeCandidates = [asRecord(metadata.before), asRecord(metadata.previous), asRecord(metadata.old)]
  const afterCandidates = [asRecord(metadata.after), asRecord(metadata.current), asRecord(metadata.new)]
  let before: number | null = null
  let after: number | null = null
  for (const candidate of beforeCandidates) {
    before = extractNumericField(candidate, keys)
    if (before !== null) break
  }
  for (const candidate of afterCandidates) {
    after = extractNumericField(candidate, keys)
    if (after !== null) break
  }
  if (before === null || after === null || before === after) return null
  return { before, after }
}

const extractPriceDiff = (metadata: Record<string, unknown> | null) =>
  extractAuditDiff(metadata, ['price', 'base_price', 'basePrice', 'unit_price', 'unitPrice', 'precio'])

const extractStockDiff = (metadata: Record<string, unknown> | null) =>
  extractAuditDiff(metadata, ['stock', 'stock_quantity', 'stockQuantity', 'quantity', 'cantidad'])

const auditPriceDiff = (log: { metadata: Record<string, unknown> | null }) => extractPriceDiff(asRecord(log.metadata))
const auditStockDiff = (log: { metadata: Record<string, unknown> | null }) => extractStockDiff(asRecord(log.metadata))

const detectAuditType = (action: string): 'create' | 'edit' | 'delete' | 'security' | 'cancel' | 'other' => {
  const value = action.toLowerCase()
  if (value.includes('cancel') || value.includes('reject')) return 'cancel'
  if (value.includes('login') || value.includes('auth') || value.includes('password') || value.includes('session')) return 'security'
  if (value.includes('deleted') || value.includes('remove')) return 'delete'
  if (value.includes('created') || value.includes('create')) return 'create'
  if (value.includes('updated') || value.includes('edit') || value.includes('status_updated')) return 'edit'
  return 'other'
}

const matchesAuditChip = (log: { action: string; metadata: Record<string, unknown> | null }) => {
  const action = log.action.toLowerCase()
  const type = detectAuditType(action)
  if (auditFilterChip.value === 'all') return true
  if (auditFilterChip.value === 'cancelations') return type === 'cancel'
  if (auditFilterChip.value === 'price_changes') return action.includes('price') || Boolean(extractPriceDiff(log.metadata))
  if (auditFilterChip.value === 'security') return type === 'security'
  if (auditFilterChip.value === 'created') return type === 'create'
  if (auditFilterChip.value === 'edited') return type === 'edit'
  if (auditFilterChip.value === 'deleted') return type === 'delete' || type === 'cancel'
  return true
}

const filteredAuditLogs = computed(() =>
  store.auditLogs
    .filter((log) => {
      const metadata = asRecord(log.metadata)
      if (!matchesAuditChip({ action: log.action, metadata })) return false
      if (auditUserFilter.value !== 'all' && log.userName !== auditUserFilter.value) return false

      const logDate = new Date(log.createdAt)
      if (auditDateFrom.value) {
        const from = new Date(`${auditDateFrom.value}T00:00:00`)
        if (logDate < from) return false
      }
      if (auditDateTo.value) {
        const to = new Date(`${auditDateTo.value}T23:59:59`)
        if (logDate > to) return false
      }

      const query = auditSearchTerm.value.trim().toLowerCase()
      if (!query) return true
      const haystack = [
        log.userName,
        log.action,
        log.entityType,
        String(log.entityId || ''),
      ].join(' ').toLowerCase()
      return haystack.includes(query)
    })
    .sort((a, b) => b.createdAt - a.createdAt),
)

const auditIconComponent = (action: string) => {
  const type = detectAuditType(action)
  if (type === 'edit') return Pencil
  if (type === 'create') return Plus
  if (type === 'delete' || type === 'cancel') return Trash2
  if (type === 'security') return ShieldCheck
  if (action.startsWith('order.')) return ShoppingCart
  if (action.startsWith('user.') || action.startsWith('role.')) return UserCog
  return Activity
}

const auditIconClass = (action: string) => {
  const type = detectAuditType(action)
  if (type === 'edit') return 'bg-amber-100 text-amber-700'
  if (type === 'create') return 'bg-emerald-100 text-emerald-700'
  if (type === 'delete' || type === 'cancel') return 'bg-rose-100 text-rose-700'
  if (type === 'security') return 'bg-sky-100 text-sky-700'
  return 'bg-slate-200 text-slate-700'
}

const auditSectionLabel = (entityType: string) => {
  if (entityType === 'order') return 'Pedidos'
  if (entityType === 'user') return 'Equipo'
  if (entityType === 'role') return 'Roles'
  if (entityType === 'product') return 'Catalogo'
  return 'Sistema'
}

const auditRelativeTime = (createdAt: number) => {
  const diffMin = Math.max(1, Math.round((Date.now() - createdAt) / 60000))
  if (diffMin < 60) return `Hace ${diffMin} min`
  const diffHours = Math.round(diffMin / 60)
  if (diffHours < 24) return `Hace ${diffHours} h`
  const diffDays = Math.round(diffHours / 24)
  return `Hace ${diffDays} d`
}

const auditTimeLabel = (createdAt: number) =>
  new Date(createdAt).toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  })

const moneyLabel = (amount: number) =>
  `$${amount.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`

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

const goToTab = (tab: AdminTab) => {
  if (isTabBlockedByPermission(tab)) {
    router.push('/403')
    return
  }
  if (isTabLockedByPlan(tab)) {
    openUpgradeModalForTab(tab)
    return
  }
  const targetPath = tabToRoute[tab]
  if (route.path === targetPath) return
  router.push(targetPath)
}

const resetProductForm = () => {
  editingProductId.value = null
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
  selectedRecipeProductId.value = 0
  productModalOpen.value = false
}

const openCreateProductModal = () => {
  resetProductForm()
  productModalOpen.value = true
}

const editProductCard = async (productId: number) => {
  const product = store.getProduct(productId)
  if (!product) return

  editingProductId.value = productId
  selectedRecipeProductId.value = productId
  productModalOpen.value = true
  productForm.name = product.name
  productForm.price = Number(product.price || 0)
  productForm.prepMin = Number(product.prepMin || 15)
  productForm.category = product.category || ''
  productForm.stockQuantity = Number(product.stockQuantity || 0)
  productForm.minStockQuantity = Number(product.minStockQuantity || 0)
  selectedIngredientIds.value = []
  productExtras.value = []
  productImageFile.value = null
  productImagePreview.value = product.imageUrl || ''

  try {
    const response = await fetch(`${apiBaseUrl.replace(/\/$/, '')}/products/${productId}`)
    if (!response.ok) throw new Error('detail-error')
    const payload = (await response.json()) as {
      product?: {
        ingredients?: Array<{ id: number }>
        extras?: Array<{ id?: number; name?: string; additional_price?: number }>
      }
    }
    selectedIngredientIds.value = (payload.product?.ingredients || []).map((ingredient) => Number(ingredient.id)).filter((id) => Number.isFinite(id))
    productExtras.value = (payload.product?.extras || []).map((extra) => ({
      id: extra.id ? Number(extra.id) : undefined,
      name: String(extra.name || 'Extra'),
      additionalPrice: Number(extra.additional_price || 0),
    }))
  } catch {
    // si falla el detalle, deja el editor con datos base del producto
  }
}

const submitProduct = async () => {
  const basePayload = {
    ...productForm,
    imageFile: productImageFile.value,
    ingredients: selectedIngredientIds.value.map((ingredientId) => ({
      ingredientId,
      isDefault: true,
      isRemovable: true,
      additionalPrice: 0,
    })),
    extras: productExtras.value.map((extra) => ({
      id: extra.id,
      name: extra.name,
      additionalPrice: extra.additionalPrice,
      isActive: true,
    })),
  }
  const ok = isEditingProduct.value && editingProductId.value !== null
    ? await store.updateProduct(editingProductId.value, basePayload)
    : await store.addProduct(basePayload)
  if (!ok) return
  statusBanner.value = isEditingProduct.value ? 'Producto actualizado.' : 'Producto creado.'
  resetProductForm()
  inventoryView.value = 'list'
}

const createCategory = () => {
  const next = categoryDraft.value.trim()
  if (!next) return
  const exists = productCategories.value.some((category) => category.toLowerCase() === next.toLowerCase())
  if (!exists) {
    customCategories.value.push(next)
    localStorage.setItem(CATEGORY_STORAGE_KEY, JSON.stringify(customCategories.value))
  }
  productForm.category = next
  bulkPriceForm.category = next
  categoryDraft.value = ''
  categoryModalOpen.value = false
  statusBanner.value = `Categoria "${next}" creada.`
}

const submitIngredient = async () => {
  if (!ingredientForm.name.trim()) return
  const createdName = ingredientForm.name.trim()
  const purchasePrice = Number(ingredientForm.additionalPrice || 0)
  await store.createIngredient({
    name: createdName,
    additionalPrice: purchasePrice,
    stockQuantity: ingredientForm.stockQuantity,
  })
  const created = store.ingredients.find((ingredient) => ingredient.name.toLowerCase() === createdName.toLowerCase())
  if (created) {
    updateIngredientMeta(created.id, {
      unit: ingredientForm.unit,
      supplier: ingredientForm.supplier.trim(),
      purchasePrice,
      minStock: Math.max(0, Number(ingredientForm.minStock || 0)),
      previousPurchasePrice: purchasePrice,
      priceTrend: 'same',
    })
  }
  ingredientForm.name = ''
  ingredientForm.unit = 'unidad'
  ingredientForm.supplier = ''
  ingredientForm.additionalPrice = 0
  ingredientForm.stockQuantity = 0
  ingredientForm.minStock = 5
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
    updateIngredientMeta(created.id, {
      unit: 'unidad',
      supplier: '',
      purchasePrice: 0,
      minStock: 5,
      previousPurchasePrice: 0,
      priceTrend: 'same',
    })
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
  statusBanner.value = 'La creacion de roles desde el negocio esta bloqueada. Solo Admin SaaS puede crear roles base.'
}

const assignableBusinessRoles = computed(() =>
  store.roles.filter((role) => !['admin'].includes(String(role.name || '').toLowerCase())),
)

const ensureEncargadoRole = async () => {
  const exists = store.roles.some((role) => String(role.name || '').toLowerCase() === 'encargado')
  if (exists) return
  try {
    await store.createRole({ name: 'encargado', label: 'Encargado' })
  } catch {
    // si falla por permisos/backend, continua sin romper el panel
  }
}

const submitUser = async () => {
  if (!userForm.name.trim() || !userForm.email.trim() || !userForm.roleId) return
  const selectedRole = store.roles.find((role) => role.id === userForm.roleId)
  if (!selectedRole || String(selectedRole.name || '').toLowerCase() === 'admin') {
    statusBanner.value = 'No se puede asignar rol Admin desde el negocio.'
    return
  }
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

const resetComboForm = () => {
  editingComboId.value = null
  comboForm.name = ''
  comboForm.description = ''
  comboForm.basePrice = 0
  comboForm.imageUrl = ''
  comboItems.value = []
  comboProductId.value = 0
  comboQuantity.value = 1
  comboImageFile.value = null
  comboImagePreview.value = ''
  comboCategoryFilter.value = 'all'
  comboModalOpen.value = false
}

const openCreateComboModal = () => {
  resetComboForm()
  comboModalOpen.value = true
}

const editComboCard = async (combo: ComboItem) => {
  editingComboId.value = combo.id
  comboForm.name = combo.name
  comboForm.description = combo.description || ''
  comboForm.basePrice = combo.basePrice
  comboForm.imageUrl = combo.imageUrl || ''
  comboItems.value = combo.items.map((item) => ({ productId: item.productId, quantity: item.quantity }))
  comboImageFile.value = null
  comboImagePreview.value = combo.imageUrl || ''
  comboModalOpen.value = true

  try {
    const response = await fetch(`${apiBaseUrl.replace(/\/$/, '')}/combos/${combo.id}`)
    if (!response.ok) return

    const payload = (await response.json()) as {
      name?: string
      description?: string | null
      base_price?: number
      image_url?: string | null
      products?: Array<{ id?: number; product_id?: number; pivot?: { quantity?: number }; quantity?: number }>
    }

    comboForm.name = String(payload.name || combo.name)
    comboForm.description = payload.description ? String(payload.description) : ''
    comboForm.basePrice = Number(payload.base_price || combo.basePrice || 0)
    comboForm.imageUrl = resolveAssetUrl(payload.image_url || null) || combo.imageUrl || ''
    comboImagePreview.value = comboForm.imageUrl

    if (Array.isArray(payload.products) && payload.products.length) {
      comboItems.value = payload.products
        .map((item) => ({
          productId: Number(item.id || item.product_id || 0),
          quantity: Number(item.pivot?.quantity || item.quantity || 1),
        }))
        .filter((item) => item.productId > 0)
    }
  } catch {
    // Mantiene datos base de la card si falla el detalle.
  }
}

const deleteComboCard = async (comboId: number) => {
  if (!window.confirm(`Eliminar combo #${comboId}?`)) return
  const ok = await store.deleteCombo(comboId)
  statusBanner.value = ok ? 'Combo eliminado.' : 'No se pudo eliminar el combo.'
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
  const payload = {
    name: comboForm.name.trim(),
    description: comboForm.description.trim(),
    basePrice: comboForm.basePrice,
    imageUrl: comboForm.imageUrl.trim(),
    imageFile: comboImageFile.value,
    items: comboItems.value,
  }
  const ok = isEditingCombo.value && editingComboId.value
    ? await store.updateCombo(editingComboId.value, payload)
    : await store.createCombo(payload)
  if (!ok) {
    statusBanner.value = isEditingCombo.value
      ? 'No se pudo actualizar el combo. Revisa sesion/permisos y vuelve a intentar.'
      : 'No se pudo crear el combo. Revisa sesion/permisos y vuelve a intentar.'
    return
  }
  statusBanner.value = isEditingCombo.value ? 'Combo actualizado.' : 'Combo creado.'
  resetComboForm()
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
  const ok = await store.createBundle({
    name: bundleForm.name.trim(),
    description: bundleForm.description.trim(),
    pricingMode: bundleForm.pricingMode,
    fixedPrice: bundleForm.fixedPrice,
    discountPercentage: bundleForm.discountPercentage,
    imageFile: bundleImageFile.value,
    items: bundleItems.value,
  })
  if (!ok) {
    statusBanner.value = 'No se pudo crear el bundle. Revisa sesion/permisos y vuelve a intentar.'
    return
  }
  bundleForm.name = ''
  bundleForm.description = ''
  bundleForm.pricingMode = 'fixed_price'
  bundleForm.fixedPrice = 0
  bundleForm.discountPercentage = 0
  bundleItems.value = []
  bundleImageFile.value = null
  bundleImagePreview.value = ''
  bundleCategoryFilter.value = 'all'
  bundleModalOpen.value = false
  statusBanner.value = 'Bundle creado.'
}

const resetDailyMenuForm = () => {
  dailyMenuEditingId.value = null
  dailyMenuForm.name = ''
  dailyMenuForm.description = ''
  dailyMenuForm.imageUrl = ''
  dailyMenuForm.isActive = true
  dailyMenuForm.slot = 'all_day'
  dailyMenuForm.weekdays = []
  dailyMenuForm.activeFrom = ''
  dailyMenuForm.activeTo = ''
  dailyMenuForm.priority = 0
  dailyMenuModalOpen.value = false
}

const openCreateDailyMenuModal = () => {
  resetDailyMenuForm()
  dailyMenuModalOpen.value = true
}

const editDailyMenuCard = (menu: DailyMenu) => {
  dailyMenuEditingId.value = menu.id
  dailyMenuForm.name = menu.name
  dailyMenuForm.description = menu.description || ''
  dailyMenuForm.imageUrl = menu.imageUrl || ''
  dailyMenuForm.isActive = menu.isActive
  dailyMenuForm.slot = menu.slot
  dailyMenuForm.weekdays = [...menu.weekdays]
  dailyMenuForm.activeFrom = menu.activeFrom ? String(menu.activeFrom).slice(0, 16) : ''
  dailyMenuForm.activeTo = menu.activeTo ? String(menu.activeTo).slice(0, 16) : ''
  dailyMenuForm.priority = menu.priority || 0
  dailyMenuModalOpen.value = true
}

const toggleDailyMenuWeekday = (weekday: number) => {
  if (dailyMenuForm.weekdays.includes(weekday)) {
    dailyMenuForm.weekdays = dailyMenuForm.weekdays.filter((value) => value !== weekday)
    return
  }
  dailyMenuForm.weekdays.push(weekday)
}

const submitDailyMenu = async () => {
  if (!dailyMenuForm.name.trim()) return
  const payload = {
    name: dailyMenuForm.name.trim(),
    description: dailyMenuForm.description.trim(),
    imageUrl: dailyMenuForm.imageUrl.trim(),
    isActive: dailyMenuForm.isActive,
    slot: dailyMenuForm.slot,
    weekdays: [...dailyMenuForm.weekdays].sort((a, b) => a - b),
    activeFrom: dailyMenuForm.activeFrom || null,
    activeTo: dailyMenuForm.activeTo || null,
    priority: Number(dailyMenuForm.priority || 0),
  }
  try {
    if (isEditingDailyMenu.value && dailyMenuEditingId.value) {
      await store.updateDailyMenu(dailyMenuEditingId.value, payload)
      statusBanner.value = 'Menu diario actualizado.'
    } else {
      await store.createDailyMenu(payload)
      statusBanner.value = 'Menu diario creado.'
    }
    resetDailyMenuForm()
  } catch {
    statusBanner.value = 'No se pudo guardar el menu diario.'
  }
}

const deleteDailyMenuCard = async (menuId: number) => {
  if (!window.confirm(`Eliminar menu diario #${menuId}?`)) return
  try {
    await store.deleteDailyMenu(menuId)
    statusBanner.value = 'Menu diario eliminado.'
  } catch {
    statusBanner.value = 'No se pudo eliminar el menu diario.'
  }
}

const openDailyMenuItemModal = (menuId: number) => {
  dailyMenuItemForm.dailyMenuId = menuId
  dailyMenuItemForm.itemType = 'product'
  dailyMenuItemForm.itemId = 0
  dailyMenuItemForm.discountPercent = ''
  dailyMenuItemForm.sortOrder = 0
  dailyMenuItemModalOpen.value = true
}

const submitDailyMenuItem = async () => {
  if (!dailyMenuItemForm.dailyMenuId || !dailyMenuItemForm.itemId) return
  try {
    await store.upsertDailyMenuItem(dailyMenuItemForm.dailyMenuId, {
      itemType: dailyMenuItemForm.itemType,
      itemId: Number(dailyMenuItemForm.itemId),
      promoPrice: computedDailyPromoPrice.value,
      sortOrder: Number(dailyMenuItemForm.sortOrder || 0),
    })
    dailyMenuItemModalOpen.value = false
    statusBanner.value = 'Item cargado en menu diario.'
  } catch {
    statusBanner.value = 'No se pudo cargar el item.'
  }
}

const removeDailyMenuItemFromMenu = async (menuId: number, item: DailyMenuItem) => {
  try {
    await store.removeDailyMenuItem(menuId, item.id)
    statusBanner.value = 'Item removido del menu diario.'
  } catch {
    statusBanner.value = 'No se pudo remover el item.'
  }
}

const dailyMenuItemDiscountLabel = (item: DailyMenuItem) => {
  const base = Number(item.basePrice || 0)
  const promo = item.promoPrice === null ? null : Number(item.promoPrice)
  if (!base || promo === null || promo >= base) return 'sin descuento'
  const discount = Math.max(0, Math.min(100, ((base - promo) / base) * 100))
  return `${discount.toFixed(0)}%`
}

watch(
  () => route.path,
  (path) => {
    activeTab.value = resolveTabFromPath(path)
    mobileSidebarOpen.value = false
    cashDrawerOpen.value = false
    cashMovementModalOpen.value = false
    couponModalOpen.value = false
    qrPrintModalOpen.value = false
    upgradeModalOpen.value = false
  },
  { immediate: true },
)

watch(
  [activeTab, resolvedSessionRole, rbacMatrixState],
  () => {
    if (isTabBlockedByPermission(activeTab.value) && route.path !== '/403') {
      router.replace('/403')
    }
  },
  { deep: true },
)

watch(
  () => dailyMenuItemForm.itemType,
  () => {
    dailyMenuItemForm.itemId = 0
  },
)

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

watch(
  cashManualMovements,
  () => {
    localStorage.setItem(CASHBOX_MOVEMENTS_STORAGE_KEY, JSON.stringify(cashManualMovements.value))
  },
  { deep: true },
)

watch(cashSessionOpen, () => {
  localStorage.setItem(CASHBOX_SESSION_STORAGE_KEY, JSON.stringify(cashSessionOpen.value))
})

watch(
  () => store.roles.map((role) => `${role.id}:${role.name}`),
  () => {
    for (const role of store.roles) {
      normalizeRolePermissions(role)
    }
    if (!activeRoleEditorId.value && store.roles.length) {
      activeRoleEditorId.value = store.roles[0].id
    }
  },
  { immediate: true },
)

watch(
  () => store.products.map((product) => product.id),
  () => {
    if (!selectedRecipeProductId.value && store.products.length) {
      selectedRecipeProductId.value = store.products[0].id
    }
  },
  { immediate: true },
)

watch(
  rolePermissionState,
  () => {
    localStorage.setItem(ROLE_PERMISSIONS_STORAGE_KEY, JSON.stringify(rolePermissionState.value))
  },
  { deep: true },
)

watch(
  rbacMatrixState,
  () => {
    localStorage.setItem(RBAC_MATRIX_STORAGE_KEY, JSON.stringify(rbacMatrixState.value))
  },
  { deep: true },
)

watch(
  coupons,
  () => {
    localStorage.setItem(COUPONS_STORAGE_KEY, JSON.stringify(coupons.value))
  },
  { deep: true },
)

watch(
  productRecipeMap,
  () => {
    localStorage.setItem(PRODUCT_RECIPE_STORAGE_KEY, JSON.stringify(productRecipeMap.value))
  },
  { deep: true },
)

watch(
  tables,
  () => {
    localStorage.setItem(TABLES_STORAGE_KEY, JSON.stringify(tables.value))
    if (selectedTableId.value && !tables.value.some((table) => table.id === selectedTableId.value)) {
      selectedTableId.value = tables.value[0]?.id || null
    }
    if (!selectedTableId.value && tables.value.length) {
      selectedTableId.value = tables.value[0].id
    }
  },
  { deep: true },
)

watch(
  ingredientMetaMap,
  () => {
    localStorage.setItem(INGREDIENT_META_STORAGE_KEY, JSON.stringify(ingredientMetaMap.value))
  },
  { deep: true },
)

watch(
  adminExpenses,
  () => {
    localStorage.setItem(ADMIN_EXPENSES_STORAGE_KEY, JSON.stringify(adminExpenses.value))
  },
  { deep: true },
)

watch(currentPlan, () => {
  localStorage.setItem(SUBSCRIPTION_PLAN_STORAGE_KEY, currentPlan.value)
})

watch(subscriptionRenewAt, () => {
  localStorage.setItem(SUBSCRIPTION_RENEW_STORAGE_KEY, String(subscriptionRenewAt.value))
})

watch(
  () => store.ingredients.map((ingredient) => ingredient.id),
  () => {
    for (const ingredient of store.ingredients) {
      const key = String(ingredient.id)
      if (!ingredientMetaMap.value[key]) {
        const fallbackPrice = Number(ingredient.unitCost ?? ingredient.additionalPrice ?? 0)
        ingredientMetaMap.value = {
          ...ingredientMetaMap.value,
          [key]: {
            unit: 'unidad',
            supplier: '',
            purchasePrice: fallbackPrice,
            minStock: 5,
            previousPurchasePrice: fallbackPrice,
            priceTrend: 'same',
          },
        }
      }
    }
  },
  { immediate: true },
)

onMounted(async () => {
  try {
    const raw = localStorage.getItem(CATEGORY_STORAGE_KEY)
    const parsed = raw ? JSON.parse(raw) : []
    customCategories.value = Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string') : []
  } catch {
    customCategories.value = []
  }
  try {
    const rawCashMovements = localStorage.getItem(CASHBOX_MOVEMENTS_STORAGE_KEY)
    const parsedCashMovements = rawCashMovements ? JSON.parse(rawCashMovements) : []
    cashManualMovements.value = Array.isArray(parsedCashMovements) ? parsedCashMovements : []
  } catch {
    cashManualMovements.value = []
  }
  try {
    const rawCashSession = localStorage.getItem(CASHBOX_SESSION_STORAGE_KEY)
    cashSessionOpen.value = rawCashSession ? Boolean(JSON.parse(rawCashSession)) : true
  } catch {
    cashSessionOpen.value = true
  }
  try {
    const rawRolePermissions = localStorage.getItem(ROLE_PERMISSIONS_STORAGE_KEY)
    const parsedRolePermissions = rawRolePermissions ? JSON.parse(rawRolePermissions) : {}
    rolePermissionState.value = parsedRolePermissions && typeof parsedRolePermissions === 'object' ? parsedRolePermissions : {}
  } catch {
    rolePermissionState.value = {}
  }
  try {
    const rawRbac = localStorage.getItem(RBAC_MATRIX_STORAGE_KEY)
    const parsedRbac = rawRbac ? JSON.parse(rawRbac) : null
    rbacMatrixState.value = normalizeRbacMatrix(parsedRbac)
  } catch {
    rbacMatrixState.value = createDefaultRbacMatrix()
  }
  try {
    const rawCoupons = localStorage.getItem(COUPONS_STORAGE_KEY)
    const parsedCoupons = rawCoupons ? JSON.parse(rawCoupons) : []
    coupons.value = Array.isArray(parsedCoupons) ? parsedCoupons : []
  } catch {
    coupons.value = []
  }
  try {
    const rawRecipes = localStorage.getItem(PRODUCT_RECIPE_STORAGE_KEY)
    const parsedRecipes = rawRecipes ? JSON.parse(rawRecipes) : {}
    productRecipeMap.value = parsedRecipes && typeof parsedRecipes === 'object' ? parsedRecipes : {}
  } catch {
    productRecipeMap.value = {}
  }
  try {
    const rawTables = localStorage.getItem(TABLES_STORAGE_KEY)
    const parsedTables = rawTables ? JSON.parse(rawTables) : []
    tables.value = Array.isArray(parsedTables) ? parsedTables : []
  } catch {
    tables.value = []
  }
  try {
    const rawIngredientMeta = localStorage.getItem(INGREDIENT_META_STORAGE_KEY)
    const parsedIngredientMeta = rawIngredientMeta ? JSON.parse(rawIngredientMeta) : {}
    if (parsedIngredientMeta && typeof parsedIngredientMeta === 'object') {
      const normalized: Record<string, IngredientMeta> = {}
      for (const [key, value] of Object.entries(parsedIngredientMeta as Record<string, Partial<IngredientMeta>>)) {
        const purchasePrice = Number(value.purchasePrice || 0)
        normalized[key] = {
          unit: String(value.unit || 'unidad'),
          supplier: String(value.supplier || ''),
          purchasePrice,
          minStock: Math.max(0, Number(value.minStock ?? 5)),
          previousPurchasePrice: Number(value.previousPurchasePrice ?? purchasePrice),
          priceTrend: value.priceTrend === 'up' || value.priceTrend === 'down' ? value.priceTrend : 'same',
        }
      }
      ingredientMetaMap.value = normalized
    } else {
      ingredientMetaMap.value = {}
    }
  } catch {
    ingredientMetaMap.value = {}
  }
  try {
    const rawAdminExpenses = localStorage.getItem(ADMIN_EXPENSES_STORAGE_KEY)
    const parsedAdminExpenses = rawAdminExpenses ? JSON.parse(rawAdminExpenses) : []
    adminExpenses.value = Array.isArray(parsedAdminExpenses)
      ? parsedAdminExpenses.map((item) => ({
          ...item,
          kind: item.kind === 'fixed' || item.kind === 'variable'
            ? item.kind
            : ['Alquiler', 'Personal', 'Servicios'].includes(String(item.category || ''))
              ? 'fixed'
              : 'variable',
        }))
      : []
  } catch {
    adminExpenses.value = []
  }
  try {
    const rawPlan = localStorage.getItem(SUBSCRIPTION_PLAN_STORAGE_KEY)
    if (rawPlan === 'takeaway' || rawPlan === 'full' || rawPlan === 'bi') {
      currentPlan.value = rawPlan
    }
  } catch {
    currentPlan.value = 'full'
  }
  try {
    const rawRenew = Number(localStorage.getItem(SUBSCRIPTION_RENEW_STORAGE_KEY) || '')
    if (Number.isFinite(rawRenew) && rawRenew > Date.now()) {
      subscriptionRenewAt.value = rawRenew
    }
  } catch {
    subscriptionRenewAt.value = Date.now() + (12 * 24 * 60 * 60 * 1000)
  }
  try {
    const onboardingState = localStorage.getItem(ONBOARDING_STORAGE_KEY)
    onboardingOpen.value = onboardingState !== 'completed'
  } catch {
    onboardingOpen.value = true
  }
  await store.refreshAll()
  await ensureEncargadoRole()
})
</script>

<template>
  <section
    v-if="onboardingOpen"
    class="rounded-[24px] bg-[#F8F9FA] p-2"
  >
    <div class="mx-auto max-w-6xl rounded-[32px] bg-white p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
      <header class="mb-5 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <div>
            <h2 class="text-xl font-extrabold text-slate-900">Configuracion Inicial</h2>
            <p class="text-sm text-slate-500">Activa tu tienda en 4 pasos guiados.</p>
          </div>
          <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" @click="onboardingOpen = false">
            Salir
          </button>
        </div>
        <div class="rounded-full bg-slate-100 p-1">
          <div class="h-1.5 rounded-full bg-emerald-500 transition-all" :style="{ width: `${onboardingProgress}%` }"></div>
        </div>
        <div class="grid grid-cols-4 gap-2 text-center text-[11px] font-semibold">
          <span :class="onboardingStep === 1 ? 'text-emerald-700 onboarding-step-pulse' : 'text-slate-400'">1. Identidad</span>
          <span :class="onboardingStep === 2 ? 'text-emerald-700 onboarding-step-pulse' : 'text-slate-400'">2. Ubicacion</span>
          <span :class="onboardingStep === 3 ? 'text-emerald-700 onboarding-step-pulse' : 'text-slate-400'">3. Tu Menu</span>
          <span :class="onboardingStep >= 4 ? 'text-emerald-700 onboarding-step-pulse' : 'text-slate-400'">4. Lanzamiento</span>
        </div>
      </header>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="space-y-4">
          <article v-if="onboardingStep === 1" class="space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Identidad visual</h3>
            <label class="flex h-44 cursor-pointer flex-col items-center justify-center rounded-full border-2 border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
              <img v-if="onboarding.logoDataUrl" :src="onboarding.logoDataUrl" alt="Logo" class="h-24 w-24 rounded-full object-cover" />
              <span v-else>Arrastra o sube tu logo</span>
              <input type="file" class="hidden" accept="image/*" @change="onOnboardingLogoInput" />
            </label>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
              Nombre de la tienda
              <input :value="onboarding.storeName" class="input mt-1" type="text" placeholder="Ej: Pizzeria Pepe" @input="onOnboardingStoreNameInput" />
            </label>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Color de marca</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  v-for="color in onboardingColorOptions"
                  :key="color.key"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition"
                  :class="onboarding.brandColor === color.value ? 'border-slate-900 bg-slate-100 text-slate-900' : 'border-slate-200 bg-white text-slate-600'"
                  @click="onboarding.brandColor = color.value"
                >
                  <span class="h-4 w-4 rounded-full" :style="{ backgroundColor: color.value }"></span>
                  {{ color.label }}
                </button>
              </div>
            </div>
          </article>

          <article v-else-if="onboardingStep === 2" class="space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Ubicacion y contacto</h3>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
              Telefono de contacto
              <input v-model="onboarding.phone" class="input mt-1" type="text" placeholder="+54 11 1234 5678" />
            </label>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
              Direccion del local
              <div class="relative mt-1">
                <MapPin class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input v-model="onboarding.address" class="input pl-9" type="text" placeholder="Buscar ubicacion en mapa..." />
              </div>
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
              <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Horarios de atencion</p>
                <button type="button" class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600" @click="copyHoursToAll">
                  Copiar a todos
                </button>
              </div>
              <div class="space-y-2">
                <div v-for="(day, index) in onboardingWeekdays" :key="`day-${day}`" class="grid grid-cols-[52px_1fr_1fr_auto] items-center gap-2 text-xs">
                  <span class="font-semibold text-slate-700">{{ day }}</span>
                  <input v-model="onboarding.hours[index].from" class="input" type="time" />
                  <input v-model="onboarding.hours[index].to" class="input" type="time" />
                  <input v-model="onboarding.hours[index].enabled" type="checkbox" class="h-4 w-4" />
                </div>
              </div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
              <iframe
                v-if="onboardingMapEmbedUrl"
                :src="onboardingMapEmbedUrl"
                class="h-52 w-full border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Mapa local"
              ></iframe>
              <div v-else class="grid h-52 place-items-center text-sm text-slate-500">Completa una direccion para ver el mapa.</div>
            </div>
          </article>

          <article v-else-if="onboardingStep === 3" class="space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Carga de menu flash</h3>
            <p class="text-sm text-slate-500">Selecciona categorias iniciales para arrancar rapido.</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="category in onboardingCategoryQuick"
                :key="`quick-cat-${category}`"
                type="button"
                class="rounded-full px-3 py-2 text-xs font-semibold transition"
                :class="onboarding.categories.includes(category) ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600'"
                @click="toggleOnboardingCategory(category)"
              >
                {{ category }}
              </button>
            </div>
            <button
              type="button"
              class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600"
              @click="runMenuAiImport"
            >
              {{ onboardingAiLoading ? 'Procesando carta...' : 'Subi una foto de tu carta fisica y cargamos nombres por vos (IA)' }}
            </button>
          </article>

          <article v-else-if="onboardingStep === 4" class="space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Lanzamiento</h3>
            <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
              <p><span class="font-semibold text-slate-900">Local:</span> {{ onboarding.storeName || 'Sin nombre' }}</p>
              <p class="mt-1"><span class="font-semibold text-slate-900">URL:</span> dunamis.store/{{ onboardingPreviewSlug }}</p>
              <p class="mt-1"><span class="font-semibold text-slate-900">Categorias:</span> {{ onboarding.categories.join(', ') || 'Sin categorias' }}</p>
            </div>
            <button type="button" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-bold text-white" @click="completeOnboarding">
              Publicar tienda
            </button>
          </article>

          <article v-else class="relative overflow-hidden rounded-2xl bg-slate-900 px-6 py-8 text-center text-white">
            <div class="confetti-layer">
              <span v-for="n in 16" :key="`confetti-${n}`" class="confetti-dot" :style="{ left: `${(n * 6) % 92}%`, animationDelay: `${(n % 8) * 0.12}s` }"></span>
            </div>
            <h3 class="text-2xl font-extrabold">Felicidades! Tu tienda ya esta online</h3>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
              <button type="button" class="rounded-full bg-emerald-500 px-4 py-3 text-sm font-bold text-white" @click="openStorePreview">
                Ver mi Tienda
              </button>
              <button type="button" class="rounded-full bg-white px-4 py-3 text-sm font-bold text-slate-900" @click="finishOnboardingToPanel">
                Ir al Panel de Control
              </button>
            </div>
          </article>
        </section>

        <aside class="hidden lg:block">
          <article class="mx-auto w-[290px] rounded-[28px] border border-slate-200 bg-slate-50 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="rounded-[22px] bg-white p-3">
              <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500">{{ onboarding.storeName || 'Tu tienda' }}</p>
                <span class="h-8 w-8 rounded-full bg-slate-100"></span>
              </div>
              <button class="mt-3 w-full rounded-full px-3 py-2 text-xs font-bold text-white" :style="{ backgroundColor: onboarding.brandColor }">
                Boton principal
              </button>
              <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-slate-100 p-2 text-[11px] text-slate-600">Producto</div>
                <div class="rounded-xl bg-slate-100 p-2 text-[11px] text-slate-600">Combo</div>
              </div>
            </div>
            <p class="mt-2 text-center text-[11px] text-slate-500">Vista previa en tiempo real</p>
          </article>
        </aside>
      </div>

      <footer v-if="onboardingStep <= 4" class="mt-6 flex items-center justify-between">
        <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600" :disabled="onboardingStep === 1" @click="prevOnboardingStep">
          Atras
        </button>
        <button
          type="button"
          class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="!onboardingCanContinue"
          @click="nextOnboardingStep"
        >
          {{ onboardingStep === 4 ? 'Finalizar' : 'Siguiente' }}
        </button>
      </footer>
    </div>
  </section>

  <section
    v-else
    class="rounded-[24px] bg-[#F8F9FA] p-2"
  >
    <Transition name="sidebar-fade">
      <div
        v-if="mobileSidebarOpen"
        class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-sm lg:hidden"
        @click="mobileSidebarOpen = false"
      ></div>
    </Transition>

    <aside
      class="fixed inset-y-2 left-2 z-50 flex w-[280px] flex-col rounded-[0_24px_24px_0] bg-white p-4 shadow-[0_12px_40px_rgba(15,23,42,0.18)] transition-transform duration-300 lg:hidden"
      :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-[120%]'"
    >
      <div class="mb-4 rounded-2xl bg-slate-50 p-4">
        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Dunamis Store</p>
        <p class="mt-1 text-base font-semibold text-slate-900">Panel Administrativo</p>
      </div>
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="text-[11px] uppercase tracking-wide text-slate-400">Admin</p>
          <p class="text-sm font-bold text-slate-900">Navegacion</p>
        </div>
        <button type="button" class="sidebar-icon-btn" @click="mobileSidebarOpen = false" aria-label="Cerrar menu">
          <X class="h-4 w-4" />
        </button>
      </div>
      <nav class="space-y-5">
        <div v-for="section in visibleSidebarSections" :key="`mobile-${section.title}`" class="space-y-2">
          <p class="px-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ section.title }}</p>
          <button
            v-for="item in section.items"
            :key="`mobile-item-${item.key}`"
            type="button"
            class="sidebar-item w-full"
            :class="{
              active: activeTab === item.key,
              'opacity-50': isTabLockedByPlan(item.key),
            }"
            @click="goToTab(item.key)"
          >
            <component :is="item.icon" class="h-4 w-4 shrink-0" />
            <span class="truncate text-sm font-medium">{{ item.label }}</span>
            <Lock v-if="isTabLockedByPlan(item.key)" class="ml-auto h-3.5 w-3.5 text-amber-500" />
          </button>
        </div>
      </nav>
      <div class="mt-auto border-t border-slate-200 pt-4">
        <button type="button" class="sidebar-logout-btn w-full" @click="quickLogout">
          <LogOut class="h-4 w-4" />
          <span class="text-sm font-medium">Cerrar sesion</span>
        </button>
      </div>
    </aside>

    <div class="flex gap-3">
      <aside
        class="sticky top-2 hidden h-[calc(100vh-1rem)] shrink-0 flex-col rounded-[0_24px_24px_0] bg-white p-3 shadow-[0_4px_20px_rgba(0,0,0,0.05)] lg:flex"
        :class="sidebarCollapsed ? 'w-[86px]' : 'w-[270px]'"
      >
        <div class="mb-4 rounded-2xl bg-slate-50 p-4">
          <p v-if="!sidebarCollapsed" class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Dunamis Store</p>
          <p v-if="!sidebarCollapsed" class="mt-1 text-sm font-semibold text-slate-900">Panel Administrativo</p>
          <p v-else class="text-center text-sm font-semibold text-slate-700">DS</p>
        </div>
        <div class="mb-4 flex items-center justify-between gap-2 px-1">
          <div v-if="!sidebarCollapsed" class="min-w-0">
            <p class="text-[11px] uppercase tracking-wide text-slate-400">Admin</p>
            <p class="truncate text-sm font-bold text-slate-900">Panel inteligente</p>
          </div>
          <button type="button" class="sidebar-icon-btn" @click="sidebarCollapsed = !sidebarCollapsed" aria-label="Colapsar menu">
            <ChevronLeft v-if="!sidebarCollapsed" class="h-4 w-4" />
            <ChevronRight v-else class="h-4 w-4" />
          </button>
        </div>

        <nav class="space-y-5 overflow-y-auto pr-1">
          <div v-for="section in visibleSidebarSections" :key="section.title" class="space-y-2">
            <p v-if="!sidebarCollapsed" class="px-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ section.title }}</p>
            <button
              v-for="item in section.items"
              :key="item.key"
              type="button"
              class="sidebar-item w-full"
              :class="{
                active: activeTab === item.key,
                collapsed: sidebarCollapsed,
                'opacity-50': isTabLockedByPlan(item.key),
              }"
              :title="sidebarCollapsed ? item.label : ''"
              :data-tooltip="sidebarCollapsed ? item.label : ''"
              @click="goToTab(item.key)"
            >
              <component :is="item.icon" class="h-4 w-4 shrink-0" />
              <span v-if="!sidebarCollapsed" class="truncate text-sm font-medium">{{ item.label }}</span>
              <Lock v-if="isTabLockedByPlan(item.key) && !sidebarCollapsed" class="ml-auto h-3.5 w-3.5 text-amber-500" />
            </button>
          </div>
        </nav>
        <div class="mt-auto border-t border-slate-200 pt-4">
          <button
            type="button"
            class="sidebar-logout-btn w-full"
            :class="{ collapsed: sidebarCollapsed }"
            :title="sidebarCollapsed ? 'Cerrar sesion' : ''"
            :data-tooltip="sidebarCollapsed ? 'Cerrar sesion' : ''"
            @click="quickLogout"
          >
            <LogOut class="h-4 w-4 shrink-0" />
            <span v-if="!sidebarCollapsed" class="text-sm font-medium">Cerrar sesion</span>
          </button>
        </div>
      </aside>

      <div class="min-w-0 flex-1 space-y-6">
        <Transition name="fade">
          <div
            v-if="rbacToastMessage"
            class="fixed right-4 top-4 z-[70] rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700 shadow-[0_4px_20px_rgba(0,0,0,0.08)]"
          >
            {{ rbacToastMessage }}
          </div>
        </Transition>

        <div v-if="statusBanner" class="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-700 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          {{ statusBanner }}
        </div>

        <header class="rounded-[24px] p-1">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <button type="button" class="sidebar-icon-btn lg:hidden" @click="mobileSidebarOpen = true" aria-label="Abrir menu">
                <Menu class="h-4 w-4" />
              </button>
              <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ headerTitle }}</h2>
                <p class="text-sm text-slate-500">{{ headerSubtitle }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <div class="min-w-[220px] rounded-2xl bg-white px-3 py-2 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Negocio</p>
                <p class="truncate text-sm font-semibold text-slate-900">{{ businessDisplayName }}</p>
                <p class="truncate text-[11px] text-slate-500">{{ businessPublicUrl }}</p>
              </div>
              <button type="button" class="sidebar-icon-btn" @click="copyBusinessUrl" title="Copiar URL del negocio">
                <Copy class="h-4 w-4" />
              </button>
              <button type="button" class="sidebar-icon-btn" @click="openStorePreview" title="Abrir tienda publica">
                <Eye class="h-4 w-4" />
              </button>
              <button type="button" class="sidebar-icon-btn" @click="openBusinessSettings" title="Configurar negocio">
                <Settings class="h-4 w-4" />
              </button>
              <AppButton v-if="activeTab !== 'home'" variant="soft" @click="goToTab('home')">Volver a acciones</AppButton>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-12">
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-3">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">Pedidos activos</p>
              <div class="mt-3 flex items-center justify-between">
                <p class="text-4xl font-extrabold text-slate-900">{{ activeOrders.length }}</p>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                  <ClipboardList class="h-5 w-5" />
                </span>
              </div>
            </article>
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-3">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">En preparacion</p>
              <div class="mt-3 flex items-center justify-between">
                <p class="text-4xl font-extrabold text-slate-900">{{ preparingOrders.length }}</p>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                  <Flame class="h-5 w-5" />
                </span>
              </div>
            </article>
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-3">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">Pendientes de envio</p>
              <div class="mt-3 flex items-center justify-between">
                <p class="text-4xl font-extrabold text-slate-900">{{ pendingShippingOrders.length }}</p>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                  <Truck class="h-5 w-5" />
                </span>
              </div>
            </article>
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-3">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">Ingresos del dia</p>
              <div class="mt-3 flex items-center justify-between">
                <p class="text-4xl font-extrabold text-slate-900">${{ todaySales.toFixed(0) }}</p>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                  <Wallet class="h-5 w-5" />
                </span>
              </div>
            </article>
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-6">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">Comparativa ventas</p>
              <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-3">
                  <p class="text-[11px] uppercase tracking-wide text-slate-500">Hoy</p>
                  <p class="text-2xl font-extrabold text-slate-900">${{ todaySales.toFixed(2) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3">
                  <p class="text-[11px] uppercase tracking-wide text-slate-500">Ayer</p>
                  <p class="text-2xl font-extrabold text-slate-900">${{ yesterdaySales.toFixed(2) }}</p>
                </div>
              </div>
            </article>
            <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] xl:col-span-6">
              <p class="text-[11px] uppercase tracking-wide text-slate-400">Alertas de stock</p>
              <div class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 p-3">
                <p class="text-lg font-extrabold text-slate-900">{{ lowIngredientsCount }}</p>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full" :class="lowIngredientsCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                  <TriangleAlert class="h-5 w-5" />
                </span>
              </div>
              <p class="mt-2 text-xs" :class="lowIngredientsCount > 0 ? 'text-amber-700' : 'text-emerald-700'">
                {{ lowIngredientsCount > 0 ? `Atencion: ${lowIngredientsCount} por agotarse` : 'Stock de ingredientes estable' }}
              </p>
            </article>
          </div>
        </header>
        
        <div v-if="activeTab === 'home'" class="space-y-4">
          <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">Estado del sistema</h3>
                <p class="mt-1 text-xs text-slate-500">Monitor de latido para operacion y mantenimiento.</p>
              </div>
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 active:scale-[0.98]"
                @click="supportModalOpen = true"
              >
                <LifeBuoy class="h-4 w-4" />
                Centro de ayuda
              </button>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-3">
              <div class="rounded-2xl bg-slate-50 p-3">
                <div class="flex items-center gap-2">
                  <span class="relative inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <Server class="h-4 w-4" />
                    <span class="absolute -right-0.5 -top-0.5 inline-flex h-2.5 w-2.5 rounded-full" :class="[statusDotClass(store.realtimeConnected ? 'ok' : 'critical'), store.realtimeConnected ? 'heartbeat-dot-pulse' : '']"></span>
                  </span>
                  <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Conexion</p>
                    <p class="text-sm font-semibold text-slate-900">{{ store.realtimeConnected ? 'Sistema online' : 'Canal degradado' }}</p>
                  </div>
                </div>
              </div>
              <div class="rounded-2xl bg-slate-50 p-3">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                    <Database class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Latencia</p>
                    <p class="text-sm font-semibold" :class="metricToneClass(heartbeatLatencyTone)">{{ heartbeatLatencyLabel }}</p>
                  </div>
                </div>
              </div>
              <div class="rounded-2xl bg-slate-50 p-3">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                    <ShieldCheck class="h-4 w-4" />
                  </span>
                  <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Mercado Pago</p>
                    <p class="text-sm font-semibold" :class="metricToneClass(heartbeatPaymentsTone)">{{ heartbeatPaymentsTone === 'ok' ? 'Conectado' : 'Con alertas' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-700">Estabilidad ultimos 30 dias</span>
                <span class="text-slate-500">Uptime {{ uptimePercent7d.toFixed(2) }}%</span>
              </div>
              <div class="flex items-center gap-1">
                <span
                  v-for="segment in uptime30dSegments"
                  :key="`uptime-segment-${segment.date}`"
                  class="h-4 min-w-0 flex-1 rounded-[4px]"
                  :class="segment.tone === 'ok' ? 'bg-emerald-400' : segment.tone === 'warn' ? 'bg-amber-400' : 'bg-rose-400'"
                  :title="segment.tooltip"
                ></span>
              </div>
            </div>
          </article>

          <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Centro de modulos</h3>
            <p class="mt-1 text-sm text-slate-500">Navega desde aqui: Inicio, Pedidos, Catalogo, Categorias, Combos, Caja, Equipo, Roles, Clientes y Auditoria.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <button
                v-for="section in visibleAdminHomeButtons"
                :key="section.key"
                type="button"
                class="w-full rounded-xl border px-3 py-3 text-left transition"
                :class="activeTab === section.key ? 'border-emerald-300 bg-emerald-50 shadow-sm' : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-sm'"
                @click="goToTab(section.key)"
              >
                <p class="text-sm font-semibold text-slate-900">{{ section.label }}</p>
                <p class="text-xs text-slate-500">{{ section.hint }}</p>
              </button>
            </div>
          </article>
        </div>

    <div v-if="activeTab === 'orders'" class="grid gap-6 lg:grid-cols-[minmax(0,7fr)_minmax(280px,3fr)]">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-[20px] bg-slate-50 p-3">
          <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Filtro de flujo:</span>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'all' }" @click="orderFlowFilter = 'all'">Todos</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'in_kitchen' }" @click="orderFlowFilter = 'in_kitchen'">En cocina</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'waiting_driver' }" @click="orderFlowFilter = 'waiting_driver'">Esperando repartidor</button>
          <button type="button" class="tab-btn" :class="{ active: orderFlowFilter === 'delayed' }" @click="orderFlowFilter = 'delayed'">Demorados</button>
        </div>

        <div class="sticky top-2 z-10 mb-3 rounded-2xl bg-white/75 px-4 py-2 backdrop-blur-md shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <div class="grid grid-cols-[120px_1fr_130px_150px_120px] gap-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            <span>Pedido</span>
            <span>Cliente</span>
            <span>Estado</span>
            <span>Pago</span>
            <span class="text-right">Acciones</span>
          </div>
        </div>

        <div v-if="!boardOrders.length" class="rounded-[24px] bg-slate-50 p-6 text-center">
          <p class="text-sm font-semibold text-slate-900">Esperando tu primera venta del dia</p>
          <p class="mt-1 text-xs text-slate-500">No hay pedidos para este filtro en este momento.</p>
        </div>

        <TransitionGroup name="order-fade" tag="div" class="space-y-3">
          <article
            v-for="order in boardOrders"
            :key="`order-${order.id}`"
            class="rounded-[20px] bg-transparent p-5 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.03)] transition hover:bg-white hover:shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
            :class="kdsCardClass(order.createdAt)"
          >
            <div class="grid items-start gap-3 md:grid-cols-[120px_1fr_130px_150px_120px]">
              <div class="py-[15px]">
                <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold font-mono text-slate-700">{{ orderCode(order.id) }}</span>
                <p class="mt-1 text-[11px] text-slate-400">{{ formatOrderCreatedAt(order.createdAt) }}</p>
                <p class="text-[11px] text-slate-400">{{ elapsedText(order.createdAt) }}</p>
              </div>
              <div class="py-[15px]">
                <p class="text-sm font-semibold text-slate-900">{{ order.customer }}</p>
                <p class="text-xs text-slate-500">{{ order.address }}</p>
                <p
                  v-if="orderTableLabel(order)"
                  class="mt-1 inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-extrabold tracking-wide text-sky-700"
                >
                  {{ orderTableLabel(order) }}
                </p>
                <p class="mt-1 text-xs text-slate-600">{{ orderLines(order) }}</p>
              </div>
              <div class="py-[15px]">
                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="orderPillClass(order.status)">{{ orderPillLabel(order.status) }}</span>
              </div>
              <div class="py-[15px]">
                <p class="text-xs text-slate-600">{{ paymentMethodLabel(order.paymentMethod) }}</p>
                <p class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="paymentStatusClass(order.paymentStatus)">
                  {{ paymentStatusLabel(order.paymentStatus) }}
                </p>
                <p class="mt-1 text-xs text-slate-500">ETA {{ order.etaMin }} min</p>
              </div>
              <div class="flex items-center justify-end gap-2 py-[15px]">
                <button
                  v-if="order.status === 'received'"
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-emerald-700 transition active:scale-95"
                  @click="acceptOrder(order.id)"
                  title="Aceptar"
                >
                  <ClipboardList class="h-4 w-4" />
                </button>
                <button
                  v-if="order.status === 'preparing'"
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-emerald-700 transition active:scale-95"
                  @click="completeOrder(order.id)"
                  title="Completar"
                >
                  <Check class="h-4 w-4" />
                </button>
                <button
                  v-if="order.status === 'ready'"
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-sky-100 text-sky-700 transition active:scale-95"
                  @click="assignDriver(order.id)"
                  title="Despachar"
                >
                  <Truck class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-emerald-100 hover:text-emerald-700 active:scale-95"
                  @click="openOrderEditor(order)"
                  title="Ver"
                >
                  <Eye class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-emerald-100 hover:text-emerald-700 active:scale-95"
                  @click="openOrderEditor(order)"
                  title="Editar"
                >
                  <Pencil class="h-4 w-4" />
                </button>
              </div>
            </div>
          </article>
        </TransitionGroup>
      </article>

      <aside class="space-y-6">
        <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <h3 class="text-sm font-semibold text-slate-900">Flujo operativo</h3>
          <div class="mt-3 space-y-3">
            <div>
              <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                <span>En cocina</span>
                <span>{{ preparingOrders.length }}</span>
              </div>
              <div class="h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-amber-400" :style="{ width: `${Math.min(100, preparingOrders.length * 14)}%` }"></div>
              </div>
            </div>
            <div>
              <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                <span>Esperando envio</span>
                <span>{{ pendingShippingOrders.length }}</span>
              </div>
              <div class="h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-sky-400" :style="{ width: `${Math.min(100, pendingShippingOrders.length * 14)}%` }"></div>
              </div>
            </div>
            <div>
              <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                <span>Activos</span>
                <span>{{ activeOrders.length }}</span>
              </div>
              <div class="h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-emerald-400" :style="{ width: `${Math.min(100, activeOrders.length * 10)}%` }"></div>
              </div>
            </div>
          </div>
        </article>

        <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
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
        </article>
      </aside>
    </div>

    <div v-if="activeTab === 'products' || activeTab === 'inventory'" class="space-y-4">
      <article v-if="activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900">Gestion de productos</h3>
          <div class="flex flex-wrap items-center gap-2">
            <AppButton variant="primary" @click="openCreateProductModal">Nuevo producto</AppButton>
            <AppButton variant="soft" @click="categoryModalOpen = true">Nueva categoria</AppButton>
          </div>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          Todo alta/edicion se abre en modal para mantener esta pantalla limpia.
        </p>
      </article>

      <article v-if="false && activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Configurador de producto</h3>
        <p class="text-xs text-slate-500">Divide y venceras: completa por secciones para evitar errores.</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'basic' }" @click="productFormTab = 'basic'">Basicos</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'recipe' }" @click="productFormTab = 'recipe'">Receta</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'ops' }" @click="productFormTab = 'ops'">Inventario/Logistica</button>
          <button type="button" class="tab-btn" :class="{ active: productFormTab === 'media' }" @click="productFormTab = 'media'">Multimedia</button>
        </div>
      </article>

      <article v-if="false && activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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

      <article v-if="false && activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Datos del producto</h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ isEditingProduct ? `Editando producto #${editingProductId}: ${productForm.name}` : 'Crea un producto nuevo' }}
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

          <div class="md:col-span-2 flex flex-wrap items-center justify-end gap-2">
            <AppButton v-if="isEditingProduct" variant="ghost" type="button" @click="resetProductForm">Cancelar edicion</AppButton>
            <AppButton variant="primary" type="submit">{{ isEditingProduct ? 'Guardar cambios' : 'Guardar producto' }}</AppButton>
          </div>
        </form>
      </article>

      <article v-if="activeTab === 'inventory'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Insumos e ingredientes</h3>
        <p class="mt-1 text-xs text-slate-500">Carga y control rapido de ingredientes para operacion diaria.</p>
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

      <article v-if="activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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

      <article v-if="activeTab === 'products'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Listado de productos</h3>
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

        <div class="mt-3 space-y-3">
          <article
            v-for="product in filteredProducts"
            :key="product.id"
            class="rounded-[20px] bg-slate-50 p-4 transition hover:bg-white hover:shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]"
          >
            <div class="grid items-center gap-3 md:grid-cols-[72px_1fr_170px_140px]">
              <div class="h-[72px] w-[72px] overflow-hidden rounded-2xl bg-white">
                <img
                  v-if="product.imageUrl"
                  :src="product.imageUrl"
                  :alt="product.name"
                  class="h-full w-full object-cover"
                  loading="lazy"
                />
                <div v-else class="grid h-full place-items-center text-xs font-semibold text-slate-400">Sin imagen</div>
              </div>
              <div>
                <p class="font-semibold text-slate-900">{{ product.name }}</p>
                <p class="text-xs text-slate-500">${{ product.price }} | {{ product.prepMin }} min | {{ product.category || 'Sin categoria' }}</p>
                <div class="mt-1 flex items-center gap-2">
                  <span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="productStockClass(product.stockQuantity, product.minStockQuantity)">
                    {{ productStockLabel(product.stockQuantity, product.minStockQuantity) }}
                  </span>
                  <span class="text-[11px]" :class="stockValueClass(product.stockQuantity, product.minStockQuantity)">Stock actual: {{ product.stockQuantity }}</span>
                </div>
              </div>
              <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                <input
                  class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                  type="checkbox"
                  :checked="selectedProductIds.includes(product.id)"
                  @change="toggleProductSelection(product.id)"
                />
                Seleccionar
              </label>
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 transition active:scale-95"
                  @click="editProductCard(product.id)"
                  title="Editar"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 transition active:scale-95"
                  @click="store.toggleProduct(product.id)"
                  title="Activar/Desactivar"
                >
                  <ChartNoAxesColumnIncreasing class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-700 transition active:scale-95"
                  @click="duplicateProductCard(product.id)"
                  title="Duplicar"
                >
                  <Copy class="h-4 w-4" />
                </button>
              </div>
            </div>
          </article>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'combos'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Ofertas y combos</h3>
        <p class="mt-1 text-xs text-slate-500">Recupera combos existentes, editalos y crea nuevos.</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <AppButton variant="primary" @click="openCreateComboModal">Crear combo</AppButton>
          <AppButton variant="soft" @click="bundleModalOpen = true">Crear bundle</AppButton>
        </div>
        <div class="mt-3">
          <input v-model="comboSearch" class="input max-w-sm" type="text" placeholder="Buscar combo por nombre o ID" />
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Combos existentes</h3>
        <div v-if="!filteredCombos.length" class="mt-3 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
          No hay combos cargados.
        </div>
        <div v-else class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="combo in filteredCombos" :key="`combo-card-${combo.id}`" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <img
              v-if="combo.imageUrl"
              :src="combo.imageUrl"
              :alt="combo.name"
              class="mb-2 h-24 w-full rounded-lg border border-slate-200 object-cover"
            />
            <div v-else class="mb-2 grid h-24 w-full place-items-center rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-400">
              Sin imagen
            </div>
            <p class="font-semibold text-slate-900">#{{ combo.id }} | {{ combo.name }}</p>
            <p class="mt-1 text-xs text-slate-500">${{ combo.basePrice }} | {{ combo.items.length }} item(s)</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <AppButton variant="soft" @click="editComboCard(combo)">Editar</AppButton>
              <AppButton variant="ghost" @click="deleteComboCard(combo.id)">Eliminar</AppButton>
            </div>
          </div>
        </div>
      </article>

      <article v-if="false" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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
              <select v-model="comboCategoryFilter" class="input sm:col-span-3">
                <option v-for="category in productCategories" :key="`combo-cat-${category}`" :value="category">
                  {{ category === 'all' ? 'Todas las categorias' : category }}
                </option>
              </select>
              <select v-model.number="comboProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in comboSelectableProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
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

      <article v-if="false" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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
              <select v-model="bundleCategoryFilter" class="input sm:col-span-3">
                <option v-for="category in productCategories" :key="`bundle-cat-${category}`" :value="category">
                  {{ category === 'all' ? 'Todas las categorias' : category }}
                </option>
              </select>
              <select v-model.number="bundleProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in bundleSelectableProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
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
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900">Categorias del menu</h3>
          <AppButton variant="soft" @click="categoryModalOpen = true">Crear categoria</AppButton>
        </div>
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

    <div v-if="activeTab === 'dailymenus'" class="space-y-4">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Menus del dia</h3>
        <p class="mt-1 text-xs text-slate-500">Crea menus por franja horaria, dias y prioridad.</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <AppButton variant="primary" @click="openCreateDailyMenuModal">Nuevo menu diario</AppButton>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Configuraciones existentes</h3>
        <div v-if="!store.dailyMenus.length" class="mt-3 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
          No hay menus diarios cargados.
        </div>
        <div v-else class="mt-3 space-y-3">
          <div v-for="menu in store.dailyMenus" :key="`daily-menu-${menu.id}`" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-slate-900">#{{ menu.id }} | {{ menu.name }}</p>
                <p class="text-xs text-slate-500">
                  Slot: {{ menu.slot }} | Prioridad: {{ menu.priority }} | {{ menu.isActive ? 'Activo' : 'Inactivo' }}
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <AppButton variant="soft" @click="editDailyMenuCard(menu)">Editar</AppButton>
                <AppButton variant="soft" @click="openDailyMenuItemModal(menu.id)">Agregar item</AppButton>
                <AppButton variant="ghost" @click="deleteDailyMenuCard(menu.id)">Eliminar</AppButton>
              </div>
            </div>

            <p v-if="menu.description" class="mt-2 text-xs text-slate-600">{{ menu.description }}</p>

            <div class="mt-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Items del menu</p>
              <div v-if="!menu.items.length" class="mt-2 rounded-lg border border-dashed border-slate-300 p-3 text-xs text-slate-500">
                Este menu no tiene items todavia.
              </div>
              <div v-else class="mt-2 space-y-2">
                <div v-for="item in menu.items" :key="`menu-item-${item.id}`" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                  <div class="flex items-center gap-2">
                    <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.name || 'item'" class="h-8 w-8 rounded border border-slate-200 object-cover" />
                    <div>
                      <p class="text-xs font-semibold text-slate-800">{{ item.name || `Item #${item.itemId}` }}</p>
                      <p class="text-[11px] text-slate-500">
                        {{ item.itemType }} | Base: ${{ Number(item.basePrice || 0).toFixed(2) }} | Desc: {{ dailyMenuItemDiscountLabel(item) }} | Promo:
                        {{ item.promoPrice === null ? 'sin promo' : `$${Number(item.promoPrice).toFixed(2)}` }}
                      </p>
                    </div>
                  </div>
                  <AppButton variant="ghost" @click="removeDailyMenuItemFromMenu(menu.id, item)">Quitar</AppButton>
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'cashbox'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Balance de caja</h3>
            <p class="text-xs text-slate-500">Vista inmediata del estado operativo.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm transition active:scale-95"
              :class="cashSessionOpen ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-700 hover:bg-slate-800'"
              @click="toggleCashSession"
            >
              <DoorOpen v-if="cashSessionOpen" class="h-4 w-4" />
              <DoorClosed v-else class="h-4 w-4" />
              {{ cashSessionOpen ? 'Cierre de caja' : 'Apertura de caja' }}
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-95"
              @click="cashMovementModalOpen = true"
            >
              <Plus class="h-4 w-4" />
              Nuevo movimiento
            </button>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
          <article class="rounded-[24px] bg-emerald-600 p-5 text-white shadow-[0_4px_20px_rgba(5,150,105,0.25)]">
            <p class="text-xs uppercase tracking-wide text-emerald-100">Total en caja</p>
            <p class="mt-2 text-4xl font-extrabold">${{ cashBalanceStats.total.toFixed(2) }}</p>
          </article>
          <article class="rounded-[24px] bg-emerald-50 p-5">
            <div class="flex items-center gap-2 text-emerald-700">
              <ArrowUpRight class="h-4 w-4" />
              <p class="text-xs uppercase tracking-wide">Ingresos del dia</p>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">+ ${{ cashBalanceStats.income.toFixed(2) }}</p>
          </article>
          <article class="rounded-[24px] bg-rose-50 p-5">
            <div class="flex items-center gap-2 text-rose-700">
              <ArrowDownRight class="h-4 w-4" />
              <p class="text-xs uppercase tracking-wide">Egresos / retiros</p>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-rose-700">- ${{ cashBalanceStats.expense.toFixed(2) }}</p>
          </article>
          <article class="rounded-[24px] bg-slate-900 p-5 text-white">
            <p class="text-xs uppercase tracking-wide text-slate-300">Balance neto</p>
            <p class="mt-2 text-3xl font-extrabold">${{ netCashBalance.toFixed(2) }}</p>
            <p class="mt-1 text-[11px] text-slate-300">Incluye gastos administrativos: ${{ adminFixedExpensesTotal.toFixed(2) }}</p>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="tab-btn" :class="{ active: cashFilterPreset === 'today' }" @click="cashFilterPreset = 'today'">Hoy</button>
          <button type="button" class="tab-btn" :class="{ active: cashFilterPreset === 'yesterday' }" @click="cashFilterPreset = 'yesterday'">Ayer</button>
          <button type="button" class="tab-btn" :class="{ active: cashFilterPreset === 'week' }" @click="cashFilterPreset = 'week'">Ultima semana</button>
          <button type="button" class="tab-btn" :class="{ active: cashFilterPreset === 'custom' }" @click="cashFilterPreset = 'custom'">Rango</button>
          <div v-if="cashFilterPreset === 'custom'" class="ml-2 flex flex-wrap items-center gap-2">
            <label class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs text-slate-600">
              <CalendarDays class="h-4 w-4" />
              <input v-model="cashDateFrom" type="date" class="bg-transparent outline-none" />
            </label>
            <label class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs text-slate-600">
              <CalendarDays class="h-4 w-4" />
              <input v-model="cashDateTo" type="date" class="bg-transparent outline-none" />
            </label>
          </div>
          <div class="ml-auto flex flex-wrap items-center gap-2">
            <button type="button" class="tab-btn" :class="{ active: cashShiftFilter === 'all' }" @click="cashShiftFilter = 'all'">Todo el dia</button>
            <button type="button" class="tab-btn" :class="{ active: cashShiftFilter === 'morning' }" @click="cashShiftFilter = 'morning'">Manana</button>
            <button type="button" class="tab-btn" :class="{ active: cashShiftFilter === 'afternoon' }" @click="cashShiftFilter = 'afternoon'">Tarde</button>
            <button type="button" class="tab-btn" :class="{ active: cashShiftFilter === 'night' }" @click="cashShiftFilter = 'night'">Noche</button>
          </div>
        </div>
      </article>

      <div class="grid gap-4 lg:grid-cols-[minmax(0,7fr)_minmax(280px,3fr)]">
        <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <h3 class="text-sm font-semibold text-slate-900">Historial de movimientos</h3>
          <div v-if="!filteredCashMovements.length" class="mt-3 rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
            No hay movimientos para los filtros seleccionados.
          </div>
          <div v-else class="mt-3">
            <button
              v-for="movement in filteredCashMovements"
              :key="movement.id"
              type="button"
              class="cash-row w-full text-left"
              :class="{ 'cursor-pointer': movement.source === 'order', 'cursor-default': movement.source !== 'order' }"
              :disabled="movement.source !== 'order'"
              @click="openCashDetail(movement)"
            >
              <div class="w-16 shrink-0 text-xs text-slate-400">{{ formatCashTime(movement.createdAt) }}</div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ movement.concept }}</p>
                <p class="text-xs text-slate-500">{{ formatCashDate(movement.createdAt) }}</p>
              </div>
              <div
                class="shrink-0 text-sm font-bold"
                :class="movement.type === 'income' ? 'text-emerald-700' : 'text-rose-700'"
              >
                {{ movement.type === 'income' ? '+' : '-' }} ${{ movement.amount.toFixed(2) }}
              </div>
            </button>
          </div>
        </article>

        <aside class="space-y-4">
          <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <h3 class="text-sm font-semibold text-slate-900">Conciliacion repartidores</h3>
            <div v-if="driverPendingCash.length" class="mt-3 space-y-2">
              <div
                v-for="row in driverPendingCash"
                :key="`driver-pending-${row.id}`"
                class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2"
              >
                <p class="text-sm font-semibold text-slate-900">{{ row.name }}</p>
                <p class="text-sm font-semibold text-rose-700">${{ row.pending.toFixed(2) }} pendiente</p>
              </div>
            </div>
            <p v-else class="mt-2 text-sm text-slate-500">No hay efectivo pendiente por repartir.</p>
          </article>
          <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <h3 class="text-sm font-semibold text-slate-900">Resumen rapido</h3>
            <p class="mt-2 text-xs text-slate-500">Total movimientos: {{ filteredCashMovements.length }}</p>
            <p class="mt-1 text-xs text-slate-500">Caja {{ cashSessionOpen ? 'abierta' : 'cerrada' }}</p>
            <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
              <CircleDollarSign class="h-4 w-4" />
              Corte rapido disponible
            </div>
          </article>

          <PlanGuard
            plan="full"
            :current-plan="currentPlan"
            title="Modulo pro: auditoria inteligente de caja"
            cta-label="Desbloquea esta funcion"
            @upgrade="openUpgradeModalForPlanGuard($event, 'Auditoria avanzada de caja')"
          >
            <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                  <BarChart3 class="h-4 w-4" />
                </span>
                <h3 class="text-sm font-semibold text-slate-900">Auditoria avanzada de caja</h3>
              </div>
              <p class="mt-2 text-xs text-slate-500">Analisis de desvio por turno, trazabilidad de anulaciones y alertas de diferencia automatica.</p>
              <div class="mt-3 h-2 rounded-full bg-slate-200">
                <div class="h-2 w-2/3 rounded-full bg-emerald-500"></div>
              </div>
              <p class="mt-2 text-[11px] text-slate-500">Indice de consistencia del turno: 83%</p>
            </article>
          </PlanGuard>
        </aside>
      </div>

      <Transition name="sidebar-fade">
        <div
          v-if="cashDrawerOpen"
          class="fixed inset-0 z-[70] bg-slate-950/25 backdrop-blur-sm"
          @click="closeCashDetail"
        ></div>
      </Transition>
      <aside
        class="fixed right-0 top-0 z-[80] h-full w-full max-w-[520px] bg-white p-5 shadow-[-12px_0_32px_rgba(15,23,42,0.18)] transition-transform duration-300"
        :class="cashDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
      >
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Detalle de venta</p>
            <p class="text-sm font-semibold text-slate-900">{{ selectedCashMovement?.concept || 'Movimiento' }}</p>
          </div>
          <button type="button" class="sidebar-icon-btn" @click="closeCashDetail">
            <X class="h-4 w-4" />
          </button>
        </div>
        <div v-if="selectedCashOrder" class="mt-4 space-y-3">
          <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
            <p>Cliente: <span class="font-semibold text-slate-900">{{ selectedCashOrder.customer }}</span></p>
            <p>Direccion: <span class="font-semibold text-slate-900">{{ selectedCashOrder.address }}</span></p>
            <p>Total: <span class="font-semibold text-slate-900">${{ selectedCashOrder.total.toFixed(2) }}</span></p>
          </div>
          <div class="space-y-2">
            <article
              v-for="(item, idx) in selectedCashOrder.items"
              :key="`cash-order-item-${idx}`"
              class="rounded-xl border border-slate-200 px-3 py-2"
            >
              <p class="text-sm font-semibold text-slate-900">{{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}</p>
              <p class="text-xs text-slate-500">Subtotal: ${{ item.subtotal.toFixed(2) }}</p>
            </article>
          </div>
        </div>
      </aside>
    </div>

    <div v-if="activeTab === 'coupons'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Gestion de cupones</h3>
            <p class="mt-1 text-xs text-slate-500">Ofertas visuales con restricciones y control de uso.</p>
          </div>
          <AppButton variant="primary" @click="couponModalOpen = true">Nuevo cupon</AppButton>
        </div>
      </article>

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="coupon in coupons"
          :key="coupon.id"
          class="coupon-ticket rounded-[20px] p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
          :class="couponTypeCardClass(coupon)"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="text-lg font-extrabold tracking-wide text-slate-900">{{ coupon.code }}</p>
              <p class="text-sm font-semibold" :class="couponTypeValueClass(coupon)">
                {{ couponValueLabel(coupon) }}
              </p>
            </div>
            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="couponStatusClass(coupon)">
              {{ couponStatusLabel(coupon) }}
            </span>
          </div>
          <div class="mt-3 space-y-1 text-xs text-slate-600">
            <p>Pedido minimo: ${{ coupon.minOrder.toFixed(2) }}</p>
            <p>Expira: {{ coupon.expiresAt || 'Sin fecha' }}</p>
            <p>Por cliente: {{ coupon.usesPerClient }} usos max</p>
            <p class="font-semibold text-slate-700">Usados vs disponibles</p>
            <div class="grid grid-cols-2 gap-1">
              <div class="h-1.5 rounded-full bg-slate-200">
                <div class="h-1.5 rounded-full bg-slate-700" :style="{ width: `${couponUsagePercent(coupon)}%` }"></div>
              </div>
              <div class="h-1.5 rounded-full bg-slate-200">
                <div class="h-1.5 rounded-full bg-emerald-500" :style="{ width: `${100 - couponUsagePercent(coupon)}%` }"></div>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">{{ coupon.usedCount }} / {{ coupon.totalUsesLimit }} usados</p>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button type="button" class="tab-btn" @click="toggleCoupon(coupon.id)">
              {{ coupon.active ? 'Pausar' : 'Activar' }}
            </button>
            <button type="button" class="tab-btn" @click="removeCoupon(coupon.id)">Eliminar</button>
          </div>
        </article>
      </div>
      <div v-if="!coupons.length" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
        Aun no hay cupones creados.
      </div>
    </div>

    <div v-if="activeTab === 'expenses'" class="space-y-6" style="font-family: Inter, Arial, sans-serif;">
      <section class="grid gap-6 xl:grid-cols-12">
        <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)] transition duration-200 hover:scale-[1.02] hover:shadow-[0_18px_36px_rgba(15,23,42,0.14)] xl:col-span-4">
          <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Margen promedio de la tienda</p>
          <div class="mt-4 flex items-center gap-4">
            <div class="h-20 w-20 rounded-full p-2" :style="marginAvgStorePieStyle">
              <div class="grid h-full w-full place-items-center rounded-full bg-white text-sm font-bold text-slate-900">{{ marginAvgStore.toFixed(0) }}%</div>
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900">{{ marginAvgStore.toFixed(1) }}%</p>
              <p class="text-sm font-medium text-slate-500">rentabilidad neta promedio</p>
            </div>
          </div>
        </article>

        <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)] transition duration-200 hover:scale-[1.02] hover:shadow-[0_18px_36px_rgba(15,23,42,0.14)] xl:col-span-4">
          <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Costo de inventario actual</p>
          <p class="mt-4 text-2xl font-bold text-slate-900">{{ moneyLabel(ingredientInventoryMonthlyCost) }}</p>
          <div class="mt-3 flex items-center justify-between text-sm font-medium text-slate-600">
            <span>Insumos cargados</span>
            <span class="font-bold text-slate-900">{{ ingredientCards.length }}</span>
          </div>
          <div class="mt-1 flex items-center justify-between text-sm font-medium text-slate-600">
            <span>Stock critico</span>
            <span class="font-bold text-rose-600">{{ ingredientCriticalStockCount }}</span>
          </div>
        </article>

        <article class="rounded-[24px] border border-amber-200 bg-amber-50 p-6 shadow-[0_8px_24px_rgba(180,83,9,0.08)] transition duration-200 hover:scale-[1.02] hover:shadow-[0_18px_36px_rgba(180,83,9,0.16)] xl:col-span-4">
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs uppercase tracking-[0.12em] text-amber-700">Alerta de inflacion</p>
            <TriangleAlert class="h-4 w-4 text-amber-700" />
          </div>
          <p class="mt-2 text-sm font-medium text-amber-800">Insumos con suba superior al 10% en la ultima semana.</p>
          <div class="mt-4 space-y-2">
            <div v-if="!ingredientInflationAlerts.length" class="rounded-xl bg-white/70 px-3 py-2 text-sm font-medium text-amber-700">Sin alertas criticas esta semana.</div>
            <div v-for="item in ingredientInflationAlerts" :key="`inflation-${item.id}`" class="rounded-xl bg-white/80 px-3 py-2 text-sm">
              <div class="flex items-center justify-between gap-2">
                <span class="font-bold text-slate-900">{{ item.name }}</span>
                <span class="font-bold text-amber-700">+{{ item.inflation.toFixed(1) }}%</span>
              </div>
            </div>
          </div>
        </article>
      </section>

      <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-slate-900">Lista compacta de insumos</h3>
            <p class="mt-1 text-sm font-medium text-slate-500">Stock, costo y rentabilidad sugerida en una sola vista.</p>
          </div>
          <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-2 py-2">
            <span class="text-xs font-semibold text-slate-600">Actualizacion masiva</span>
            <input v-model.number="bulkPriceIncreasePct" class="input max-w-[90px]" type="number" min="-50" step="1" placeholder="%"/>
            <button type="button" class="tab-btn active" @click="runMassPriceUpdate">Aplicar</button>
          </div>
        </div>

        <form class="mt-6 grid gap-3 md:grid-cols-[1.25fr_100px_1fr_130px_120px_110px_auto]" @submit.prevent="submitIngredient">
          <input id="expense-ingredient-name" v-model="ingredientForm.name" class="input" type="text" required placeholder="Nombre del insumo" />
          <input v-model="ingredientForm.unit" class="input" type="text" placeholder="Unidad" />
          <input v-model="ingredientForm.supplier" class="input" type="text" placeholder="Proveedor" />
          <input v-model.number="ingredientForm.additionalPrice" class="input" type="number" min="0" step="0.01" placeholder="Costo unitario" />
          <input v-model.number="ingredientForm.stockQuantity" class="input" type="number" min="0" step="0.01" placeholder="Stock" />
          <input v-model.number="ingredientForm.minStock" class="input" type="number" min="0" step="0.1" placeholder="Stock min." />
          <AppButton variant="primary" type="submit">Agregar</AppButton>
        </form>

        <div v-if="!ingredientCards.length" class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
          <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm">
            <PackagePlus class="h-8 w-8" />
          </div>
          <h4 class="mt-4 text-lg font-bold text-slate-900">Todavia no hay insumos cargados</h4>
          <p class="mt-1 text-sm font-medium text-slate-500">Empeza con tu materia prima para activar alertas y calculos de rentabilidad.</p>
          <button type="button" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-[0_10px_24px_rgba(16,185,129,0.24)] transition hover:brightness-110" @click="document.getElementById('expense-ingredient-name')?.focus()">
            Cargar mi primer insumo
          </button>
        </div>

        <div v-else class="mt-6 space-y-4">
          <article
            v-for="ingredient in ingredientCards"
            :key="`expense-card-${ingredient.id}`"
            class="rounded-2xl border border-slate-200 bg-white p-4 transition duration-200 hover:scale-[1.02] hover:shadow-[0_14px_28px_rgba(15,23,42,0.12)]"
          >
            <div class="grid items-center gap-4 md:grid-cols-[1.2fr_1fr_1fr_auto]">
              <div>
                <p class="flex items-center gap-2 text-base font-bold text-slate-900">
                  <span class="h-2.5 w-2.5 rounded-full" :class="ingredientStockDotClass(ingredient)"></span>
                  {{ ingredient.name }}
                </p>
                <p class="text-xs font-medium text-slate-500">ID #{{ ingredient.id }}</p>
                <div class="mt-2">
                  <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-slate-500">
                    <span>Stock disponible</span>
                    <span>{{ Number(ingredient.stockQuantity || 0).toFixed(2) }} {{ ingredientMetaFor(ingredient.id).unit || 'un' }}</span>
                  </div>
                  <div class="h-2 rounded-full bg-slate-200">
                    <div class="h-2 rounded-full transition-all" :class="ingredientStockBarClass(ingredient)" :style="{ width: `${Math.max(4, ingredient.stockPct)}%` }"></div>
                  </div>
                </div>
              </div>

              <div class="rounded-xl bg-white p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Costo vs Venta</p>
                <p class="mt-1 flex items-center gap-1 text-sm font-bold text-slate-900">
                  Costo: {{ moneyLabel(ingredient.cost) }}
                  <BarChart3 class="h-3.5 w-3.5" :class="ingredientPriceTrendClass(ingredient)" :title="ingredientPriceTrendLabel(ingredient)" />
                </p>
                <p class="text-sm font-medium text-emerald-700">Sugerido: {{ moneyLabel(ingredient.suggested) }}</p>
              </div>

              <div class="grid gap-2">
                <input :value="ingredientMetaFor(ingredient.id).supplier" class="input" type="text" placeholder="Proveedor" @input="onIngredientMetaSupplierInput(ingredient.id, $event)" />
                <input :value="ingredientMetaFor(ingredient.id).purchasePrice" class="input" type="number" min="0" step="0.01" placeholder="Costo unitario" @input="onIngredientMetaPriceInput(ingredient.id, $event)" />
                <input :value="ingredientMetaFor(ingredient.id).minStock" class="input" type="number" min="0" step="0.1" placeholder="Stock minimo" @input="onIngredientMetaMinStockInput(ingredient.id, $event)" />
              </div>

              <div class="flex items-start justify-end">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">{{ ingredient.profitPct.toFixed(0) }}% Profit</span>
              </div>
            </div>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)]">
        <h3 class="text-lg font-bold text-slate-900">Calculadora de costos por producto</h3>
        <p class="mt-1 text-sm font-medium text-slate-500">Escandallo dinamico con semaforo de margen y precio sugerido (objetivo 60%).</p>
        <div class="mt-6 grid gap-3 md:grid-cols-[1fr_1fr_110px_auto]">
          <select v-model.number="selectedRecipeProductId" class="input">
            <option :value="0">Seleccionar producto</option>
            <option v-for="product in store.products" :key="`recipe-product-expense-${product.id}`" :value="product.id">{{ product.name }}</option>
          </select>
          <select v-model.number="recipeIngredientDraft.ingredientId" class="input">
            <option :value="0">Seleccionar insumo</option>
            <option v-for="ingredient in store.ingredients" :key="`recipe-ingredient-expense-${ingredient.id}`" :value="ingredient.id">
              {{ ingredient.name }} ({{ moneyLabel(recipeIngredientCost(ingredient.id)) }})
            </option>
          </select>
          <input v-model.number="recipeIngredientDraft.qty" class="input" type="number" min="0.1" step="0.1" placeholder="Cant." />
          <AppButton variant="soft" @click="addRecipeLine">Agregar insumo</AppButton>
        </div>
        <div class="mt-5 space-y-2">
          <div v-if="!selectedRecipeLines.length" class="rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm font-medium text-slate-500">
            Agrega insumos para generar el escandallo visual.
          </div>
          <div v-for="line in selectedRecipeLines" :key="`recipe-line-expense-${selectedRecipeProductId}-${line.ingredientId}`" class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3">
            <p class="text-sm font-semibold text-slate-800">
              {{ store.getIngredient(line.ingredientId)?.name || `Insumo #${line.ingredientId}` }} x{{ line.qty }}
              <span class="text-xs font-medium text-slate-500">subtotal {{ moneyLabel(recipeIngredientCost(line.ingredientId) * line.qty) }}</span>
            </p>
            <button type="button" class="tab-btn" @click="removeRecipeLine(selectedRecipeProductId, line.ingredientId)">Quitar</button>
          </div>
        </div>
        <div class="mt-6 grid gap-3 md:grid-cols-3">
          <article class="rounded-xl bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Costo total</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ moneyLabel(selectedRecipeCostTotal) }}</p>
          </article>
          <article class="rounded-xl bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Precio actual</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ moneyLabel(Number(store.getProduct(selectedRecipeProductId)?.price || 0)) }}</p>
            <p class="text-xs font-semibold" :class="marginClass(selectedRecipeMargin)">{{ selectedRecipeMargin.toFixed(1) }}% margen</p>
          </article>
          <article class="rounded-xl bg-emerald-50 p-3">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Precio sugerido (60%)</p>
            <p class="mt-1 text-xl font-bold text-emerald-700">{{ moneyLabel(selectedRecipeSuggestedPrice) }}</p>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)]">
        <h3 class="text-lg font-bold text-slate-900">Analisis de productos</h3>
        <p class="mt-1 text-sm font-medium text-slate-500">Selecciona un producto para abrir el analisis detallado y simular precio de venta.</p>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <button
            v-for="row in productMarginHealth"
            :key="`margin-row-${row.id}`"
            type="button"
            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left transition duration-200 hover:scale-[1.02] hover:shadow-[0_14px_28px_rgba(15,23,42,0.12)]"
            @click="openProductAnalysis(row.id)"
          >
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-bold text-slate-900">{{ row.name }}</p>
              <p class="text-sm font-bold" :class="marginClass(row.margin)">{{ row.margin.toFixed(1) }}%</p>
            </div>
            <p class="mt-1 text-xs font-medium text-slate-500">Costo receta {{ moneyLabel(row.cost) }} | Venta {{ moneyLabel(row.price) }}</p>
            <div class="mt-3 h-2 rounded-full bg-slate-200">
              <div class="h-2 rounded-full" :class="row.margin < 15 ? 'bg-rose-600' : row.margin < 30 ? 'bg-orange-500' : row.margin > 60 ? 'bg-emerald-500' : 'bg-amber-500'" :style="{ width: `${Math.max(6, Math.min(100, row.margin))}%` }"></div>
            </div>
          </button>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)]">
        <h3 class="text-sm font-bold text-slate-900">Gasto administrativo</h3>
        <p class="mt-1 text-xs font-medium text-slate-500">Servicios, alquiler o personal. Impacta directo en balance neto de caja.</p>
        <div class="mt-4 flex flex-wrap items-center gap-2">
          <button type="button" class="tab-btn" :class="{ active: adminExpenseFilter === 'all' }" @click="adminExpenseFilter = 'all'">Todos</button>
          <button type="button" class="tab-btn" :class="{ active: adminExpenseFilter === 'fixed' }" @click="adminExpenseFilter = 'fixed'">Fijos</button>
          <button type="button" class="tab-btn" :class="{ active: adminExpenseFilter === 'variable' }" @click="adminExpenseFilter = 'variable'">Variables</button>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-[180px_1fr]">
          <div class="rounded-xl bg-slate-50 p-3">
            <div class="mx-auto h-28 w-28 rounded-full" :style="expensePieStyle"></div>
            <div class="mt-3 space-y-1">
              <p v-if="!expensePieSegments.length" class="text-xs font-medium text-slate-500">Sin datos de este mes.</p>
              <p v-for="segment in expensePieSegments" :key="`expense-segment-${segment.category}`" class="text-xs font-medium text-slate-600">
                {{ segment.category }}: {{ segment.pct.toFixed(0) }}%
              </p>
            </div>
          </div>
          <form class="grid gap-3 md:grid-cols-[1fr_140px_140px_auto]" @submit.prevent="submitAdminExpense">
          <select v-model="adminExpenseForm.category" class="input">
            <option value="Servicios">Servicios</option>
            <option value="Personal">Personal</option>
            <option value="Alquiler">Alquiler</option>
            <option value="Insumos extra">Insumos extra</option>
            <option value="Reparaciones">Reparaciones</option>
          </select>
          <input v-model.number="adminExpenseForm.amount" class="input" type="number" min="0" step="0.01" placeholder="Monto" />
          <select v-model="adminExpenseForm.frequency" class="input">
            <option value="once">Unica vez</option>
            <option value="monthly">Mensual</option>
          </select>
            <AppButton class="hidden md:inline-flex" variant="primary" type="submit">Registrar gasto</AppButton>
          </form>
        </div>
        <div class="mt-6 space-y-3">
          <article v-for="expense in filteredAdminExpenses" :key="expense.id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-3">
            <div>
              <p class="text-sm font-bold text-slate-900">{{ expense.category }} - {{ moneyLabel(expense.amount) }}</p>
              <p class="text-xs font-medium text-slate-500">
                {{ expense.frequency === 'monthly' ? 'Mensual' : 'Unica vez' }} - {{ expense.kind === 'fixed' ? 'Fijo' : 'Variable' }}
              </p>
            </div>
            <button type="button" class="tab-btn" @click="removeAdminExpense(expense.id)">Eliminar</button>
          </article>
        </div>
        <div class="sticky bottom-2 mt-6 rounded-2xl bg-white/95 p-2 backdrop-blur md:hidden">
          <AppButton class="w-full" variant="primary" type="button" @click="submitAdminExpense">Registrar gasto</AppButton>
        </div>
      </article>

      <AppModal :open="productAnalysisModalOpen" max-width-class="max-w-2xl" scrollable @close="productAnalysisModalOpen = false">
        <div class="space-y-6 p-1">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Analisis de producto</p>
              <h3 class="mt-1 text-xl font-bold text-slate-900">{{ productAnalysisItem?.name || 'Producto' }}</h3>
            </div>
            <button type="button" class="rounded-lg bg-slate-100 p-2 text-slate-600" @click="productAnalysisModalOpen = false">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Escandallo</p>
            <p class="mt-2 text-sm font-medium text-slate-700">{{ productAnalysisBreakdownText }}</p>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-bold text-slate-900">Simulador de precio de venta</p>
              <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Margen {{ productAnalysisMargin.toFixed(1) }}%</span>
            </div>
            <input v-model.number="productAnalysisSalePrice" class="mt-4 w-full" type="range" :min="productAnalysisPriceMin" :max="productAnalysisPriceMax" step="100" />
            <div class="mt-3 flex flex-wrap items-center justify-between text-sm font-medium text-slate-600">
              <span>Costo total: <strong class="text-slate-900">{{ moneyLabel(productAnalysisCost) }}</strong></span>
              <span>Precio simulado: <strong class="text-slate-900">{{ moneyLabel(productAnalysisSalePrice) }}</strong></span>
            </div>
            <p class="mt-2 text-xs" :class="marginClass(productAnalysisMargin)">{{ marginAlert(productAnalysisMargin) }}</p>
          </div>
        </div>
      </AppModal>
    </div>

    <div v-if="activeTab === 'tables'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Gestion de mesas</h3>
        <p class="mt-1 text-xs text-slate-500">Define cuantas mesas opera el salon y genera IDs unicos.</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <input v-model.number="tablesCountDraft" class="input max-w-[220px]" type="number" min="1" max="300" placeholder="Cantidad de mesas" />
          <AppButton variant="primary" @click="generateTables">Generar mesas</AppButton>
          <AppButton variant="soft" @click="openQrPrintModal">Impresion masiva de QRs</AppButton>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Mosaico de mesas</h3>
        <p class="mt-1 text-xs text-slate-500">Puedes arrastrar y soltar para ordenar visualmente el salon.</p>
        <div v-if="!tables.length" class="mt-3 rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
          Genera mesas para empezar con el modo salon.
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
          <button
            v-for="table in tables"
            :key="table.code"
            type="button"
            draggable="true"
            class="table-tile aspect-square rounded-3xl bg-slate-50 p-3 text-left transition"
            :class="selectedTableId === table.id ? 'ring-2 ring-sky-400' : ''"
            @click="selectTable(table.id)"
            @dragstart="onTableDragStart(table.id)"
            @dragover.prevent
            @drop="onTableDrop(table.id)"
          >
            <p class="text-sm font-bold text-slate-900">{{ `Mesa ${String(table.id).padStart(2, '0')}` }}</p>
            <p class="text-[11px] text-slate-500">{{ table.code }}</p>
            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="tableStatusClass(table.id)">
              {{ tableStatusLabel(table.id) }}
            </span>
            <p class="mt-2 text-xs font-semibold text-slate-700">{{ hasActiveTableOrder(table.id) ? `$${tableOpenTotal(table.id).toFixed(2)}` : 'Sin consumo' }}</p>
            <p class="text-[11px] text-slate-500">{{ tableElapsedText(table.id) }}</p>
            <p
              v-if="tableHasKitchenPending(table.id)"
              class="mt-2 inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-700"
            >
              Pedido en cocina
            </p>
          </button>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'qr'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Centro de generacion QR</h3>
        <p class="mt-1 text-xs text-slate-500">Define destino, agrega logo y genera cartel listo para impresion.</p>
        <div v-if="!tables.length" class="mt-3 rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
          No hay mesas registradas para generar QR.
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="tab-btn"
            :class="{ active: qrDestinationMode === 'general' }"
            @click="qrDestinationMode = 'general'"
          >
            Menu general
          </button>
          <button
            type="button"
            class="tab-btn"
            :class="{ active: qrDestinationMode === 'table' }"
            @click="qrDestinationMode = 'table'"
          >
            Mesa especifica
          </button>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <select v-if="qrDestinationMode === 'table'" v-model.number="selectedTableId" class="input max-w-[240px]">
            <option :value="0">Seleccionar mesa</option>
            <option v-for="table in tables" :key="`qr-select-${table.id}`" :value="table.id">
              {{ `Mesa ${String(table.id).padStart(2, '0')}` }}
            </option>
          </select>
          <input class="input max-w-[280px]" type="file" accept="image/*" @change="onQrLogoInput" />
          <AppButton variant="soft" @click="openQrPrintModal">Impresion masiva de QRs</AppButton>
          <AppButton variant="primary" @click="printCurrentQrCard">Imprimir cartel actual</AppButton>
        </div>
        <div v-if="qrTargetLink" class="mt-4 grid gap-3 md:grid-cols-[320px_1fr]">
          <div class="rounded-2xl bg-slate-50 p-3">
            <div class="relative mx-auto h-[260px] w-[260px] rounded-xl bg-white p-2">
              <img :src="tableQrPreviewUrl" alt="QR destino seleccionado" class="h-full w-full rounded-md object-contain" />
              <img
                v-if="qrLogoDataUrl"
                :src="qrLogoDataUrl"
                alt="Logo local"
                class="absolute left-1/2 top-1/2 h-12 w-12 -translate-x-1/2 -translate-y-1/2 rounded-lg border border-slate-200 bg-white p-1 object-contain"
              />
            </div>
          </div>
          <div class="rounded-2xl bg-slate-50 p-3">
            <p class="text-sm font-semibold text-slate-900">
              {{ qrDestinationMode === 'general' ? 'Menu general' : `Mesa ${String(selectedTableId).padStart(2, '0')}` }}
            </p>
            <p class="mt-1 text-xs text-slate-500 break-all">{{ qrTargetLink }}</p>
            <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">
              1. Escanea | 2. Elegi | 3. Disfruta
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <a
                class="tab-btn"
                :href="tableQrPreviewUrl"
                :download="qrDestinationMode === 'general' ? 'menu-general-qr.png' : `mesa-${selectedTableId}-qr.png`"
              >
                Descargar QR
              </a>
              <button type="button" class="tab-btn" @click="copyQrTargetLink">Copiar enlace</button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'loyalty'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Fidelizacion</h3>
        <p class="mt-1 text-sm text-slate-600">Modulo listo para la siguiente fase: puntos por compra, niveles de cliente y recompensas por frecuencia.</p>
      </article>
    </div>

    <div v-if="activeTab === 'billing'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Suscripcion y planes</h3>
            <p class="mt-1 text-xs text-slate-500">Compara planes, controla limites y gestiona upgrades.</p>
          </div>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
            Plan actual: {{ planNameMap[currentPlan] }}
          </span>
        </div>
        <div class="mt-4 grid gap-3 xl:grid-cols-3">
          <article class="rounded-[24px] border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-sky-50 p-5">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold text-slate-900">Takeaway</h4>
              <QrCode class="h-4 w-4 text-sky-600" />
            </div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">$110.000</p>
            <p class="text-xs text-slate-500">/ mes</p>
            <ul class="mt-3 space-y-1.5 text-xs text-slate-600">
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Pedidos y caja basica</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Menu QR y mesas</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Cupones basicos</li>
            </ul>
            <AppButton
              class="mt-4"
              :variant="currentPlan === 'takeaway' ? 'soft' : 'primary'"
              :disabled="currentPlan === 'takeaway'"
              @click="switchSubscriptionPlan('takeaway')"
            >
              {{ currentPlan === 'takeaway' ? 'Tu Plan Actual' : 'Cambiar a este Plan' }}
            </AppButton>
          </article>

          <article class="rounded-[24px] border-2 border-emerald-400 bg-gradient-to-br from-emerald-50 via-white to-emerald-100 p-5 shadow-[0_8px_28px_rgba(16,185,129,0.16)]">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold text-slate-900">Full Operativo</h4>
              <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white">MAS ELEGIDO</span>
            </div>
            <div class="mt-2 flex items-center gap-2">
              <p class="text-3xl font-extrabold text-slate-900">$160.000</p>
              <Bike class="h-5 w-5 text-emerald-700" />
            </div>
            <p class="text-xs text-slate-500">/ mes</p>
            <ul class="mt-3 space-y-1.5 text-xs text-slate-600">
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Todo Takeaway</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Insumos y costos</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-600" /> Equipo y roles</li>
            </ul>
            <AppButton
              class="mt-4"
              :variant="currentPlan === 'full' ? 'soft' : 'primary'"
              :disabled="currentPlan === 'full'"
              @click="switchSubscriptionPlan('full')"
            >
              {{ currentPlan === 'full' ? 'Tu Plan Actual' : 'Cambiar a este Plan' }}
            </AppButton>
          </article>

          <article class="rounded-[24px] border border-amber-300 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-5 text-white">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold">BI & Marketing</h4>
              <BarChart3 class="h-4 w-4 text-amber-300" />
            </div>
            <p class="mt-2 text-3xl font-extrabold">$230.000</p>
            <p class="text-xs text-slate-300">/ mes</p>
            <ul class="mt-3 space-y-1.5 text-xs text-slate-200">
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-300" /> Todo Full Operativo</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-300" /> Auditoria avanzada</li>
              <li class="flex items-center gap-1.5"><Check class="h-3.5 w-3.5 text-emerald-300" /> Fidelizacion y BI</li>
            </ul>
            <AppButton
              class="mt-4"
              :variant="currentPlan === 'bi' ? 'soft' : 'primary'"
              :disabled="currentPlan === 'bi'"
              @click="switchSubscriptionPlan('bi')"
            >
              {{ currentPlan === 'bi' ? 'Tu Plan Actual' : 'Contactar Soporte' }}
            </AppButton>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
          <div class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Estado de cuenta</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">Tu abono vence en {{ daysToRenew }} dias</p>
            <p class="mt-1 text-xs text-slate-500">Renovacion automatica para el plan {{ planNameMap[currentPlan] }}.</p>
          </div>
          <div class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Consumo de recursos</p>
            <div class="mt-3 space-y-3">
              <div v-for="row in usageRows" :key="row.key">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-semibold text-slate-700">{{ row.key }}</span>
                  <span class="text-slate-500">{{ row.used }} / {{ row.limit }}</span>
                </div>
                <div class="mt-1 h-2 rounded-full bg-slate-200">
                  <div class="h-2 rounded-full bg-emerald-500" :style="{ width: `${usagePercent(row.used, row.limit)}%` }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,0.8fr)]">
          <section class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Metodo de pago activo</p>
            <div class="mt-3 rounded-[22px] bg-gradient-to-br from-slate-950 via-slate-800 to-slate-700 p-4 text-white shadow-[0_8px_28px_rgba(15,23,42,0.24)]">
              <div class="flex items-start justify-between gap-2">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white">
                  <CreditCard class="h-5 w-5" />
                </span>
                <span
                  class="rounded-full px-2 py-1 text-[10px] font-bold tracking-wide"
                  :class="billingCardStatus === 'active' ? 'bg-emerald-500/25 text-emerald-100' : 'bg-amber-400/20 text-amber-100'"
                >
                  {{ billingCardStatusLabel }}
                </span>
              </div>
              <p class="mt-6 text-xl font-extrabold tracking-[0.18em]">**** {{ billingCard.last4 }}</p>
              <div class="mt-4 flex items-end justify-between">
                <div>
                  <p class="text-[10px] uppercase tracking-wide text-slate-300">Titular</p>
                  <p class="text-sm font-semibold">{{ billingCard.holder }}</p>
                </div>
                <div class="text-right">
                  <p class="text-[10px] uppercase tracking-wide text-slate-300">Expira</p>
                  <p class="text-sm font-semibold">{{ billingCard.expiresAt }}</p>
                </div>
                <p class="text-sm font-extrabold uppercase tracking-[0.12em] text-sky-200">{{ billingCard.brand }}</p>
              </div>
            </div>
            <AppButton class="mt-3" variant="soft" @click="openChangePaymentMethod">Cambiar metodo de pago</AppButton>
          </section>

          <section class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Procesador oficial</p>
            <div class="mt-3 rounded-2xl border border-sky-200 bg-sky-50 p-4">
              <div class="flex items-center gap-2">
                <span class="inline-flex rounded-xl bg-[#009EE3] px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide text-white">
                  Mercado Pago
                </span>
                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-sky-700">Seguro</span>
              </div>
              <p class="mt-3 text-sm font-semibold text-slate-900">Pagos procesados de forma segura por Mercado Pago</p>
              <p class="mt-1 text-xs text-slate-600">Tus pagos estan protegidos.</p>
              <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  class="rounded-full bg-[#009EE3] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#0089c5] active:scale-[0.98]"
                  @click="linkMercadoPagoAccount"
                >
                  Vincular cuenta
                </button>
                <button
                  type="button"
                  class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98]"
                  @click="payNowWithMercadoPago"
                >
                  Pagar ahora
                </button>
              </div>
            </div>
          </section>

          <aside class="rounded-[20px] bg-slate-900 p-4 text-white">
            <p class="text-xs uppercase tracking-wide text-slate-300">Proximo debito automatico</p>
            <p class="mt-3 text-3xl font-extrabold">${{ planAmountByPlan[currentPlan].toFixed(0) }}</p>
            <p class="mt-1 text-sm text-slate-200">{{ nextDebitDateLabel }}</p>
            <p class="mt-3 rounded-xl bg-white/10 px-3 py-2 text-xs text-slate-100">Plan {{ planNameMap[currentPlan] }} • Renovacion automatica activa</p>
          </aside>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between gap-2">
          <h4 class="text-sm font-semibold text-slate-900">Historial de facturas</h4>
          <p class="text-xs text-slate-500">Transparencia total de tus cobros</p>
        </div>
        <div class="mt-3 overflow-hidden rounded-[20px] border border-slate-200">
          <div class="grid grid-cols-[1.4fr_1fr_1fr_110px] bg-slate-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            <span>Periodo</span>
            <span>Monto</span>
            <span>Estado</span>
            <span class="text-right">Descarga</span>
          </div>
          <div
            v-for="invoice in billingInvoices"
            :key="invoice.id"
            class="grid grid-cols-[1.4fr_1fr_1fr_110px] items-center gap-2 border-t border-slate-100 px-4 py-4 transition hover:bg-slate-50"
          >
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ invoice.periodLabel }}</p>
              <p class="text-xs text-slate-500">Emitida: {{ invoiceIssueDate(invoice.issuedAt) }}</p>
            </div>
            <p class="text-sm font-semibold text-slate-900">${{ invoice.amount.toFixed(0) }}</p>
            <span class="w-max rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="invoiceStatusClass(invoice.status)">
              {{ invoiceStatusLabel(invoice.status) }}
            </span>
            <div class="flex justify-end">
              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-emerald-100 hover:text-emerald-700 active:scale-[0.98]"
                @click="downloadInvoicePdf(invoice)"
                aria-label="Descargar PDF"
              >
                <Download class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'roles'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900">Roles y permisos inteligentes</h3>
          <p v-if="permissionSaveMessage" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
            {{ permissionSaveMessage }}
          </p>
        </div>
        <form class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="submitRole">
          <input v-model="roleForm.name" class="input" type="text" required placeholder="role_name" />
          <input v-model="roleForm.label" class="input" type="text" required placeholder="Etiqueta" />
          <AppButton variant="primary" type="submit">Crear</AppButton>
        </form>
      </article>

      <article
        v-for="role in store.roles"
        :key="`role-editor-${role.id}`"
        class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
      >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <div>
            <p class="text-sm font-semibold text-slate-900">{{ role.label }}</p>
            <p class="text-xs text-slate-500">{{ role.name }}</p>
          </div>
          <button
            type="button"
            class="tab-btn"
            :class="{ active: activeRoleEditorId === role.id }"
            @click="activeRoleEditorId = role.id"
          >
            {{ activeRoleEditorId === role.id ? 'Editando' : 'Editar permisos' }}
          </button>
        </div>

        <div v-if="activeRoleEditorId === role.id" class="space-y-4">
          <section v-for="group in permissionCatalog" :key="`${role.id}-${group.category}`">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ group.category }}</p>
            <div class="grid gap-3 md:grid-cols-2">
              <article
                v-for="permission in group.items"
                :key="permission.key"
                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-900">{{ permission.title }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ permission.description }}</p>
                  </div>
                  <button
                    type="button"
                    class="toggle-btn"
                    :class="{ on: hasRolePermission(role.id, permission.key) }"
                    @click="toggleRolePermission(role.id, permission.key)"
                    :aria-pressed="hasRolePermission(role.id, permission.key)"
                  >
                    <span class="toggle-knob"></span>
                  </button>
                </div>
              </article>
            </div>
          </section>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'team'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Nuevo integrante</h3>
        <form class="mt-3 grid gap-2 md:grid-cols-2" @submit.prevent="submitUser">
          <input v-model="userForm.name" class="input" type="text" required placeholder="Nombre" />
          <input v-model="userForm.email" class="input" type="email" required placeholder="Email" />
          <input v-model="userForm.password" class="input" type="text" required placeholder="Password temporal" />
          <select v-model.number="userForm.roleId" class="input" required>
            <option :value="0">Seleccionar rol</option>
            <option v-for="role in assignableBusinessRoles" :key="role.id" :value="role.id">
              {{ String(role.name || '').toLowerCase() === 'cashier' ? 'Encargado' : role.label }}
            </option>
          </select>
          <AppButton variant="primary" type="submit" class="md:col-span-2">Crear Usuario</AppButton>
        </form>
      </article>

      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-sm font-semibold text-slate-900">Directorio del equipo</h3>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
          <article
            v-for="user in store.users"
            :key="`team-card-${user.id}`"
            class="rounded-2xl bg-slate-50 px-4 py-3"
          >
            <div class="flex items-center gap-3">
              <div class="relative">
                <div class="grid h-12 w-12 place-items-center rounded-full bg-slate-200 text-sm font-bold text-slate-700">
                  {{ user.name.slice(0, 1).toUpperCase() }}
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2" :class="teamStatusDotClass(user.active)"></span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ user.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ user.email || 'Sin email' }}</p>
              </div>
              <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="teamRoleBadgeClass(user.role)">
                {{ teamRoleLabel(user.role) }}
              </span>
              <button
                type="button"
                class="grid h-10 w-10 place-items-center rounded-full bg-slate-200 text-slate-700 transition hover:bg-slate-300 active:scale-95"
                title="Configurar permisos"
                @click="openRolePermissionsForUser(user.role)"
              >
                <Settings class="h-4 w-4" />
              </button>
            </div>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Matriz de roles y accesos</h3>
            <p class="mt-1 text-xs text-slate-500">Configura permisos de Ver, Editar y Borrar por area del negocio.</p>
          </div>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">RBAC activo</span>
        </div>

        <div class="mt-4 overflow-x-auto rounded-[24px] border border-slate-200">
          <table class="min-w-[1060px] w-full border-separate border-spacing-0">
            <thead>
              <tr class="bg-slate-100 text-left">
                <th rowspan="2" class="rounded-tl-[24px] px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Modulo</th>
                <th
                  v-for="role in rbacRoles"
                  :key="`role-head-${role.key}`"
                  :colspan="rbacActions.length"
                  class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  {{ role.label }}
                </th>
              </tr>
              <tr class="bg-slate-50">
                <template v-for="role in rbacRoles" :key="`role-actions-${role.key}`">
                  <th
                    v-for="action in rbacActions"
                    :key="`head-${role.key}-${action.key}`"
                    class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500"
                    :class="{ 'rounded-tr-[24px]': role.key === rbacRoles[rbacRoles.length - 1].key && action.key === rbacActions[rbacActions.length - 1].key }"
                  >
                    {{ action.label }}
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in rbacMatrixGroups" :key="`group-${group.area}`">
                <tr>
                  <td :colspan="1 + (rbacRoles.length * rbacActions.length)" class="bg-slate-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                    {{ group.area }}
                  </td>
                </tr>
                <tr
                  v-for="moduleItem in group.modules"
                  :key="`module-${moduleItem.key}`"
                  class="transition hover:bg-white hover:shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
                >
                  <td class="px-4 py-4 align-top">
                    <p class="text-sm font-semibold text-slate-900">{{ moduleItem.label }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ moduleItem.description }}</p>
                  </td>
                  <template v-for="role in rbacRoles" :key="`cell-${moduleItem.key}-${role.key}`">
                    <td
                      v-for="action in rbacActions"
                      :key="`toggle-${moduleItem.key}-${role.key}-${action.key}`"
                      class="px-2 py-4 text-center"
                    >
                      <button
                        type="button"
                        class="mx-auto grid h-8 w-8 place-items-center rounded-full border transition active:scale-95"
                        :class="
                          role.key === 'admin'
                            ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-300'
                            : rbacMatrixState[role.key][moduleItem.key][action.key]
                            ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                            : 'border-slate-200 bg-slate-100 text-slate-400 hover:border-emerald-200 hover:text-emerald-600'
                        "
                        :disabled="role.key === 'admin'"
                        @click="toggleRbacPermission(role.key, moduleItem.key, action.key)"
                        :aria-label="`Permiso ${action.label} en ${moduleItem.label} para ${role.label}`"
                        :aria-pressed="rbacMatrixState[role.key][moduleItem.key][action.key]"
                        :title="role.key === 'admin' ? 'Los permisos de Admin SaaS no se editan desde el negocio.' : ''"
                      >
                        <Check v-if="rbacMatrixState[role.key][moduleItem.key][action.key]" class="h-4 w-4" />
                        <X v-else class="h-3.5 w-3.5" />
                      </button>
                    </td>
                  </template>
                </tr>
              </template>
            </tbody>
          </table>
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

    <div v-if="activeTab === 'health'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Calidad y operacion</h3>
            <p class="mt-1 text-xs text-slate-500">Estado tecnico del sistema y salud de cobros en tiempo real.</p>
          </div>
          <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="store.realtimeConnected ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
            {{ store.realtimeConnected ? 'Canal en linea' : 'Canal desconectado' }}
          </span>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-3">
          <article class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Latencia del servidor</p>
            <p class="mt-2 text-3xl font-extrabold" :class="metricToneClass(latencyStatus)">{{ serverLatencyMs }} ms</p>
            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="metricPillClass(latencyStatus)">
              {{ latencyStatus === 'ok' ? 'Optima' : latencyStatus === 'warn' ? 'En observacion' : 'Critica' }}
            </span>
          </article>

          <article class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Uptime ultimos 7 dias</p>
            <p class="mt-2 text-3xl font-extrabold" :class="metricToneClass(uptimeStatus)">{{ uptimePercent7d.toFixed(2) }}%</p>
            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="metricPillClass(uptimeStatus)">
              {{ uptimeStatus === 'ok' ? 'Estable' : uptimeStatus === 'warn' ? 'Atencion' : 'Degradado' }}
            </span>
          </article>

          <article class="rounded-[20px] bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Errores reportados</p>
            <p class="mt-2 text-3xl font-extrabold" :class="metricToneClass(errorStatus)">{{ reportedErrors24h }}</p>
            <p class="mt-1 text-xs text-slate-500">Incluye incidencias de pago y logs de error en 24h.</p>
            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="metricPillClass(errorStatus)">
              {{ errorStatus === 'ok' ? 'Sin impacto' : errorStatus === 'warn' ? 'Moderado' : 'Alto impacto' }}
            </span>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h4 class="text-sm font-semibold text-slate-900">Incidentes y observabilidad</h4>
        <div class="mt-3 space-y-2">
          <div
            v-for="incident in incidentsFeed"
            :key="incident.id"
            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ incident.title }}</p>
              <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="metricPillClass(incident.severity)">
                {{ incident.when }}
              </span>
            </div>
            <p class="mt-1 text-xs text-slate-600">{{ incident.detail }}</p>
          </div>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'help'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Centro de transparencia</h3>
            <p class="mt-1 text-xs text-slate-500">Resumen legal en lenguaje humano y control de datos.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]"
              @click="exportMyInformation('json')"
            >
              <Download class="h-4 w-4" />
              Exportar mi informacion
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 active:scale-[0.98]"
              @click="exportMyInformation('csv')"
            >
              <Download class="h-4 w-4" />
              Exportar CSV
            </button>
          </div>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
          <article
            v-for="item in legalHighlights"
            :key="item.key"
            class="rounded-2xl bg-slate-50 p-4"
            :class="{ 'ring-1 ring-emerald-300': helpLegalFocus === 'privacy' && item.key === 'privacy-owner' || helpLegalFocus === 'terms' && item.key === 'cancel-anytime' || helpLegalFocus === 'sla' && item.key === 'service-uptime' }"
          >
            <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ item.description }}</p>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h4 class="text-sm font-semibold text-slate-900">Base de conocimientos</h4>
        <p class="mt-1 text-xs text-slate-500">Preguntas rapidas para operacion diaria.</p>
        <div class="mt-3 space-y-2">
          <article
            v-for="faq in faqEntries"
            :key="`faq-${faq.id}`"
            class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2"
          >
            <button type="button" class="flex w-full items-center justify-between gap-2 text-left" @click="toggleHelpFaq(faq.id)">
              <span class="text-sm font-semibold text-slate-900">{{ faq.question }}</span>
              <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600">{{ helpFaqOpenId === faq.id ? '-' : '+' }}</span>
            </button>
            <p v-if="helpFaqOpenId === faq.id" class="mt-2 text-xs text-slate-600">{{ faq.answer }}</p>
          </article>
        </div>
      </article>

      <article class="rounded-[24px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h4 class="text-sm font-semibold text-slate-900">Video tutoriales express</h4>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="video in helpVideoCards"
            :key="video.id"
            class="group rounded-2xl bg-slate-50 p-3 shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition hover:-translate-y-0.5"
          >
            <div class="relative grid h-28 place-items-center rounded-xl bg-gradient-to-br from-slate-200 to-slate-100">
              <span class="grid h-10 w-10 place-items-center rounded-full bg-white/90 text-slate-700 shadow-sm">
                <Play class="h-4 w-4" />
              </span>
              <span class="absolute right-2 top-2 rounded-full bg-slate-900/80 px-2 py-0.5 text-[10px] font-semibold text-white">{{ video.duration }}</span>
            </div>
            <p class="mt-2 text-sm font-semibold text-slate-900">{{ video.title }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ video.description }}</p>
          </article>
        </div>
      </article>
    </div>

    <div v-if="activeTab === 'audit'" class="space-y-4">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Timeline de auditoria</h3>
            <p class="mt-1 text-xs text-slate-500">Registro de seguridad y actividad operativa con responsables.</p>
          </div>
          <p class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ filteredAuditLogs.length }} eventos</p>
        </div>

        <div class="mt-4 space-y-3 rounded-2xl bg-slate-50 p-3">
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'all' }" @click="auditFilterChip = 'all'">Todos</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'cancelations' }" @click="auditFilterChip = 'cancelations'">Solo cancelaciones</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'price_changes' }" @click="auditFilterChip = 'price_changes'">Solo cambios de precio</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'security' }" @click="auditFilterChip = 'security'">Seguridad</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'created' }" @click="auditFilterChip = 'created'">Creaciones</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'edited' }" @click="auditFilterChip = 'edited'">Ediciones</button>
            <button type="button" class="tab-btn" :class="{ active: auditFilterChip === 'deleted' }" @click="auditFilterChip = 'deleted'">Eliminaciones</button>
          </div>
          <div class="grid gap-2 lg:grid-cols-[1.3fr_1fr_180px_180px]">
            <input v-model="auditSearchTerm" class="input" type="text" placeholder="Buscar por accion, usuario o modulo..." />
            <select v-model="auditUserFilter" class="input">
              <option value="all">Por usuario (todos)</option>
              <option v-for="name in auditUsers" :key="`audit-user-filter-${name}`" :value="name">{{ name }}</option>
            </select>
            <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs text-slate-600">
              <CalendarDays class="h-4 w-4" />
              <input v-model="auditDateFrom" type="date" class="w-full bg-transparent outline-none" />
            </label>
            <label class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs text-slate-600">
              <CalendarDays class="h-4 w-4" />
              <input v-model="auditDateTo" type="date" class="w-full bg-transparent outline-none" />
            </label>
          </div>
        </div>

        <div class="mt-4 space-y-4">
          <div v-if="!filteredAuditLogs.length" class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">
            No hay eventos para los filtros seleccionados.
          </div>
          <div
            v-for="log in filteredAuditLogs"
            :key="`audit-log-${log.id}`"
            class="audit-item relative flex gap-3 rounded-2xl bg-slate-50 px-3 py-3"
          >
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="auditIconClass(log.action)">
              <component :is="auditIconComponent(log.action)" class="h-4 w-4" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm text-slate-900">
                <span class="font-semibold text-slate-500">{{ auditTimeLabel(log.createdAt) }}</span>
                -
                <span class="rounded-full bg-slate-900 px-2 py-0.5 text-xs font-semibold text-white">@{{ log.userName }}</span>
                realizo
                <span class="font-semibold">{{ auditActionLabel(log.action) }}</span>
                en
                <span class="font-semibold">{{ auditSectionLabel(log.entityType) }}</span>
                <span v-if="log.entityId">#{{ log.entityId }}</span>
              </p>
              <p class="mt-1 text-xs text-slate-500">{{ auditRelativeTime(log.createdAt) }} | {{ new Date(log.createdAt).toLocaleString('es-AR') }}</p>

              <div v-if="auditPriceDiff(log)" class="mt-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                <p class="font-semibold text-slate-700">Detalle del cambio de precio</p>
                <p class="mt-1">
                  Precio anterior:
                  <span class="font-semibold text-rose-700 line-through">{{ moneyLabel(auditPriceDiff(log)!.before) }}</span>
                  <span class="mx-1 text-slate-400">-></span>
                  Precio nuevo:
                  <span class="font-semibold text-emerald-700">{{ moneyLabel(auditPriceDiff(log)!.after) }}</span>
                </p>
              </div>

              <div v-else-if="auditStockDiff(log)" class="mt-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                <p class="font-semibold text-slate-700">Detalle del cambio de stock</p>
                <p class="mt-1">
                  Stock anterior:
                  <span class="font-semibold text-rose-700 line-through">{{ auditStockDiff(log)!.before }}</span>
                  <span class="mx-1 text-slate-400">-></span>
                  Stock nuevo:
                  <span class="font-semibold text-emerald-700">{{ auditStockDiff(log)!.after }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <footer class="rounded-2xl bg-white px-4 py-3 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
      <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
        <p class="text-slate-500">Dunamis Pro • Centro Legal y Soporte</p>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 hover:bg-slate-200" @click="openLegalTopic('terms')">Terminos de uso</button>
          <button type="button" class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 hover:bg-slate-200" @click="openLegalTopic('privacy')">Politica de privacidad</button>
          <button type="button" class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 hover:bg-slate-200" @click="openLegalTopic('sla')">SLA de servicio</button>
        </div>
      </div>
    </footer>

    <div class="fixed bottom-5 right-5 z-[65]">
      <button
        type="button"
        class="group inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-3 text-xs font-semibold text-white shadow-[0_12px_30px_rgba(15,23,42,0.28)] transition hover:bg-slate-800 active:scale-[0.98]"
        @click="supportModalOpen = true"
      >
        <LifeBuoy class="h-4 w-4" />
        <span class="hidden sm:inline">Soporte en linea • 15 min</span>
      </button>
    </div>

    <AppModal :open="dailyMenuModalOpen" max-width-class="max-w-2xl" :scrollable="true" @close="resetDailyMenuForm">
      <div class="w-full max-w-2xl p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">{{ isEditingDailyMenu ? 'Editar menu diario' : 'Nuevo menu diario' }}</h3>
          <AppButton variant="ghost" @click="resetDailyMenuForm">Cerrar</AppButton>
        </div>
        <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="submitDailyMenu">
          <input v-model="dailyMenuForm.name" class="input" type="text" required placeholder="Nombre del menu" />
          <select v-model="dailyMenuForm.slot" class="input">
            <option value="all_day">Todo el dia</option>
            <option value="lunch">Almuerzo</option>
            <option value="dinner">Cena</option>
          </select>
          <input v-model="dailyMenuForm.description" class="input md:col-span-2" type="text" placeholder="Descripcion" />
          <input v-model="dailyMenuForm.imageUrl" class="input md:col-span-2" type="url" placeholder="Imagen URL" />
          <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
            <input v-model="dailyMenuForm.isActive" type="checkbox" />
            Menu activo
          </label>
          <input v-model.number="dailyMenuForm.priority" class="input" type="number" min="0" placeholder="Prioridad" />
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Activo desde
            <input v-model="dailyMenuForm.activeFrom" class="input mt-1" type="datetime-local" />
          </label>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Activo hasta
            <input v-model="dailyMenuForm.activeTo" class="input mt-1" type="datetime-local" />
          </label>

          <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dias de aplicacion</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="day in weekdayOptions"
                :key="`weekday-${day.value}`"
                type="button"
                class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                :class="dailyMenuForm.weekdays.includes(day.value) ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600'"
                @click="toggleDailyMenuWeekday(day.value)"
              >
                {{ day.label }}
              </button>
            </div>
            <p class="mt-2 text-[11px] text-slate-500">Si no seleccionas dias, aplica todos.</p>
          </div>

          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="resetDailyMenuForm">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">{{ isEditingDailyMenu ? 'Guardar cambios' : 'Crear menu' }}</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="dailyMenuItemModalOpen" max-width-class="max-w-xl" @close="dailyMenuItemModalOpen = false">
      <div class="w-full max-w-xl p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Agregar item al menu diario</h3>
          <AppButton variant="ghost" @click="dailyMenuItemModalOpen = false">Cerrar</AppButton>
        </div>
        <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="submitDailyMenuItem">
          <select v-model="dailyMenuItemForm.itemType" class="input">
            <option value="product">Producto</option>
            <option value="combo">Combo</option>
          </select>
          <select v-model.number="dailyMenuItemForm.itemId" class="input">
            <option :value="0">Seleccionar item</option>
            <option v-for="item in selectedDailyItemOptions" :key="`daily-item-${item.id}`" :value="item.id">{{ item.name }}</option>
          </select>
          <input v-model="dailyMenuItemForm.discountPercent" class="input" type="number" min="0" max="100" step="1" placeholder="Descuento (%)" />
          <input v-model.number="dailyMenuItemForm.sortOrder" class="input" type="number" min="0" placeholder="Orden" />
          <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
            Base: ${{ selectedDailyItemBasePrice.toFixed(2) }} |
            Promo: {{ computedDailyPromoPrice === null ? 'sin descuento' : `$${computedDailyPromoPrice.toFixed(2)}` }}
          </div>
          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="dailyMenuItemModalOpen = false">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">Guardar item</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="cashMovementModalOpen" max-width-class="max-w-lg" @close="cashMovementModalOpen = false">
      <div class="w-full max-w-lg p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Nuevo movimiento de caja</h3>
          <AppButton variant="ghost" @click="cashMovementModalOpen = false">Cerrar</AppButton>
        </div>
        <form class="mt-4 grid gap-3" @submit.prevent="submitCashMovement">
          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Tipo de movimiento
            <select v-model="cashMovementDraft.type" class="input mt-1">
              <option value="income">Ingreso</option>
              <option value="expense">Egreso</option>
            </select>
          </label>
          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Concepto
            <input v-model="cashMovementDraft.concept" class="input mt-1" type="text" placeholder="Ej: Retiro para proveedores" />
          </label>
          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
            Monto
            <input v-model.number="cashMovementDraft.amount" class="input mt-1" type="number" min="0" step="0.01" placeholder="0.00" />
          </label>
          <div class="flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="cashMovementModalOpen = false">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">Registrar</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="couponModalOpen" max-width-class="max-w-xl" @close="couponModalOpen = false">
      <div class="w-full max-w-xl p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Crear cupon</h3>
          <AppButton variant="ghost" @click="couponModalOpen = false">Cerrar</AppButton>
        </div>
        <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="submitCoupon">
          <input v-model="couponForm.code" class="input" type="text" placeholder="Codigo (ej: SUMMER2026)" required />
          <select v-model="couponForm.kind" class="input">
            <option value="percentage">Porcentaje (%)</option>
            <option value="fixed">Monto fijo ($)</option>
            <option value="free_shipping">Envio gratis</option>
          </select>
          <input
            v-model.number="couponForm.value"
            class="input"
            type="number"
            min="0"
            step="0.01"
            :disabled="couponForm.kind === 'free_shipping'"
            :placeholder="couponForm.kind === 'free_shipping' ? 'No aplica para envio gratis' : 'Valor del descuento'"
          />
          <input v-model.number="couponForm.minOrder" class="input" type="number" min="0" step="0.01" placeholder="Monto minimo de compra" />
          <input v-model.number="couponForm.totalUsesLimit" class="input" type="number" min="1" placeholder="Limite total de usos" />
          <input v-model.number="couponForm.usesPerClient" class="input" type="number" min="1" placeholder="Usos por cliente" />
          <label class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            Fecha de expiracion
            <input v-model="couponForm.expiresAt" class="input mt-1" type="date" required />
          </label>
          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="couponModalOpen = false">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">Guardar cupon</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="qrPrintModalOpen" max-width-class="max-w-2xl" @close="qrPrintModalOpen = false">
      <div class="w-full max-w-2xl p-1">
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Impresion masiva de QRs</h3>
            <p class="text-xs text-slate-500">Selecciona mesas para imprimir codigos listos para salon.</p>
          </div>
          <AppButton variant="ghost" @click="qrPrintModalOpen = false">Cerrar</AppButton>
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <label
            v-for="table in tables"
            :key="`print-table-${table.id}`"
            class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700"
          >
            <span>{{ `Mesa ${String(table.id).padStart(2, '0')}` }}</span>
            <input v-model="selectedPrintTableIds" :value="table.id" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600" />
          </label>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <AppButton variant="ghost" type="button" @click="qrPrintModalOpen = false">Cancelar</AppButton>
          <AppButton variant="primary" type="button" @click="printSelectedTableQrs">Imprimir seleccion</AppButton>
        </div>
      </div>
    </AppModal>

    <AppModal :open="supportModalOpen" max-width-class="max-w-xl" :scrollable="true" @close="supportModalOpen = false">
      <div class="w-full max-w-xl p-1">
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Centro de Ayuda</h3>
            <p class="text-xs text-emerald-700">Soporte en linea - Tiempo de respuesta estimado: 15 min</p>
          </div>
          <AppButton variant="ghost" @click="supportModalOpen = false">Cerrar</AppButton>
        </div>
        <p class="mt-2 text-sm text-slate-600">Reporta un problema y enviaremos automaticamente el contexto tecnico del sistema.</p>
        <textarea
          v-model="supportIssueDraft"
          class="input mt-3 min-h-[110px]"
          placeholder="Describe el problema (ej: cobro rechazado en caja, pantalla lenta, corte de sincronizacion)."
        ></textarea>
        <div class="mt-3 rounded-2xl bg-slate-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnostico tecnico adjunto</p>
          <ul class="mt-2 space-y-1 text-xs text-slate-600">
            <li>Navegador: {{ browserSnapshot.userAgent }}</li>
            <li>Idioma: {{ browserSnapshot.language }}</li>
            <li>Plataforma: {{ browserSnapshot.platform }}</li>
            <li>Version app: {{ browserSnapshot.appVersion }}</li>
            <li>Ruta actual: {{ browserSnapshot.route }}</li>
            <li>Realtime: {{ browserSnapshot.realtime }}</li>
          </ul>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <AppButton variant="ghost" @click="supportModalOpen = false">Cancelar</AppButton>
          <AppButton variant="primary" @click="submitSupportIssue">Enviar reporte</AppButton>
        </div>
      </div>
    </AppModal>

    <AppModal :open="upgradeModalOpen" max-width-class="max-w-lg" @close="upgradeModalOpen = false">
      <div class="w-full max-w-lg p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Potencia tu negocio con el plan {{ planNameMap[upgradeRequiredPlan] }}</h3>
          <AppButton variant="ghost" @click="upgradeModalOpen = false">Cerrar</AppButton>
        </div>
        <div class="mt-3 rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-sky-50 p-4">
          <div class="flex items-start gap-3">
            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-[0_4px_20px_rgba(0,0,0,0.06)]">
              <Bike class="h-5 w-5" />
            </span>
            <div>
              <p class="text-sm text-slate-700">
                La funcion
                <span class="font-semibold">{{ upgradeContextLabel || 'seleccionada' }}</span>
                esta incluida desde
                <span class="font-semibold">{{ planNameMap[upgradeRequiredPlan] }}</span>.
              </p>
              <ul class="mt-3 space-y-1.5 text-xs text-slate-600">
                <li class="flex items-center gap-1.5">
                  <Check class="h-3.5 w-3.5 text-emerald-600" />
                  Gestion de repartidores propia
                </li>
                <li class="flex items-center gap-1.5">
                  <Check class="h-3.5 w-3.5 text-emerald-600" />
                  App de cocina tactil
                </li>
                <li class="flex items-center gap-1.5">
                  <Check class="h-3.5 w-3.5 text-emerald-600" />
                  Pagos por Mercado Pago
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <AppButton variant="ghost" @click="upgradeModalOpen = false">Ahora no</AppButton>
          <AppButton
            variant="primary"
            @click="upgradeModalOpen = false; goToTab('billing')"
          >
            {{ upgradePrimaryCta }}
          </AppButton>
        </div>
      </div>
    </AppModal>

    <AppModal :open="orderEditorOpen" max-width-class="max-w-2xl" :scrollable="true" @close="closeOrderEditor">
      <div class="w-full max-w-2xl p-1">
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
    </AppModal>

    <AppModal :open="productModalOpen" max-width-class="max-w-3xl" :scrollable="true" @close="resetProductForm">
      <div class="w-full max-w-3xl p-1">
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-slate-900">{{ isEditingProduct ? `Editar producto #${editingProductId}` : 'Nuevo producto' }}</h3>
            <p class="text-xs text-slate-500">Gestion rapida de producto desde modal.</p>
          </div>
          <AppButton variant="ghost" @click="resetProductForm">Cerrar</AppButton>
        </div>
        <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="submitProduct">
          <input v-model="productForm.name" class="input" type="text" required placeholder="Nombre del producto" />
          <input v-model.number="productForm.price" class="input" type="number" min="1" required placeholder="Precio de venta" />
          <select v-model="productForm.category" class="input">
            <option value="">Sin categoria</option>
            <option v-for="category in productCategories.filter((category) => category !== 'all')" :key="`modal-cat-${category}`" :value="category">
              {{ category }}
            </option>
          </select>
          <input v-model.number="productForm.prepMin" class="input" type="number" min="1" required placeholder="Preparacion (min)" />
          <input v-model.number="productForm.stockQuantity" class="input" type="number" min="0" placeholder="Stock disponible" />
          <input v-model.number="productForm.minStockQuantity" class="input" type="number" min="0" placeholder="Minimo de alerta" />
          <p class="md:col-span-2 text-xs text-slate-500">
            Tip: carga una imagen cuadrada (1:1) y define stock minimo para alertas.
          </p>
          <div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Imagen</p>
            <input class="mt-2 w-full text-sm text-slate-600" type="file" accept="image/*" @change="handleImageInput" />
            <img v-if="productImagePreview" :src="productImagePreview" alt="Preview" class="mt-3 h-24 rounded-xl border border-slate-200 object-cover" />
          </div>
          <div v-if="isEditingProduct && selectedRecipeProductId" class="md:col-span-2">
            <ProfitabilityCalculator
              :sale-price="productForm.price"
              :ingredients="profitabilityIngredients"
              :lines="selectedRecipeLines"
              @update:sale-price="updateSalePriceFromCalculator"
              @add-line="addRecipeLineFromCalculator"
              @remove-line="removeRecipeLineFromCalculator"
            />
          </div>
          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="resetProductForm">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">{{ isEditingProduct ? 'Guardar cambios' : 'Crear producto' }}</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="categoryModalOpen" max-width-class="max-w-md" @close="categoryModalOpen = false">
      <div class="w-full max-w-md p-1">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Crear categoria</h3>
          <AppButton variant="ghost" @click="categoryModalOpen = false">Cerrar</AppButton>
        </div>
        <div class="mt-3 space-y-2">
          <input v-model="categoryDraft" class="input" type="text" placeholder="Ej: Hamburguesas, Postres, Bebidas" />
          <p class="text-xs text-slate-500">Usa nombres cortos y unicos para mejorar filtros y reportes.</p>
          <div class="flex justify-end">
            <AppButton variant="primary" @click="createCategory">Guardar categoria</AppButton>
          </div>
        </div>
      </div>
    </AppModal>

    <AppModal :open="comboModalOpen" max-width-class="max-w-3xl" :scrollable="true" @close="resetComboForm">
      <div class="mx-auto w-full max-w-3xl p-1">
        <div class="mb-3 flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">{{ isEditingCombo ? 'Editar combo' : 'Crear combo' }}</h3>
          <AppButton variant="ghost" @click="resetComboForm">Cerrar</AppButton>
        </div>
        <form class="grid gap-2 md:grid-cols-2" @submit.prevent="submitCombo">
          <input v-model="comboForm.name" class="input" type="text" required placeholder="Nombre de la oferta" />
          <input v-model.number="comboForm.basePrice" class="input" type="number" min="0" required placeholder="Precio final del combo" />
          <input v-model="comboForm.description" class="input md:col-span-2" type="text" placeholder="Descripcion" />
          <input v-model="comboForm.imageUrl" class="input md:col-span-2" type="url" placeholder="Imagen URL (fallback)" />
          <p class="md:col-span-2 text-xs text-slate-500">Incluye al menos 2 productos para una oferta atractiva.</p>
          <div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Imagen del combo (upload)</p>
            <input class="mt-2 w-full text-sm text-slate-600" type="file" accept="image/*" @change="handleComboImageInput" />
            <img v-if="comboImagePreview" :src="comboImagePreview" alt="Preview combo" class="mt-3 h-24 rounded-xl border border-slate-200 object-cover" />
          </div>
          <div class="md:col-span-2 rounded-xl border border-slate-200 p-3">
            <div class="grid gap-2 sm:grid-cols-[1fr_120px_auto]">
              <select v-model="comboCategoryFilter" class="input sm:col-span-3">
                <option v-for="category in productCategories" :key="`combo-modal-cat-${category}`" :value="category">
                  {{ category === 'all' ? 'Todas las categorias' : category }}
                </option>
              </select>
              <select v-model.number="comboProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in comboSelectableProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
              </select>
              <input v-model.number="comboQuantity" class="input" type="number" min="1" />
              <AppButton variant="soft" @click="addComboItem">Agregar Item</AppButton>
            </div>
            <div class="mt-3 space-y-2">
              <div v-for="item in comboItems" :key="item.productId" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <span>{{ store.getProduct(item.productId)?.name || 'Producto' }} x{{ item.quantity }}</span>
                <AppButton variant="ghost" @click="removeComboItem(item.productId)">Quitar</AppButton>
              </div>
            </div>
          </div>
          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="resetComboForm">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">{{ isEditingCombo ? 'Guardar cambios' : 'Guardar Combo' }}</AppButton>
          </div>
        </form>
      </div>
    </AppModal>

    <AppModal :open="bundleModalOpen" max-width-class="max-w-3xl" :scrollable="true" @close="bundleModalOpen = false">
      <div class="mx-auto w-full max-w-3xl p-1">
        <div class="mb-3 flex items-center justify-between gap-2">
          <h3 class="text-base font-semibold text-slate-900">Crear bundle</h3>
          <AppButton variant="ghost" @click="bundleModalOpen = false">Cerrar</AppButton>
        </div>
        <form class="grid gap-2 md:grid-cols-2" @submit.prevent="submitBundle">
          <input v-model="bundleForm.name" class="input" type="text" required placeholder="Nombre bundle" />
          <select v-model="bundleForm.pricingMode" class="input">
            <option value="fixed_price">Precio fijo</option>
            <option value="discount_percentage">Descuento porcentual</option>
          </select>
          <input v-model="bundleForm.description" class="input md:col-span-2" type="text" placeholder="Descripcion" />
          <p class="md:col-span-2 text-xs text-slate-500">
            Usa precio fijo para campañas simples o porcentaje para promociones variables.
          </p>
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
              <select v-model="bundleCategoryFilter" class="input sm:col-span-3">
                <option v-for="category in productCategories" :key="`bundle-modal-cat-${category}`" :value="category">
                  {{ category === 'all' ? 'Todas las categorias' : category }}
                </option>
              </select>
              <select v-model.number="bundleProductId" class="input">
                <option :value="0">Seleccionar producto</option>
                <option v-for="product in bundleSelectableProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
              </select>
              <input v-model.number="bundleQuantity" class="input" type="number" min="1" />
              <AppButton variant="soft" @click="addBundleItem">Agregar Item</AppButton>
            </div>
            <div class="mt-3 space-y-2">
              <div v-for="item in bundleItems" :key="item.productId" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <span>{{ store.getProduct(item.productId)?.name || 'Producto' }} x{{ item.quantity }}</span>
                <AppButton variant="ghost" @click="removeBundleItem(item.productId)">Quitar</AppButton>
              </div>
            </div>
          </div>
          <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            Ahorro estimado del cliente: {{ bundleEstimatedSaving.toFixed(2) }}
          </div>
          <div class="md:col-span-2 flex justify-end gap-2">
            <AppButton variant="ghost" type="button" @click="bundleModalOpen = false">Cancelar</AppButton>
            <AppButton variant="primary" type="submit">Guardar Bundle</AppButton>
          </div>
        </form>
      </div>
    </AppModal>
      </div>
    </div>
  </section>
</template>

<style scoped>
.sidebar-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  border-radius: 0.9rem;
  padding: 0.58rem 0.7rem 0.58rem 0.95rem;
  color: #374151;
  font-weight: 500;
  transition: all 0.3s ease;
}

.sidebar-item + .sidebar-item {
  margin-top: 8px;
}

.sidebar-item:hover {
  background: rgb(241 245 249);
  color: rgb(17 24 39);
}

.sidebar-item.active {
  background: rgb(16 185 129 / 0.1);
  color: rgb(5 150 105);
}

.sidebar-item.active::before {
  content: '';
  position: absolute;
  left: 0.25rem;
  top: 0.45rem;
  bottom: 0.45rem;
  width: 3px;
  border-radius: 9999px;
  background: rgb(16 185 129);
}

.sidebar-item.collapsed {
  justify-content: center;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.sidebar-item.collapsed:hover::after,
.sidebar-logout-btn.collapsed:hover::after {
  content: attr(data-tooltip);
  position: absolute;
  left: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
  white-space: nowrap;
  border-radius: 0.55rem;
  background: rgb(15 23 42);
  color: white;
  padding: 0.35rem 0.55rem;
  font-size: 11px;
  font-weight: 600;
  box-shadow: 0 8px 24px rgb(15 23 42 / 0.2);
  z-index: 20;
}

.sidebar-icon-btn {
  display: grid;
  height: 2rem;
  width: 2rem;
  place-items: center;
  border-radius: 9999px;
  background: rgb(241 245 249);
  color: rgb(71 85 105);
  transition: all 180ms ease;
}

.sidebar-icon-btn:hover {
  background: rgb(226 232 240);
  color: rgb(15 23 42);
}

.sidebar-logout-btn {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  justify-content: center;
  border-radius: 0.9rem;
  padding: 0.6rem 0.8rem;
  color: rgb(148 163 184);
  transition: all 0.3s ease;
}

.sidebar-logout-btn:hover {
  background: rgb(254 242 242);
  color: rgb(190 24 93);
}

.sidebar-logout-btn.collapsed {
  width: 100%;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.cash-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 0.2rem;
  border-bottom: 1px solid rgb(241 245 249);
  transition: background-color 180ms ease;
}

.cash-row:hover {
  background: rgb(248 250 252);
}

.cash-row:disabled {
  cursor: default;
}

.toggle-btn {
  position: relative;
  display: inline-flex;
  height: 1.55rem;
  width: 2.7rem;
  align-items: center;
  border-radius: 9999px;
  background: rgb(203 213 225);
  transition: all 0.25s ease;
  padding: 0.2rem;
}

.toggle-btn.on {
  background: rgb(14 165 233);
}

.toggle-knob {
  height: 1.15rem;
  width: 1.15rem;
  border-radius: 9999px;
  background: white;
  box-shadow: 0 1px 2px rgb(0 0 0 / 0.18);
  transition: transform 0.25s ease;
}

.toggle-btn.on .toggle-knob {
  transform: translateX(1.1rem);
}

.audit-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 1.42rem;
  top: calc(100% + 2px);
  height: 14px;
  width: 1px;
  background: rgb(203 213 225);
}

.coupon-ticket {
  position: relative;
  border-width: 1px;
  border-style: solid;
  border-right-width: 2px;
  border-right-style: dashed;
}

.coupon-ticket::before,
.coupon-ticket::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 14px;
  height: 14px;
  border-radius: 9999px;
  background: rgb(248 249 250);
  transform: translateY(-50%);
}

.table-tile {
  box-shadow: 0 4px 20px rgb(0 0 0 / 0.03);
}

.table-tile:hover {
  background: white;
  box-shadow: 0 8px 24px rgb(15 23 42 / 0.08);
}

.coupon-ticket::before {
  right: -8px;
  top: 22%;
}

.coupon-ticket::after {
  right: -8px;
  top: 78%;
}

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

.sidebar-fade-enter-active,
.sidebar-fade-leave-active {
  transition: opacity 180ms ease;
}

.sidebar-fade-enter-from,
.sidebar-fade-leave-to {
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.kds-critical {
  animation: kdsCriticalPulse 1.4s ease-in-out infinite;
}

.kds-critical-hard {
  animation: kdsCriticalHardPulse 0.95s ease-in-out infinite;
}

.heartbeat-dot-pulse {
  animation: heartbeatDotPulse 1.25s ease-in-out infinite;
}

.onboarding-step-pulse {
  animation: onboardingPulse 1.1s ease-in-out infinite;
}

.confetti-layer {
  pointer-events: none;
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.confetti-dot {
  position: absolute;
  top: -16px;
  width: 8px;
  height: 14px;
  border-radius: 3px;
  background: linear-gradient(180deg, rgb(16 185 129), rgb(34 197 94));
  animation: confettiFall 1.8s linear infinite;
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

@keyframes heartbeatDotPulse {
  0%,
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgb(16 185 129 / 0.32);
  }
  50% {
    transform: scale(1.08);
    box-shadow: 0 0 0 5px rgb(16 185 129 / 0.08);
  }
}

@keyframes onboardingPulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.72;
    transform: scale(1.05);
  }
}

@keyframes confettiFall {
  0% {
    transform: translateY(-14px) rotate(0deg);
    opacity: 0;
  }
  15% {
    opacity: 1;
  }
  100% {
    transform: translateY(260px) rotate(210deg);
    opacity: 0;
  }
}
</style>
