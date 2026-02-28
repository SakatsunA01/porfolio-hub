<script setup lang="ts">
import { computed } from 'vue'
import { useDeliveryStore } from '../stores/delivery'
import DriverNavTabs from '../components/driver/DriverNavTabs.vue'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

const store = useDeliveryStore()
useOrdersRealtime()

const currentDriverId = computed(() => store.shiftDriverId || store.currentUser?.id || null)
const profileName = computed(() => store.currentUser?.name || 'Repartidor')
const profileEmail = computed(() => store.currentUser?.email || 'repartidor@delivery.local')
const profileStatus = computed(() => {
  const driver = store.drivers.find((item) => item.id === currentDriverId.value)
  return driver?.active !== false ? 'Activo' : 'Inactivo'
})

const driverOrders = computed(() => store.orders.filter((order) => order.driverId === currentDriverId.value))
const deliveredCount = computed(() => driverOrders.value.filter((order) => order.status === 'delivered').length)
const pendingCount = computed(() => driverOrders.value.filter((order) => ['ready', 'onroute'].includes(order.status)).length)
const totalEarnings = computed(() =>
  driverOrders.value
    .filter((order) => order.status === 'delivered')
    .reduce((sum, order) => sum + Number(order.total || 0), 0),
)
</script>

<template>
  <section class="relative space-y-4">
    <header class="forest-card relative overflow-hidden p-4">
      <span class="forest-glow -right-8 -top-8"></span>
      <h2 class="text-xl font-semibold text-slate-900">Perfil del Repartidor</h2>
      <p class="text-sm text-slate-500">Datos de cuenta y resumen de actividad.</p>
      <DriverNavTabs />
    </header>

    <article class="forest-card p-4">
      <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Nombre</p>
          <p class="text-sm font-semibold text-slate-900">{{ profileName }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
          <p class="text-sm font-semibold text-slate-900">{{ profileEmail }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Estado</p>
          <p class="text-sm font-semibold text-emerald-700">{{ profileStatus }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">ID repartidor</p>
          <p class="text-sm font-semibold text-slate-900">{{ currentDriverId || 'Sin asignar' }}</p>
        </div>
      </div>
    </article>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Resumen</h3>
      <div class="mt-3 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
          <p class="text-xs uppercase tracking-wide text-amber-700">Pendientes</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ pendingCount }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3">
          <p class="text-xs uppercase tracking-wide text-emerald-700">Entregados</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ deliveredCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Total entregado</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">${{ totalEarnings.toFixed(2) }}</p>
        </div>
      </div>
    </article>
  </section>
</template>
