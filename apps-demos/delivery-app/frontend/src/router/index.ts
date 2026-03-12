import { createRouter, createWebHistory } from 'vue-router'
import AdminView from '../views/AdminView.vue'
import EmployeeView from '../views/EmployeeView.vue'
import DriverView from '../views/DriverView.vue'
import EmployeeHistoryView from '../views/EmployeeHistoryView.vue'
import EmployeeProfileView from '../views/EmployeeProfileView.vue'
import DriverHistoryView from '../views/DriverHistoryView.vue'
import DriverProfileView from '../views/DriverProfileView.vue'
import ClientView from '../views/ClientView.vue'
import ClientOrdersView from '../views/ClientOrdersView.vue'
import ClientProfileView from '../views/ClientProfileView.vue'
import ForbiddenView from '../views/ForbiddenView.vue'
import LoginView from '../views/LoginView.vue'
import LandingSellView from '../views/LandingSellView.vue'
import SuperAdminView from '../views/SuperAdminView.vue'

type UserRole = 'superadmin' | 'admin' | 'employee' | 'driver' | 'client'
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

interface StoredUser {
  user: {
    role: UserRole
  }
}

const AUTH_KEY = 'delivery-vue-auth-v2'
const RBAC_MATRIX_STORAGE_KEY = 'delivery-admin-rbac-matrix-v1'

const loadUser = (): StoredUser | null => {
  const raw = localStorage.getItem(AUTH_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as StoredUser
  } catch {
    localStorage.removeItem(AUTH_KEY)
    return null
  }
}

const routeByRole: Record<UserRole, string> = {
  superadmin: '/superadmin/home',
  admin: '/admin/home',
  employee: '/empleado/panel',
  driver: '/repartidor/ruta',
  client: '/cliente/tienda',
}

const routePermissionByPath: Record<string, RbacModuleKey> = {
  '/admin/home': 'dashboard',
  '/admin/orders': 'orders',
  '/admin/products': 'catalog',
  '/admin/inventory': 'inventory',
  '/admin/categories': 'catalog',
  '/admin/daily-menus': 'catalog',
  '/admin/combos': 'catalog',
  '/admin/cashbox': 'cashbox',
  '/admin/expenses': 'inventory',
  '/admin/business': 'dashboard',
  '/admin/coupons': 'marketing',
  '/admin/loyalty': 'marketing',
  '/admin/tables': 'tables',
  '/admin/qr': 'tables',
  '/admin/billing': 'billing',
  '/admin/team': 'team',
  '/admin/roles': 'team',
  '/admin/customers': 'customers',
  '/admin/audit': 'audit',
  '/admin/health': 'audit',
  '/admin/help': 'billing',
  '/empleado/panel': 'kitchen',
  '/empleado/historial': 'kitchen',
  '/empleado/perfil': 'kitchen',
  '/repartidor/ruta': 'orders',
  '/repartidor/historial': 'orders',
  '/repartidor/perfil': 'orders',
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

const resolveRbacRole = (role: UserRole): RbacRoleKey => {
  if (role === 'employee' || role === 'driver') return role
  if (role === 'admin') return 'admin'
  return 'cashier'
}

const loadRbacMatrix = (): RbacMatrixState => {
  const defaults = createDefaultRbacMatrix()
  const raw = localStorage.getItem(RBAC_MATRIX_STORAGE_KEY)
  if (!raw) return defaults
  try {
    const parsed = JSON.parse(raw) as Partial<RbacMatrixState>
    if (!parsed || typeof parsed !== 'object') return defaults
    const next = { ...defaults } as RbacMatrixState
    for (const role of Object.keys(defaults) as RbacRoleKey[]) {
      const roleData = parsed[role]
      for (const moduleKey of Object.keys(defaults[role]) as RbacModuleKey[]) {
        for (const action of ['view', 'edit', 'delete'] as RbacActionKey[]) {
          const value = roleData?.[moduleKey]?.[action]
          if (typeof value === 'boolean') {
            next[role][moduleKey][action] = value
          }
        }
      }
    }
    return next
  } catch {
    return defaults
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/sell' },
    { path: '/sell', name: 'landing-sell', component: LandingSellView, meta: { public: true, label: 'Dunamis Sell', landing: true } },
    { path: '/tienda/:tenantSlug', name: 'tenant-shop', component: ClientView, meta: { public: true, label: 'Tienda' } },
    { path: '/login', name: 'login', component: LoginView, meta: { public: true, label: 'Acceso' } },
    { path: '/403', name: 'forbidden', component: ForbiddenView, meta: { public: true, label: 'Acceso denegado' } },
    { path: '/superadmin', redirect: '/superadmin/home' },
    { path: '/superadmin/home', name: 'superadmin-home', component: SuperAdminView, meta: { requiresAuth: true, roles: ['superadmin'], label: 'Admin General SaaS' } },
    { path: '/admin', redirect: '/admin/home' },
    { path: '/admin/home', name: 'admin-home', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Inicio', adminTab: 'home' } },
    { path: '/admin/orders', name: 'admin-orders', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Pedidos', adminTab: 'orders' } },
    { path: '/admin/catalog', redirect: '/admin/products' },
    { path: '/admin/products', name: 'admin-products', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Productos', adminTab: 'products' } },
    { path: '/admin/inventory', name: 'admin-inventory', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Inventario', adminTab: 'inventory' } },
    { path: '/admin/categories', name: 'admin-categories', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Categorias', adminTab: 'categories' } },
    { path: '/admin/daily-menus', name: 'admin-daily-menus', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Menus del Dia', adminTab: 'dailymenus' } },
    { path: '/admin/combos', name: 'admin-combos', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Combos', adminTab: 'combos' } },
    { path: '/admin/cashbox', name: 'admin-cashbox', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Caja', adminTab: 'cashbox' } },
    { path: '/admin/expenses', name: 'admin-expenses', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Gastos e Insumos', adminTab: 'expenses' } },
    { path: '/admin/business', name: 'admin-business', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Negocio', adminTab: 'business' } },
    { path: '/admin/coupons', name: 'admin-coupons', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Cupones', adminTab: 'coupons' } },
    { path: '/admin/loyalty', name: 'admin-loyalty', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Fidelizacion', adminTab: 'loyalty' } },
    { path: '/admin/tables', name: 'admin-tables', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Mesas', adminTab: 'tables' } },
    { path: '/admin/qr', name: 'admin-qr', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin QR Salon', adminTab: 'qr' } },
    { path: '/admin/billing', name: 'admin-billing', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Suscripcion y Planes', adminTab: 'billing' } },
    { path: '/admin/team', name: 'admin-team', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Equipo', adminTab: 'team' } },
    { path: '/admin/roles', name: 'admin-roles', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Roles', adminTab: 'roles' } },
    { path: '/admin/customers', name: 'admin-customers', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Clientes', adminTab: 'customers' } },
    { path: '/admin/audit', name: 'admin-audit', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Auditoria', adminTab: 'audit' } },
    { path: '/admin/health', name: 'admin-health', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Calidad y Operacion', adminTab: 'health' } },
    { path: '/admin/help', name: 'admin-help', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Admin Ayuda y Legal', adminTab: 'help' } },
    { path: '/empleado', redirect: '/empleado/panel' },
    { path: '/empleado/panel', name: 'employee-panel', component: EmployeeView, meta: { requiresAuth: true, roles: ['employee'], label: 'Cocina Panel' } },
    { path: '/empleado/historial', name: 'employee-history', component: EmployeeHistoryView, meta: { requiresAuth: true, roles: ['employee'], label: 'Cocina Historial' } },
    { path: '/empleado/perfil', name: 'employee-profile', component: EmployeeProfileView, meta: { requiresAuth: true, roles: ['employee'], label: 'Cocina Perfil' } },
    { path: '/repartidor', redirect: '/repartidor/ruta' },
    { path: '/repartidor/ruta', name: 'driver-route', component: DriverView, meta: { requiresAuth: true, roles: ['driver'], label: 'Repartidor Ruta' } },
    { path: '/repartidor/historial', name: 'driver-history', component: DriverHistoryView, meta: { requiresAuth: true, roles: ['driver'], label: 'Repartidor Historial' } },
    { path: '/repartidor/perfil', name: 'driver-profile', component: DriverProfileView, meta: { requiresAuth: true, roles: ['driver'], label: 'Repartidor Perfil' } },
    { path: '/cliente', redirect: '/cliente/tienda' },
    { path: '/cliente/tienda', name: 'client-shop', component: ClientView, meta: { requiresAuth: true, roles: ['client'], label: 'Cliente Tienda' } },
    { path: '/cliente/pedidos', name: 'client-orders', component: ClientOrdersView, meta: { requiresAuth: true, roles: ['client'], label: 'Cliente Pedidos' } },
    { path: '/cliente/perfil', name: 'client-profile', component: ClientProfileView, meta: { requiresAuth: true, roles: ['client'], label: 'Cliente Perfil' } },
  ],
})

const SEO_ORIGIN = (import.meta.env.VITE_CANONICAL_ORIGIN || 'https://delivery.labarcaministerio.com').replace(/\/$/, '')

const applySeoMeta = (to: any) => {
  const routeLabel = typeof to.meta?.label === 'string' ? to.meta.label : ''
  const title = routeLabel ? `${routeLabel} | Dunamis Delivery` : 'Dunamis Delivery | Multi-role Operations'
  document.title = title

  const canonicalHref = `${SEO_ORIGIN}${to.fullPath || '/'}`
  let canonical = document.querySelector<HTMLLinkElement>('link[rel="canonical"]')
  if (!canonical) {
    canonical = document.createElement('link')
    canonical.rel = 'canonical'
    document.head.appendChild(canonical)
  }
  canonical.href = canonicalHref
}

router.beforeEach((to) => {
  const user = loadUser()
  const isPublic = Boolean(to.meta.public)
  const requiresAuth = Boolean(to.meta.requiresAuth)

  if (!user && requiresAuth) {
    return '/login'
  }

  if (user && to.path === '/login') {
    return routeByRole[user.user.role]
  }

  if (user && requiresAuth) {
    const roles = (to.meta.roles || []) as UserRole[]
    if (roles.length && !roles.includes(user.user.role)) {
      return routeByRole[user.user.role]
    }
    const module = routePermissionByPath[to.path]
    if (module) {
      const matrix = loadRbacMatrix()
      const rbacRole = resolveRbacRole(user.user.role)
      const canView = Boolean(matrix[rbacRole]?.[module]?.view)
      if (!canView) {
        return '/403'
      }
    }
  }

  if (!isPublic) {
    applySeoMeta(to)
    return true
  }
  applySeoMeta(to)
  return true
})

export default router
