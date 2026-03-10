import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import HealthView from '../views/HealthView.vue'
import DashboardView from '../views/DashboardView.vue'
import ProductsView from '../views/ProductsView.vue'
import ClientsView from '../views/ClientsView.vue'
import SalesView from '../views/SalesView.vue'
import ReportsView from '../views/ReportsView.vue'
import ProfileView from '../views/ProfileView.vue'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  { path: '/health', name: 'health', component: HealthView, meta: { public: true } },
  { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { requiresAuth: true } },
  { path: '/products', name: 'products', component: ProductsView, meta: { requiresAuth: true } },
  { path: '/clients', name: 'clients', component: ClientsView, meta: { requiresAuth: true } },
  { path: '/sales', name: 'sales', component: SalesView, meta: { requiresAuth: true } },
  { path: '/reports', name: 'reports', component: ReportsView, meta: { requiresAuth: true } },
  { path: '/profile', name: 'profile', component: ProfileView, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0, left: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  try {
    if (to.meta.requiresAuth || to.path === '/login') {
      await auth.ensureUser()
    }
  } catch {
    if (to.meta.requiresAuth) {
      return '/login'
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return '/login'
  }

  if (to.path === '/login' && auth.isAuthenticated) {
    return '/dashboard'
  }

  return true
})

export default router
