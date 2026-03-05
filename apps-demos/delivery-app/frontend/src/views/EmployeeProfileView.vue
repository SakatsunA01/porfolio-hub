<script setup lang="ts">
import { computed } from 'vue'
import { ChefHat, ClipboardCheck, Clock3, ShieldCheck, UserRound } from 'lucide-vue-next'
import { useDeliveryStore } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import EmployeeNavTabs from '../components/employee/EmployeeNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()

const currentEmployeeId = computed(() => store.currentUser?.id || null)
const profileName = computed(() => store.currentUser?.name || 'Cocinero')
const profileEmail = computed(() => store.currentUser?.email || 'empleado@delivery.local')
const businessLabel = computed(() => store.activeStorefrontName || 'Dunamis Store')

const employeeOrders = computed(() =>
  store.orders.filter((order) => order.employeeId === currentEmployeeId.value || (order.status === 'received' && currentEmployeeId.value !== null)),
)
const takenOrders = computed(() => store.orders.filter((order) => order.employeeId === currentEmployeeId.value))
const preparingCount = computed(() => takenOrders.value.filter((order) => order.status === 'preparing').length)
const finishedCount = computed(() => takenOrders.value.filter((order) => ['ready', 'onroute', 'delivered'].includes(order.status)).length)
const avgPrepWindow = computed(() => {
  if (!takenOrders.value.length) return 0
  return Math.round(takenOrders.value.reduce((acc, order) => acc + Number(order.etaMin || 0), 0) / takenOrders.value.length)
})
const firstQueueCount = computed(() => store.orders.filter((order) => order.status === 'received').length)
const profileInitials = computed(() =>
  profileName.value
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('') || 'CK',
)
const employeeStatus = computed(() => (store.currentUser ? 'En servicio' : 'Sin sesion'))
</script>

<template>
  <section class="relative space-y-4">
    <header class="forest-card relative overflow-hidden p-4">
      <span class="forest-glow -right-8 -top-8"></span>
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Perfil de Cocina</h2>
          <p class="text-sm text-slate-500">Datos del usuario logueado y resumen operativo.</p>
        </div>
        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
          {{ businessLabel }}
        </span>
      </div>
      <EmployeeNavTabs />
    </header>

    <article class="forest-card p-4">
      <div class="grid gap-4 lg:grid-cols-[minmax(260px,320px)_minmax(0,1fr)]">
        <div class="rounded-[24px] bg-[linear-gradient(135deg,#0f172a_0%,#14532d_55%,#10b981_100%)] p-4 text-white shadow-[0_18px_40px_rgba(15,23,42,0.18)]">
          <div class="flex items-center gap-3">
            <span class="grid h-16 w-16 place-items-center rounded-[20px] border border-white/20 bg-white/10 text-lg font-black">
              {{ profileInitials }}
            </span>
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">Cocina</p>
              <p class="mt-1 text-xl font-black">{{ profileName }}</p>
              <p class="text-sm text-white/80">{{ profileEmail }}</p>
            </div>
          </div>
          <div class="mt-4 rounded-[18px] border border-white/12 bg-white/10 p-3 backdrop-blur">
            <p class="text-[11px] uppercase tracking-wide text-white/70">Estado actual</p>
            <p class="mt-1 text-base font-bold">{{ employeeStatus }}</p>
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Nombre</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ profileName }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ profileEmail }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Rol</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">Cocinero / Produccion</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">ID usuario</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ currentEmployeeId || 'Sin asignar' }}</p>
          </div>
        </div>
      </div>
    </article>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Resumen operativo</h3>
      <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
          <div class="flex items-center gap-2 text-amber-700">
            <Clock3 class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">En cola</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ firstQueueCount }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-3">
          <div class="flex items-center gap-2 text-sky-700">
            <ChefHat class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Preparando</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ preparingCount }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3">
          <div class="flex items-center gap-2 text-emerald-700">
            <ClipboardCheck class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Terminados</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ finishedCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <div class="flex items-center gap-2 text-slate-600">
            <ShieldCheck class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">ETA promedio</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ avgPrepWindow }} min</p>
        </div>
      </div>
    </article>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Actividad</h3>
      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <div class="flex items-center gap-2 text-slate-700">
            <UserRound class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Pedidos tomados por vos</p>
          </div>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ takenOrders.length }}</p>
          <p class="text-xs text-slate-500">Cuenta pedidos ya asignados a tu usuario.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <div class="flex items-center gap-2 text-slate-700">
            <ChefHat class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Visibles en cocina</p>
          </div>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ employeeOrders.length }}</p>
          <p class="text-xs text-slate-500">Incluye pedidos en cola y pedidos bajo tu produccion.</p>
        </div>
      </div>
    </article>
  </section>
</template>
