<script setup lang="ts">
import { computed } from 'vue'
import OrderStatusBadge from '../components/OrderStatusBadge.vue'
import { useDeliveryStore } from '../stores/delivery'

const store = useDeliveryStore()

const pending = computed(() => store.orders.filter((order) => ['received', 'preparing'].includes(order.status)))
const ready = computed(() => store.orders.filter((order) => order.status === 'ready'))
const onroute = computed(() => store.orders.filter((order) => order.status === 'onroute'))
</script>

<template>
  <section class="forest-card relative overflow-hidden p-4 backdrop-blur-md">
    <span class="forest-glow -right-8 -top-8"></span>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Modo Operador Unico</h2>
        <p class="text-sm text-slate-500">Panel multitarea para controlar todo en una sola vista.</p>
      </div>
      <span class="rounded-full border border-emerald-200/60 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
        Delivery Ops Board
      </span>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <article class="column">
        <h3 class="column-title">Administracion</h3>
        <p class="column-sub">Asignaciones rapidas</p>
        <TransitionGroup name="ops" tag="div" class="space-y-2">
          <div v-for="order in store.sortedOrders.slice(0, 8)" :key="`ops-admin-${order.id}`" class="ticket">
            <div class="ticket-head">
              <strong>#{{ order.id }}</strong>
              <OrderStatusBadge :status="order.status" />
            </div>
            <p class="text-xs text-slate-500">{{ order.customer }}</p>
            <div class="mt-1 flex gap-2">
              <select class="select" :value="order.employeeId ?? ''" @change="store.assignEmployee(order.id, Number(($event.target as HTMLSelectElement).value) || null)">
                <option value="">Empleado</option>
                <option v-for="employee in store.employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
              </select>
              <select class="select" :value="order.driverId ?? ''" @change="store.assignDriver(order.id, Number(($event.target as HTMLSelectElement).value) || null)">
                <option value="">Repartidor</option>
                <option v-for="driver in store.drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
              </select>
            </div>
          </div>
        </TransitionGroup>
      </article>

      <article class="column">
        <h3 class="column-title">Cocina</h3>
        <p class="column-sub">Tomar y despachar</p>
        <TransitionGroup name="ops" tag="div" class="space-y-2">
          <div v-for="order in pending" :key="`ops-kitchen-${order.id}`" class="ticket">
            <div class="ticket-head">
              <strong>#{{ order.id }}</strong>
              <OrderStatusBadge :status="order.status" />
            </div>
            <p class="text-xs text-slate-500">{{ order.items.map((i) => `${i.qty}x ${store.getProduct(i.productId)?.name || 'Producto'}`).join(' | ') }}</p>
            <div class="mt-2 flex gap-2">
              <button class="btn-coral" @click="store.startPreparing(order.id, store.shiftEmployeeId)">Iniciar</button>
              <button class="btn-soft" @click="store.markReady(order.id)">Listo</button>
            </div>
          </div>
        </TransitionGroup>
      </article>

      <article class="column">
        <h3 class="column-title">Despacho</h3>
        <p class="column-sub">Ruta y cierre</p>
        <div class="mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
          <button class="btn-coral w-full" @click="store.takeRouteReadyOrders()">Tomar pedidos listos</button>
        </div>

        <TransitionGroup name="ops" tag="div" class="space-y-2">
          <div v-for="order in [...ready, ...onroute]" :key="`ops-route-${order.id}`" class="ticket">
            <div class="ticket-head">
              <strong>#{{ order.id }}</strong>
              <OrderStatusBadge :status="order.status" />
            </div>
            <p class="text-xs text-slate-500">{{ order.address }}</p>
            <div class="mt-2 flex gap-2">
              <button class="btn-coral" :disabled="order.status !== 'ready'" @click="store.leaveForDelivery(order.id, store.shiftDriverId)">
                En camino
              </button>
              <button class="btn-emerald" :disabled="order.status !== 'onroute'" @click="store.markDelivered(order.id)">
                Entregado
              </button>
            </div>
          </div>
        </TransitionGroup>
      </article>
    </div>
  </section>
</template>

<style scoped>
.column {
  border: 1px solid rgb(226 232 240 / 0.8);
  border-radius: 1rem;
  background: rgb(255 255 255 / 0.7);
  backdrop-filter: blur(8px);
  padding: 0.75rem;
}

.column-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: rgb(15 23 42);
}

.column-sub {
  margin: 0 0 0.5rem;
  color: rgb(100 116 139);
  font-size: 0.78rem;
}

.ticket {
  border: 1px solid rgb(226 232 240 / 0.85);
  border-radius: 0.9rem;
  background: rgb(255 255 255 / 0.8);
  box-shadow: 0 4px 12px rgb(15 23 42 / 0.05);
  padding: 0.6rem;
}

.ticket-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.4rem;
}

.select {
  border: 1px solid rgb(226 232 240 / 0.95);
  border-radius: 0.7rem;
  padding: 0.45rem 0.55rem;
  width: 100%;
  font-size: 0.77rem;
}

.btn-coral,
.btn-soft,
.btn-emerald {
  border-radius: 999px;
  padding: 0.45rem 0.75rem;
  font-size: 0.74rem;
  font-weight: 700;
}

.btn-coral {
  color: white;
  background: linear-gradient(145deg, #10b981, #059669);
}

.btn-soft {
  border: 1px solid rgb(226 232 240 / 0.95);
  background: white;
  color: rgb(71 85 105);
}

.btn-emerald {
  color: white;
  background: linear-gradient(145deg, #10b981, #059669);
}

.ops-enter-active,
.ops-leave-active {
  transition: all 500ms ease;
}

.ops-enter-from,
.ops-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
