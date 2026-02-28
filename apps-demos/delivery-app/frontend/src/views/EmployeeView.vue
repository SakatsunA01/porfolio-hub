<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, PlayCircle } from 'lucide-vue-next'
import StatusBadge from '../components/common/StatusBadge.vue'
import AppButton from '../components/common/AppButton.vue'
import { useDeliveryStore } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import EmployeeNavTabs from '../components/employee/EmployeeNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()

const kitchenOrders = computed(() => store.orders.filter((order) => ['received', 'preparing'].includes(order.status)))
const businessLabel = computed(() => store.activeStorefrontName || 'Dunamis Store')

const waitingBadgeClass = (createdAt: number) => {
  const mins = Math.round((Date.now() - createdAt) / 60000)
  if (mins >= 30) return 'text-rose-700 bg-rose-100 border-rose-200'
  if (mins >= 18) return 'text-amber-700 bg-amber-100 border-amber-200'
  return 'text-emerald-700 bg-emerald-100 border-emerald-200'
}
</script>

<template>
  <article class="forest-card relative overflow-hidden rounded-[24px] p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
    <span class="forest-glow -right-8 -top-8"></span>
    <h2 class="text-lg font-bold text-slate-900">{{ businessLabel }}</h2>
    <p class="mt-1 text-sm text-slate-500">Cola de preparacion y prioridades por demora.</p>
    <EmployeeNavTabs />

    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-[20px] bg-slate-50 p-3 ring-1 ring-slate-200">
      <span class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Empleado en turno</span>
      <select class="input max-w-[280px]" v-model.number="store.shiftEmployeeId">
        <option v-if="!store.activeEmployees.length" :value="null" disabled>Sin empleado disponible</option>
        <option v-for="employee in store.activeEmployees" :key="employee.id" :value="employee.id">
          {{ employee.name }}
        </option>
      </select>
      <p v-if="!store.activeEmployees.length" class="text-xs text-amber-700">
        No se pudo cargar el perfil del empleado en turno.
      </p>
    </div>

    <div v-if="!kitchenOrders.length" class="mt-3 rounded-[20px] bg-slate-50 p-4 text-sm text-slate-500 ring-1 ring-slate-200">
      Sin pedidos en cocina por ahora.
    </div>

    <TransitionGroup v-else name="kitchen" tag="div" class="mt-3 space-y-2">
      <div v-for="order in kitchenOrders" :key="`kitchen-${order.id}`" class="rounded-[20px] bg-white p-3 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
            <StatusBadge :value="order.status === 'preparing' ? 'En Cocina' : 'Pendiente'" />
          </div>
          <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
            {{ Math.round((Date.now() - order.createdAt) / 60000) }} min en espera
          </span>
        </div>

        <p class="mt-1 text-sm text-slate-600">
          {{ order.items.map((item) => `${item.qty}x ${store.getProduct(item.productId)?.name || 'Producto'}`).join(' | ') }}
        </p>
        <p class="text-xs text-slate-500">Asignado: {{ order.employeeId ? store.getEmployee(order.employeeId)?.name : 'Sin asignar' }}</p>

        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
          <AppButton
            class="w-full sm:w-auto"
            variant="primary"
            :disabled="!store.shiftEmployeeId || (order.employeeId !== null && order.employeeId !== store.shiftEmployeeId)"
            @click="store.startPreparing(order.id, store.shiftEmployeeId)"
          >
            <span class="inline-flex items-center gap-1.5">
              <PlayCircle class="h-4 w-4" />
              Tomar / Iniciar
            </span>
          </AppButton>
          <AppButton class="w-full sm:w-auto" variant="soft" :disabled="order.employeeId !== store.shiftEmployeeId" @click="store.markReady(order.id)">
            <span class="inline-flex items-center gap-1.5">
              <CheckCircle2 class="h-4 w-4" />
              Marcar listo
            </span>
          </AppButton>
        </div>
      </div>
    </TransitionGroup>
  </article>
</template>

<style scoped>
.input {
  border: 1px solid rgb(203 213 225 / 0.9);
  border-radius: 0.9rem;
  padding: 0.62rem 0.72rem;
  font-size: 0.92rem;
  width: 100%;
  background: rgb(255 255 255 / 0.96);
  color: rgb(15 23 42);
}

.kitchen-enter-active,
.kitchen-leave-active {
  transition: all 500ms ease;
}

.kitchen-enter-from,
.kitchen-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
