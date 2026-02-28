<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { BarChart3, Check, QrCode, Store, Truck, MessageCircle } from 'lucide-vue-next'
import { useRouter } from 'vue-router'

const router = useRouter()
const scrolled = ref(false)
const openFaqId = ref<number | null>(1)
const contactForm = reactive({
  name: '',
  email: '',
  phone: '',
  message: '',
})

const pricingPlans = [
  {
    key: 'takeaway',
    name: 'Takeaway',
    price: '$110.000',
    period: '/ mes',
    badge: '',
    tone: 'slate',
    features: ['Menú QR', 'Pedidos web y WhatsApp', 'Caja básica'],
  },
  {
    key: 'full',
    name: 'Full Operativo',
    price: '$160.000',
    period: '/ mes',
    badge: 'MÁS ELEGIDO',
    tone: 'emerald',
    features: ['App cocina táctil', 'Gestión de repartidores', 'Modo Midnight'],
  },
  {
    key: 'bi',
    name: 'Business Intelligence',
    price: '$230.000',
    period: '/ mes',
    badge: 'PREMIUM',
    tone: 'dark',
    features: ['Auditoría avanzada', 'Métricas de salud', 'Marketing y fidelización'],
  },
] as const

const faqItems = [
  { id: 1, q: '¿Tengo que instalar algo?', a: 'No. Dunamis es 100% web y funciona en celular, tablet y escritorio.' },
  { id: 2, q: '¿Cómo cobro mis ventas?', a: 'El dinero va a tus cuentas de cobro. Dunamis solo cobra un abono fijo mensual, sin comisión por pedido.' },
  { id: 3, q: '¿Puedo usar mis propios repartidores?', a: 'Sí. En el plan Full gestionás repartidores, estados y entregas desde la app.' },
  { id: 4, q: '¿Cuánto tarda en quedar online?', a: 'En pocos minutos: cargás logo, menú y medios de pago, y empezás a vender.' },
] as const

const roiOrders = ref(350)
const roiAvgTicket = ref(8500)
const roiCommissionRate = 0.22
const roiPlanCost = 160000

const roiMonthlySales = computed(() => roiOrders.value * roiAvgTicket.value)
const roiCommissionCost = computed(() => roiMonthlySales.value * roiCommissionRate)
const roiNetDifference = computed(() => roiCommissionCost.value - roiPlanCost)

const formatArs = (value: number) =>
  new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value)

const showNavSolid = computed(() => scrolled.value)

const handleScroll = () => {
  scrolled.value = window.scrollY > 18
}

const startTrial = () => {
  router.push('/login')
}

const submitContact = () => {
  contactForm.name = ''
  contactForm.email = ''
  contactForm.phone = ''
  contactForm.message = ''
}

const toggleFaq = (id: number) => {
  openFaqId.value = openFaqId.value === id ? null : id
}

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="min-h-screen bg-[#F8F9FA] text-slate-900">
    <header
      class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
      :class="showNavSolid ? 'bg-white/85 shadow-[0_4px_20px_rgba(0,0,0,0.05)] backdrop-blur-md' : 'bg-transparent'"
    >
      <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 md:px-6">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-emerald-600 text-white">
            <Store class="h-4 w-4" />
          </span>
          <p class="text-sm font-extrabold tracking-wide">Dunamis Store</p>
        </div>
        <nav class="hidden items-center gap-5 text-sm font-medium text-slate-600 md:flex">
          <a href="#beneficios" class="hover:text-slate-900">Beneficios</a>
          <a href="#producto" class="hover:text-slate-900">Producto</a>
          <a href="#planes" class="hover:text-slate-900">Planes</a>
          <a href="#faq" class="hover:text-slate-900">FAQ</a>
        </nav>
        <button
          type="button"
          class="rounded-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-4 py-2 text-xs font-bold text-white shadow-[0_8px_20px_rgba(16,185,129,0.35)] transition hover:brightness-110 active:scale-[0.98]"
          @click="startTrial"
        >
          Empezar 7 días gratis
        </button>
      </div>
    </header>

    <main class="pb-14 pt-24">
      <section class="mx-auto grid w-full max-w-6xl gap-8 px-4 md:grid-cols-[1.05fr_0.95fr] md:px-6">
        <div class="space-y-5">
          <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">SaaS para restaurantes sin comisiones</span>
          <h1 class="text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">
            Tu restaurante, tus reglas.
            <br />
            Sin comisiones por pedido.
          </h1>
          <p class="max-w-2xl text-base text-slate-600">
            Digitalizá tu menú, gestioná tus repartidores y tomá el control total de tu rentabilidad con un costo fijo mensual.
          </p>
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="rounded-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-6 py-3 text-sm font-bold text-white shadow-[0_10px_28px_rgba(16,185,129,0.35)] transition hover:brightness-110 active:scale-[0.98]"
              @click="startTrial"
            >
              Empezar 7 días gratis
            </button>
            <a
              href="#planes"
              class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-[0_4px_20px_rgba(0,0,0,0.05)] ring-1 ring-slate-200 transition hover:bg-slate-50"
            >
              Ver planes
            </a>
          </div>
        </div>

        <div class="relative min-h-[380px]">
          <article class="absolute right-0 top-0 w-[78%] rounded-[32px] bg-white p-4 shadow-[0_20px_40px_rgba(15,23,42,0.12)] ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dashboard Admin</p>
              <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Online</span>
            </div>

            <div class="mt-3 grid grid-cols-4 gap-2">
              <div class="rounded-2xl bg-emerald-50 p-2.5">
                <p class="text-[10px] text-slate-500">Pedidos</p>
                <p class="text-base font-extrabold text-slate-900">18</p>
              </div>
              <div class="rounded-2xl bg-slate-100 p-2.5">
                <p class="text-[10px] text-slate-500">Ingresos</p>
                <p class="text-base font-extrabold text-slate-900">$320k</p>
              </div>
              <div class="rounded-2xl bg-amber-50 p-2.5">
                <p class="text-[10px] text-slate-500">Pendientes</p>
                <p class="text-base font-extrabold text-slate-900">5</p>
              </div>
              <div class="rounded-2xl bg-sky-50 p-2.5">
                <p class="text-[10px] text-slate-500">Uptime</p>
                <p class="text-base font-extrabold text-slate-900">99.9%</p>
              </div>
            </div>

            <div class="mt-3 rounded-2xl bg-slate-50 p-2.5">
              <div class="mb-1 flex items-center justify-between text-[10px] font-semibold text-slate-500">
                <span>Últimos pedidos</span>
                <span>Estado</span>
              </div>
              <div class="space-y-1.5 text-[11px]">
                <div class="flex items-center justify-between rounded-xl bg-white px-2 py-1.5">
                  <span class="font-semibold text-slate-700">#845 • Mesa 4</span>
                  <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">En cocina</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-white px-2 py-1.5">
                  <span class="font-semibold text-slate-700">#844 • Delivery</span>
                  <span class="rounded-full bg-sky-100 px-2 py-0.5 font-semibold text-sky-700">En camino</span>
                </div>
              </div>
            </div>
          </article>

          <article class="absolute bottom-0 left-0 w-[56%] rounded-[32px] bg-white p-4 shadow-[0_20px_40px_rgba(15,23,42,0.12)] ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tienda Cliente</p>
              <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">$11.900</span>
            </div>

            <div class="mt-2 flex gap-1.5 text-[10px] font-semibold">
              <span class="rounded-full bg-slate-200 px-2 py-0.5 text-slate-700">Burgers</span>
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">Combos</span>
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">Bebidas</span>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2">
              <div class="rounded-2xl bg-slate-100 p-2">
                <p class="text-[11px] font-semibold text-slate-700">Burger Clásica</p>
                <p class="text-[11px] font-bold text-emerald-700">$8.500</p>
              </div>
              <div class="rounded-2xl bg-slate-100 p-2">
                <p class="text-[11px] font-semibold text-slate-700">Combo Doble</p>
                <p class="text-[11px] font-bold text-emerald-700">$11.900</p>
              </div>
            </div>
            <button type="button" class="mt-2.5 w-full rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white">
              Ir al carrito
            </button>
          </article>
        </div>
      </section>

      <section id="beneficios" class="mx-auto mt-14 w-full max-w-6xl px-4 md:px-6">
        <div class="rounded-[32px] bg-white p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <div class="grid gap-3 md:grid-cols-3">
            <article class="rounded-2xl bg-slate-50 p-4">
              <p class="text-sm font-bold text-slate-900">Independencia</p>
              <p class="mt-1 text-sm text-slate-600">Olvidate de las comisiones abusivas.</p>
            </article>
            <article class="rounded-2xl bg-slate-50 p-4">
              <p class="text-sm font-bold text-slate-900">Velocidad</p>
              <p class="mt-1 text-sm text-slate-600">Menú QR y pedidos web/WhatsApp en minutos.</p>
            </article>
            <article class="rounded-2xl bg-slate-50 p-4">
              <p class="text-sm font-bold text-slate-900">Control</p>
              <p class="mt-1 text-sm text-slate-600">Sabé exactamente cuánto ganás con costos y márgenes.</p>
            </article>
          </div>
        </div>
      </section>

      <section id="producto" class="mx-auto mt-12 w-full max-w-6xl px-4 md:px-6">
        <div class="grid gap-3 md:grid-cols-3">
          <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
              <QrCode class="h-5 w-5" />
            </div>
            <h3 class="mt-3 text-lg font-extrabold text-slate-900">Tu cocina, organizada</h3>
            <p class="mt-1 text-sm text-slate-600">KDS visual para cocina con prioridad por tiempo y estados claros.</p>
          </article>
          <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
              <Truck class="h-5 w-5" />
            </div>
            <h3 class="mt-3 text-lg font-extrabold text-slate-900">Repartidores Pro</h3>
            <p class="mt-1 text-sm text-slate-600">App de repartidor con modo nocturno, flujo de entregas y confirmación segura.</p>
          </article>
          <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
              <BarChart3 class="h-5 w-5" />
            </div>
            <h3 class="mt-3 text-lg font-extrabold text-slate-900">Inteligencia de negocio</h3>
            <p class="mt-1 text-sm text-slate-600">Márgenes, auditoría y salud operativa para decidir con datos reales.</p>
          </article>
        </div>
      </section>

      <section id="planes" class="mx-auto mt-12 w-full max-w-6xl px-4 md:px-6">
        <h2 class="text-center text-3xl font-extrabold text-slate-900">Planes para cada etapa de tu local</h2>
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
          <article
            v-for="plan in pricingPlans"
            :key="plan.key"
            class="rounded-[32px] p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
            :class="
              plan.tone === 'emerald'
                ? 'border-2 border-emerald-400 bg-gradient-to-br from-emerald-50 via-white to-emerald-100 shadow-[0_10px_28px_rgba(16,185,129,0.16)]'
                : plan.tone === 'dark'
                  ? 'bg-slate-900 text-white'
                  : 'bg-white'
            "
          >
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-lg font-extrabold" :class="plan.tone === 'dark' ? 'text-white' : 'text-slate-900'">{{ plan.name }}</h3>
              <span v-if="plan.badge" class="rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ plan.badge }}</span>
            </div>
            <div class="mt-2 flex items-end gap-1">
              <p class="text-3xl font-extrabold" :class="plan.tone === 'dark' ? 'text-white' : 'text-slate-900'">{{ plan.price }}</p>
              <p class="text-xs" :class="plan.tone === 'dark' ? 'text-slate-300' : 'text-slate-500'">{{ plan.period }}</p>
            </div>
            <ul class="mt-4 space-y-2 text-sm" :class="plan.tone === 'dark' ? 'text-slate-200' : 'text-slate-600'">
              <li v-for="feature in plan.features" :key="`${plan.key}-${feature}`" class="flex items-center gap-2">
                <Check class="h-4 w-4 text-emerald-500" />
                {{ feature }}
              </li>
            </ul>
            <button
              type="button"
              class="mt-5 w-full rounded-full px-4 py-2 text-sm font-bold transition active:scale-[0.98]"
              :class="plan.tone === 'dark' ? 'bg-white text-slate-900 hover:bg-slate-100' : 'bg-slate-900 text-white hover:bg-slate-800'"
              @click="startTrial"
            >
              {{ plan.key === 'full' ? 'Empezar prueba gratis' : 'Elegir plan' }}
            </button>
          </article>
        </div>
      </section>

      <section class="mx-auto mt-8 w-full max-w-6xl px-4 md:px-6">
        <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Calculadora ROI</p>
              <h3 class="mt-1 text-xl font-extrabold text-slate-900">¿Cuánto te cuestan las comisiones por mes?</h3>
              <p class="mt-1 text-sm text-slate-600">Compará un marketplace (22%) vs plan Full Operativo fijo.</p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Plan Full: {{ formatArs(roiPlanCost) }}/mes</span>
          </div>

          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <label class="rounded-2xl bg-slate-50 p-3">
              <span class="text-xs font-semibold text-slate-500">Pedidos por mes</span>
              <input v-model.number="roiOrders" type="number" min="1" class="mt-2 w-full rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 outline-none ring-1 ring-slate-200 focus:ring-emerald-400" />
            </label>
            <label class="rounded-2xl bg-slate-50 p-3">
              <span class="text-xs font-semibold text-slate-500">Ticket promedio (ARS)</span>
              <input v-model.number="roiAvgTicket" type="number" min="1" step="100" class="mt-2 w-full rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 outline-none ring-1 ring-slate-200 focus:ring-emerald-400" />
            </label>
          </div>

          <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 p-3">
              <p class="text-xs text-slate-500">Venta mensual estimada</p>
              <p class="mt-1 text-lg font-extrabold text-slate-900">{{ formatArs(roiMonthlySales) }}</p>
            </div>
            <div class="rounded-2xl bg-rose-50 p-3">
              <p class="text-xs text-rose-600">Comisiones marketplace (22%)</p>
              <p class="mt-1 text-lg font-extrabold text-rose-700">{{ formatArs(roiCommissionCost) }}</p>
            </div>
            <div class="rounded-2xl p-3" :class="roiNetDifference > 0 ? 'bg-emerald-50' : 'bg-amber-50'">
              <p class="text-xs" :class="roiNetDifference > 0 ? 'text-emerald-700' : 'text-amber-700'">
                {{ roiNetDifference > 0 ? 'Ahorro estimado con Dunamis' : 'Diferencia frente al plan Full' }}
              </p>
              <p class="mt-1 text-lg font-extrabold" :class="roiNetDifference > 0 ? 'text-emerald-700' : 'text-amber-700'">
                {{ formatArs(Math.abs(roiNetDifference)) }}
              </p>
            </div>
          </div>
        </article>
      </section>

      <section id="faq" class="mx-auto mt-12 w-full max-w-6xl px-4 md:px-6">
        <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
          <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <h3 class="text-xl font-extrabold text-slate-900">Preguntas Frecuentes</h3>
            <p class="mt-1 text-sm text-slate-600">Respuestas rápidas antes de activar tu prueba.</p>
            <div class="mt-4 space-y-2">
              <article v-for="item in faqItems" :key="item.id" class="rounded-2xl bg-slate-50 px-3 py-2">
                <button type="button" class="flex w-full items-center justify-between gap-2 text-left" @click="toggleFaq(item.id)">
                  <span class="text-sm font-semibold text-slate-900">{{ item.q }}</span>
                  <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600">{{ openFaqId === item.id ? '-' : '+' }}</span>
                </button>
                <p v-if="openFaqId === item.id" class="mt-2 text-xs text-slate-600">{{ item.a }}</p>
              </article>
            </div>
            <button
              type="button"
              class="mt-5 rounded-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-6 py-3 text-sm font-bold text-white shadow-[0_10px_28px_rgba(16,185,129,0.35)] transition hover:brightness-110 active:scale-[0.98]"
              @click="startTrial"
            >
              Empezar 7 días gratis
            </button>
          </article>

          <article class="rounded-[32px] bg-white p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <h3 class="text-xl font-extrabold text-slate-900">Ventas directas</h3>
            <p class="mt-1 text-sm text-slate-600">Te ayudamos a configurar tu local en la primera llamada.</p>
            <form class="mt-4 space-y-2" @submit.prevent="submitContact">
              <input v-model="contactForm.name" class="w-full rounded-xl bg-slate-100 px-3 py-2 text-sm outline-none ring-0 focus:bg-white focus:ring-2 focus:ring-emerald-400" type="text" placeholder="Nombre del local" required />
              <input v-model="contactForm.email" class="w-full rounded-xl bg-slate-100 px-3 py-2 text-sm outline-none ring-0 focus:bg-white focus:ring-2 focus:ring-emerald-400" type="email" placeholder="Email" required />
              <input v-model="contactForm.phone" class="w-full rounded-xl bg-slate-100 px-3 py-2 text-sm outline-none ring-0 focus:bg-white focus:ring-2 focus:ring-emerald-400" type="text" placeholder="WhatsApp" />
              <textarea v-model="contactForm.message" class="w-full rounded-xl bg-slate-100 px-3 py-2 text-sm outline-none ring-0 focus:bg-white focus:ring-2 focus:ring-emerald-400" rows="3" placeholder="Contanos qué tipo de local tenés"></textarea>
              <button type="submit" class="w-full rounded-full bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-800 active:scale-[0.98]">
                Solicitar demo
              </button>
            </form>
            <a
              href="https://wa.me/5491112345678?text=Hola%20Dunamis%2C%20quiero%20una%20demo"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700"
            >
              <MessageCircle class="h-4 w-4" />
              Hablar por WhatsApp
            </a>
          </article>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200/80 py-6">
      <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-2 px-4 text-xs text-slate-500 md:px-6">
        <p>© {{ new Date().getFullYear() }} Dunamis Store</p>
        <div class="flex items-center gap-3">
          <a href="#" class="hover:text-slate-700">Términos</a>
          <a href="#" class="hover:text-slate-700">Privacidad</a>
          <a href="#" class="hover:text-slate-700">SLA</a>
        </div>
      </div>
    </footer>
  </div>
</template>

