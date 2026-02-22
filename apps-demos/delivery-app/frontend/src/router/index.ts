import { createRouter, createWebHistory } from 'vue-router'
import AdminView from '../views/AdminView.vue'
import EmployeeView from '../views/EmployeeView.vue'
import DriverView from '../views/DriverView.vue'
import ClientView from '../views/ClientView.vue'
import LoginView from '../views/LoginView.vue'

type UserRole = 'admin' | 'employee' | 'driver' | 'client'

interface StoredUser {
  user: {
    role: UserRole
  }
}

const AUTH_KEY = 'delivery-vue-auth-v2'

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
  admin: '/admin',
  employee: '/empleado',
  driver: '/repartidor',
  client: '/cliente',
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', name: 'login', component: LoginView, meta: { public: true, label: 'Acceso' } },
    { path: '/admin', name: 'admin', component: AdminView, meta: { requiresAuth: true, roles: ['admin'], label: 'Administrador' } },
    { path: '/empleado', name: 'employee', component: EmployeeView, meta: { requiresAuth: true, roles: ['employee'], label: 'Empleado' } },
    { path: '/repartidor', name: 'driver', component: DriverView, meta: { requiresAuth: true, roles: ['driver'], label: 'Repartidor' } },
    { path: '/cliente', name: 'client', component: ClientView, meta: { requiresAuth: true, roles: ['client'], label: 'Cliente' } },
  ],
})

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
  }

  if (!isPublic) {
    return true
  }
  return true
})

export default router
