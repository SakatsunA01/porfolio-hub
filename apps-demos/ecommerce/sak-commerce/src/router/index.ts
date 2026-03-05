import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import CategoryView from '../views/CategoryView.vue'
import CatalogPage from '../views/CatalogPage.vue'
import AboutView from '../views/AboutView.vue'
import ContactView from '../views/ContactView.vue'
import PressView from '../views/PressView.vue'
import PrivacyView from '../views/PrivacyView.vue'
import TermsView from '../views/TermsView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import CartPage from '../views/CartPage.vue'
import CheckoutPage from '../views/CheckoutPage.vue'
import OrderSuccess from '../views/OrderSuccess.vue'
import FavoritesPage from '../views/FavoritesPage.vue'
import AccountPage from '../views/AccountPage.vue'
import AccountOrderDetail from '../views/AccountOrderDetail.vue'
import AdminDashboard from '../views/admin/AdminDashboard.vue'
import AdminProducts from '../views/admin/AdminProducts.vue'
import AdminOrders from '../views/admin/AdminOrders.vue'
import AdminStoreSettings from '../views/admin/AdminStoreSettings.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }

    if (to.hash) {
      return { el: to.hash, top: 80, behavior: 'smooth' }
    }

    return { top: 0, left: 0, behavior: 'smooth' }
  },
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/product/:id', name: 'product-detail', component: ProductDetailView, props: true },
    { path: '/catalog', name: 'catalog', component: CatalogPage },
    { path: '/category/:categoryName', name: 'category', component: CategoryView, props: true },
    { path: '/about', name: 'about', component: AboutView },
    { path: '/contact', name: 'contact', component: ContactView },
    { path: '/press', name: 'press', component: PressView },
    { path: '/privacy', name: 'privacy', component: PrivacyView },
    { path: '/terms', name: 'terms', component: TermsView },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/register', name: 'register', component: RegisterView },
    { path: '/cart', name: 'cart', component: CartPage },
    { path: '/favorites', name: 'favorites', component: FavoritesPage },
    { path: '/checkout', name: 'checkout', component: CheckoutPage },
    { path: '/order-success', name: 'order-success', component: OrderSuccess },
    { path: '/account', name: 'account', component: AccountPage, meta: { requiresAuth: true } },
    {
      path: '/account/orders/:id',
      name: 'account-order-detail',
      component: AccountOrderDetail,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      name: 'admin',
      redirect: '/admin/dashboard',
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: 'dashboard', name: 'admin-dashboard', component: AdminDashboard },
        { path: 'products', name: 'admin-products', component: AdminProducts },
        { path: 'orders', name: 'admin-orders', component: AdminOrders },
        { path: 'settings', name: 'admin-settings', component: AdminStoreSettings },
      ],
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (!authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
    return
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'home' })
    return
  }

  next()
})

export default router
