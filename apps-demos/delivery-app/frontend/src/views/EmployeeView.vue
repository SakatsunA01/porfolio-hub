<script setup lang="ts">
import { computed } from 'vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import AppButton from '../components/common/AppButton.vue'
import { useDeliveryStore } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

const store = useDeliveryStore()
useOrdersRealtime()

const kitchenOrders = computed(() => store.orders.filter((order) => ['received', 'preparing'].includes(order.status)))

const waitingBadgeClass = (createdAt: number) => {
  const mins = Math.round((Date.now() - createdAt) / 60000)
  if (mins >= 30) return 'text-rose-700 bg-rose-50 border-rose-200'
  if (mins >= 18) return 'text-amber-700 bg-amber-50 border-amber-200'
  return 'text-emerald-700 bg-emerald-50 border-emerald-200'
}
</script>

<template>
  <article class="forest-card relative overflow-hidden p-4 backdrop-blur-md">
    <span class="forest-glow -right-8 -top-8"></span>
    <h2 class="text-lg font-bold text-slate-800">Kitchen Station</h2>
    <p class="mt-1 text-sm text-slate-500">Cola de preparacion y prioridades por demora.</p>

    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/60 bg-white/80 p-3 backdrop-blur-sm">
      <span class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Empleado en turno</span>
      <select class="input max-w-[280px]" v-model.number="store.shiftEmployeeId">
        <option v-for="employee in store.activeEmployees" :key="employee.id" :value="employee.id">
          {{ employee.name }}
        </option>
      </select>
    </div>

    <div v-if="!kitchenOrders.length" class="mt-3 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
      Sin pedidos en cocina por ahora.
    </div>

    <TransitionGroup v-else name="kitchen" tag="div" class="mt-3 space-y-2">
      <div v-for="order in kitchenOrders" :key="`kitchen-${order.id}`" class="rounded-2xl border border-slate-200/70 bg-white p-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
            <StatusBadge :value="order.status === 'preparing' ? 'En Cocina' : 'Pendiente'" />
          </div>
          <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
            {{ Math.round((Date.now() - order.createdAt) / 60000) }} min en espera
          </span>
        </div>

        <p class="mt-1 text-sm text-slate-500">
          {{ order.items.map((item) => `${item.qty}x ${store.getProduct(item.productId)?.name || 'Producto'}`).join(' | ') }}
        </p>
        <p class="text-xs text-slate-500">Asignado: {{ order.employeeId ? store.getEmployee(order.employeeId)?.name : 'Sin asignar' }}</p>

        <div class="mt-2 flex flex-wrap gap-2">
          <AppButton
            variant="primary"
            :disabled="!store.shiftEmployeeId || (order.employeeId !== null && order.employeeId !== store.shiftEmployeeId)"
            @click="store.startPreparing(order.id, store.shiftEmployeeId)"
          >
            Tomar / Iniciar
          </AppButton>
          <AppButton variant="soft" :disabled="order.employeeId !== store.shiftEmployeeId" @click="store.markReady(order.id)">
            Marcar listo
          </AppButton>
        </div>
      </div>
    </TransitionGroup>
  </article>
</template>

<style scoped>
.input {
  border: 1px solid rgb(226 232 240 / 0.9);
  border-radius: 0.9rem;
  padding: 0.62rem 0.72rem;
  font-size: 0.92rem;
  width: 100%;
  background: rgb(255 255 255 / 0.85);
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
