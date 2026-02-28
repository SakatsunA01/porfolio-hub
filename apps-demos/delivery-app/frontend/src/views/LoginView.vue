<script setup lang="ts">
import { reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { BriefcaseBusiness, CookingPot, ShieldCheck, Truck, UserRound } from 'lucide-vue-next'
import { useDeliveryStore, type UserRole } from '../stores/delivery'

const store = useDeliveryStore()
const router = useRouter()
const route = useRoute()

const roleCards: Array<{ role: UserRole; label: string; subtitle: string; icon: unknown }> = [
  { role: 'superadmin', label: 'Admin General', subtitle: 'Gestion SaaS de negocios', icon: ShieldCheck },
  { role: 'client', label: 'Cliente', subtitle: 'Compra y seguimiento del pedido', icon: UserRound },
  { role: 'employee', label: 'Cocina', subtitle: 'Preparacion y despacho interno', icon: CookingPot },
  { role: 'admin', label: 'Admin', subtitle: 'Gestion de pedidos y stock', icon: BriefcaseBusiness },
  { role: 'driver', label: 'Repartidor', subtitle: 'Ruta y confirmacion de entrega', icon: Truck },
]
const form = reactive({
  email: '',
  password: '',
  tenantSlug: String(route.query.tenant || import.meta.env.VITE_DEFAULT_TENANT_SLUG || 'demo-delivery'),
})

const DEMO_CREDENTIALS: Record<UserRole, { email: string; password: string }> = {
  superadmin: { email: 'superadmin@dunamis.local', password: 'demo1234' },
  admin: { email: 'admin@delivery.local', password: 'demo1234' },
  employee: { email: 'empleado@delivery.local', password: 'demo1234' },
  driver: { email: 'repartidor@delivery.local', password: 'demo1234' },
  client: { email: 'cliente@delivery.local', password: 'demo1234' },
}

const emitAuthLog = (roleLabel: string) => {
  const message = `[AUTH] Perfil ${roleLabel} inicializado....`
  console.info(message)
  if (window.parent && window.parent !== window) {
    window.parent.postMessage(
      {
        type: 'delivery-auth-log',
        payload: message,
      },
      '*',
    )
  }
}

const enterWithRole = async (role: UserRole) => {
  emitAuthLog(roleCards.find((item) => item.role === role)?.label || role)
  const fallback = DEMO_CREDENTIALS[role]
  const email = fallback.email
  const password = fallback.password
  form.email = email
  form.password = password
  const ok = await store.login(email, password, form.tenantSlug)
  if (!ok) return
  const redirect = String(route.query.redirect || '').trim()
  if (redirect) {
    router.push(redirect)
    return
  }
  router.push(store.allowedRouteByRole)
}

const submitLogin = async () => {
  emitAuthLog('acceso')
  const ok = await store.login(form.email, form.password, form.tenantSlug)
  if (!ok) return
  const redirect = String(route.query.redirect || '').trim()
  if (redirect) {
    router.push(redirect)
    return
  }
  router.push(store.allowedRouteByRole)
}
</script>

<template>
  <section class="tracking-tight">
    <article class="forest-card relative overflow-hidden p-6 md:p-8">
      <span class="forest-glow -right-8 -top-8"></span>
      <header class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
          <p class="text-sm font-semibold text-slate-700">Delivery App</p>
        </div>
        <p class="text-sm text-slate-500">Hola, Sergio</p>
      </header>

      <h2 class="mt-6 text-3xl font-semibold text-slate-900">Selecciona un portal</h2>
      <p class="mt-1 text-sm text-slate-500">Ingresa con usuario real y luego elige el modulo operativo.</p>

      <form class="mt-4 grid gap-2 md:grid-cols-2" @submit.prevent="submitLogin">
        <input
          v-model="form.email"
          type="email"
          class="rounded-xl border border-slate-200/70 bg-white/85 px-3 py-2 text-sm"
          placeholder="Email"
          autocomplete="username"
        />
        <input
          v-model="form.password"
          type="password"
          class="rounded-xl border border-slate-200/70 bg-white/85 px-3 py-2 text-sm"
          placeholder="Password"
          autocomplete="current-password"
        />
        <input
          v-model="form.tenantSlug"
          type="text"
          class="rounded-xl border border-slate-200/70 bg-white/85 px-3 py-2 text-sm md:col-span-2"
          placeholder="Negocio (tenant slug)"
        />
        <button type="submit" class="sr-only">Ingresar</button>
      </form>
      <p class="mt-1 text-xs text-slate-500">Demo por defecto: <span class="font-semibold">demo-delivery</span></p>
      <p v-if="store.authError" class="mt-2 text-sm font-semibold text-rose-600">{{ store.authError }}</p>

      <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
        <button
          v-for="card in roleCards"
          :key="card.role"
          type="button"
          class="group flex w-full flex-col items-center gap-2 rounded-2xl border border-slate-200/70 bg-white p-4 text-center transition duration-200 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-xl active:scale-95"
          @click="enterWithRole(card.role)"
        >
          <component :is="card.icon" class="h-6 w-6 text-slate-400 transition group-hover:text-emerald-500" />
          <p class="text-base font-medium text-slate-700">{{ card.label }}</p>
          <p class="truncate text-xs text-slate-500">{{ card.subtitle }}</p>
        </button>
      </div>
    </article>
  </section>
</template>
