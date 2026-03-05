<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, ChefHat, PlayCircle, ReceiptText, TimerReset } from 'lucide-vue-next'
import StatusBadge from '../components/common/StatusBadge.vue'
import AppButton from '../components/common/AppButton.vue'
import { useDeliveryStore, type Order, type OrderItem } from '../stores/delivery'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import EmployeeNavTabs from '../components/employee/EmployeeNavTabs.vue'

const store = useDeliveryStore()
useOrdersRealtime()

const kitchenOrders = computed(() =>
  [...store.orders]
    .filter((order) => ['received', 'preparing'].includes(order.status))
    .sort((a, b) => {
      if (a.status !== b.status) return a.status === 'received' ? -1 : 1
      return a.createdAt - b.createdAt
    }),
)
const pendingOrders = computed(() => kitchenOrders.value.filter((order) => order.status === 'received'))
const preparingOrders = computed(() => kitchenOrders.value.filter((order) => order.status === 'preparing'))
const urgentPendingOrders = computed(() => pendingOrders.value.filter((order) => waitingMinutes(order.createdAt) >= 18))
const regularPendingOrders = computed(() => pendingOrders.value.filter((order) => waitingMinutes(order.createdAt) < 18))
const myKitchenOrders = computed(() => preparingOrders.value.filter((order) => order.employeeId === store.currentUser?.id))
const otherKitchenOrders = computed(() => preparingOrders.value.filter((order) => order.employeeId !== store.currentUser?.id))
const businessLabel = computed(() => store.activeStorefrontName || 'Dunamis Store')
const kitchenSummary = computed(() => ({
  pending: pendingOrders.value.length,
  preparing: preparingOrders.value.length,
}))
const loggedEmployeeName = computed(() => store.currentUser?.name || 'Empleado actual')

const waitingBadgeClass = (createdAt: number) => {
  const mins = Math.round((Date.now() - createdAt) / 60000)
  if (mins >= 30) return 'text-rose-700 bg-rose-100 border-rose-200'
  if (mins >= 18) return 'text-amber-700 bg-amber-100 border-amber-200'
  return 'text-emerald-700 bg-emerald-100 border-emerald-200'
}

const waitingMinutes = (createdAt: number) => Math.max(0, Math.round((Date.now() - createdAt) / 60000))

const orderCreatedAtLabel = (createdAt: number) =>
  new Intl.DateTimeFormat('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(createdAt))

const ingredientNames = (ids: number[]) =>
  ids
    .map((id) => store.getIngredient(id)?.name || '')
    .filter(Boolean)

const itemAdjustments = (item: OrderItem) => {
  const removed = ingredientNames(item.excludedIngredientIds || [])
  const extras = (item.extras || []).map((extra) => extra.name).filter(Boolean)
  return [
    removed.length ? `Sin ${removed.join(', ')}` : '',
    extras.length ? `+ ${extras.join(', ')}` : '',
  ].filter(Boolean)
}

const orderItemsCount = (order: Order) => order.items.reduce((acc, item) => acc + Math.max(1, Number(item.qty || 0)), 0)
const isTakenByLoggedEmployee = (order: Order) => order.status === 'preparing' && order.employeeId === store.currentUser?.id
const canStartOrder = (order: Order) => order.status === 'received' && !!store.currentUser?.id && (order.employeeId === null || order.employeeId === store.currentUser.id)
const canFinishOrder = (order: Order) => order.status === 'preparing' && order.employeeId === store.currentUser?.id
const orderPriorityLabel = (createdAt: number) => {
  const mins = waitingMinutes(createdAt)
  if (mins >= 30) return 'Urgente'
  if (mins >= 18) return 'Alta'
  return 'Normal'
}

const orderPriorityClass = (createdAt: number) => {
  const mins = waitingMinutes(createdAt)
  if (mins >= 30) return 'bg-rose-100 text-rose-700'
  if (mins >= 18) return 'bg-amber-100 text-amber-700'
  return 'bg-emerald-100 text-emerald-700'
}

const orderCardClass = (order: Order) => {
  if (isTakenByLoggedEmployee(order)) return 'ring-sky-300 bg-sky-50/30'
  if (order.status === 'received' && waitingMinutes(order.createdAt) >= 30) return 'ring-rose-300 bg-rose-50/30'
  if (order.status === 'received' && waitingMinutes(order.createdAt) >= 18) return 'ring-amber-300 bg-amber-50/30'
  if (order.status === 'preparing') return 'ring-slate-300 bg-white'
  return 'ring-emerald-200 bg-white'
}
</script>

<template>
  <article class="forest-card relative overflow-hidden rounded-[24px] p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
    <span class="forest-glow -right-8 -top-8"></span>
    <h2 class="text-lg font-bold text-slate-900">{{ businessLabel }}</h2>
    <p class="mt-1 text-sm text-slate-500">Cola de preparacion y prioridades por demora.</p>
    <EmployeeNavTabs />

    <div class="mt-3 grid gap-3 sm:grid-cols-2">
      <div class="rounded-[20px] border border-amber-200 bg-amber-50 p-3">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-amber-700">Por tomar</p>
        <p class="mt-1 text-2xl font-black text-amber-900">{{ kitchenSummary.pending }}</p>
        <p class="text-xs text-amber-700">Pedidos recibidos esperando inicio.</p>
      </div>
      <div class="rounded-[20px] border border-sky-200 bg-sky-50 p-3">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">En preparacion</p>
        <p class="mt-1 text-2xl font-black text-sky-900">{{ kitchenSummary.preparing }}</p>
        <p class="text-xs text-sky-700">Pedidos ya tomados por cocina.</p>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-[20px] bg-slate-50 p-3 ring-1 ring-slate-200">
      <span class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Cocina activa</span>
      <span class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
        {{ loggedEmployeeName }}
      </span>
    </div>

    <div v-if="!kitchenOrders.length" class="mt-3 rounded-[20px] bg-slate-50 p-4 text-sm text-slate-500 ring-1 ring-slate-200">
      Sin pedidos en cocina por ahora.
    </div>

    <div v-else class="mt-3 space-y-4">
      <section v-if="urgentPendingOrders.length" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Prioridad alta</p>
          <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
            {{ urgentPendingOrders.length }} pendientes
          </span>
        </div>
        <TransitionGroup name="kitchen" tag="div" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="order in urgentPendingOrders"
            :key="`kitchen-urgent-${order.id}`"
            class="flex min-h-[360px] flex-col rounded-[20px] p-3 shadow-sm ring-1"
            :class="orderCardClass(order)"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
                <StatusBadge :value="'Pendiente'" />
              </div>
              <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
                {{ waitingMinutes(order.createdAt) }} min en espera
              </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
              <span class="rounded-full px-2.5 py-1 font-semibold" :class="orderPriorityClass(order.createdAt)">
                Prioridad {{ orderPriorityLabel(order.createdAt) }}
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                {{ orderItemsCount(order) }} items
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                Ingreso {{ orderCreatedAtLabel(order.createdAt) }}
              </span>
            </div>

            <div class="mt-3 rounded-[18px] bg-slate-50 p-3">
              <div class="mb-2 flex items-center gap-2">
                <ReceiptText class="h-4 w-4 text-slate-500" />
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Detalle para cocina</p>
              </div>
              <div class="space-y-2">
                <div
                  v-for="(item, index) in order.items"
                  :key="`${order.id}-${item.productId}-${index}`"
                  class="rounded-2xl bg-white px-3 py-2 ring-1 ring-slate-200"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-800">
                        {{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}
                      </p>
                      <p v-if="itemAdjustments(item).length" class="mt-1 text-xs font-medium text-amber-700">
                        {{ itemAdjustments(item).join(' | ') }}
                      </p>
                      <p v-else class="mt-1 text-xs text-slate-400">Sin cambios.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                      Item {{ index + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-3 grid gap-2">
              <div class="rounded-[18px] border border-slate-200 p-3 text-xs text-slate-600">
                <div class="mb-1 flex items-center gap-1.5 font-semibold text-slate-700">
                  <ChefHat class="h-3.5 w-3.5" />
                  Foco de preparacion
                </div>
                <p>Revisa cantidades, cambios de ingredientes y extras antes de iniciar.</p>
              </div>
              <div class="rounded-[18px] border border-slate-200 p-3 text-xs text-slate-600">
                <div class="mb-1 flex items-center gap-1.5 font-semibold text-slate-700">
                  <TimerReset class="h-3.5 w-3.5" />
                  Operacion
                </div>
                <p>Asignado: {{ order.employeeId ? store.getEmployee(order.employeeId)?.name : 'Sin asignar' }}</p>
                <p class="mt-1">ETA actual: {{ order.etaMin }} min</p>
              </div>
            </div>

            <div class="mt-auto pt-3 flex flex-col gap-2">
              <AppButton
                class="w-full"
                variant="primary"
                :disabled="!canStartOrder(order)"
                @click="store.startPreparing(order.id, store.currentUser?.id || null)"
              >
                <span class="inline-flex items-center gap-1.5">
                  <PlayCircle class="h-4 w-4" />
                  Tomar / Iniciar
                </span>
              </AppButton>
              <AppButton class="w-full" variant="soft" :disabled="true">
                <span class="inline-flex items-center gap-1.5">
                  <CheckCircle2 class="h-4 w-4" />
                  Pedido terminado
                </span>
              </AppButton>
            </div>
          </div>
        </TransitionGroup>
      </section>

      <section v-if="regularPendingOrders.length" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Pendientes</p>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
            {{ regularPendingOrders.length }} en cola
          </span>
        </div>
        <TransitionGroup name="kitchen" tag="div" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="order in regularPendingOrders"
            :key="`kitchen-regular-${order.id}`"
            class="flex min-h-[320px] flex-col rounded-[20px] p-3 shadow-sm ring-1"
            :class="orderCardClass(order)"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
                <StatusBadge :value="'Pendiente'" />
              </div>
              <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
                {{ waitingMinutes(order.createdAt) }} min en espera
              </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
              <span class="rounded-full px-2.5 py-1 font-semibold" :class="orderPriorityClass(order.createdAt)">
                Prioridad {{ orderPriorityLabel(order.createdAt) }}
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                {{ orderItemsCount(order) }} items
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                Ingreso {{ orderCreatedAtLabel(order.createdAt) }}
              </span>
            </div>

            <div class="mt-3 rounded-[18px] bg-slate-50 p-3">
              <div class="mb-2 flex items-center gap-2">
                <ReceiptText class="h-4 w-4 text-slate-500" />
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Detalle para cocina</p>
              </div>
              <div class="space-y-2">
                <div
                  v-for="(item, index) in order.items"
                  :key="`${order.id}-${item.productId}-${index}`"
                  class="rounded-2xl bg-white px-3 py-2 ring-1 ring-slate-200"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-800">
                        {{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}
                      </p>
                      <p v-if="itemAdjustments(item).length" class="mt-1 text-xs font-medium text-amber-700">
                        {{ itemAdjustments(item).join(' | ') }}
                      </p>
                      <p v-else class="mt-1 text-xs text-slate-400">Sin cambios.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                      Item {{ index + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-auto pt-3 flex flex-col gap-2">
              <AppButton
                class="w-full"
                variant="primary"
                :disabled="!canStartOrder(order)"
                @click="store.startPreparing(order.id, store.currentUser?.id || null)"
              >
                <span class="inline-flex items-center gap-1.5">
                  <PlayCircle class="h-4 w-4" />
                  Tomar / Iniciar
                </span>
              </AppButton>
              <AppButton class="w-full" variant="soft" :disabled="true">
                <span class="inline-flex items-center gap-1.5">
                  <CheckCircle2 class="h-4 w-4" />
                  Pedido terminado
                </span>
              </AppButton>
            </div>
          </div>
        </TransitionGroup>
      </section>

      <section v-if="myKitchenOrders.length" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">En tus manos</p>
          <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
            {{ myKitchenOrders.length }} preparando
          </span>
        </div>
        <TransitionGroup name="kitchen" tag="div" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="order in myKitchenOrders"
            :key="`kitchen-mine-${order.id}`"
            class="flex min-h-[360px] flex-col rounded-[20px] p-3 shadow-sm ring-1"
            :class="orderCardClass(order)"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
                <StatusBadge :value="'En Cocina'" />
              </div>
              <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
                {{ waitingMinutes(order.createdAt) }} min en espera
              </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
              <span class="rounded-full px-2.5 py-1 font-semibold" :class="orderPriorityClass(order.createdAt)">
                Prioridad {{ orderPriorityLabel(order.createdAt) }}
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                {{ orderItemsCount(order) }} items
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                Ingreso {{ orderCreatedAtLabel(order.createdAt) }}
              </span>
            </div>

            <div class="mt-3 rounded-[18px] bg-slate-50 p-3">
              <div class="mb-2 flex items-center gap-2">
                <ReceiptText class="h-4 w-4 text-slate-500" />
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Detalle para cocina</p>
              </div>
              <div class="space-y-2">
                <div
                  v-for="(item, index) in order.items"
                  :key="`${order.id}-${item.productId}-${index}`"
                  class="rounded-2xl bg-white px-3 py-2 ring-1 ring-slate-200"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-800">
                        {{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}
                      </p>
                      <p v-if="itemAdjustments(item).length" class="mt-1 text-xs font-medium text-amber-700">
                        {{ itemAdjustments(item).join(' | ') }}
                      </p>
                      <p v-else class="mt-1 text-xs text-slate-400">Sin cambios.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                      Item {{ index + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="mt-3 rounded-[18px] border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-800"
            >
              Pedido tomado por vos. Queda bloqueado hasta marcarlo como listo.
            </div>

            <div class="mt-auto pt-3 flex flex-col gap-2">
              <AppButton class="w-full" variant="primary" :disabled="true">
                <span class="inline-flex items-center gap-1.5">
                  <PlayCircle class="h-4 w-4" />
                  Tomar / Iniciar
                </span>
              </AppButton>
              <AppButton class="w-full" variant="soft" :disabled="!canFinishOrder(order)" @click="store.markReady(order.id)">
                <span class="inline-flex items-center gap-1.5">
                  <CheckCircle2 class="h-4 w-4" />
                  Pedido terminado
                </span>
              </AppButton>
            </div>
          </div>
        </TransitionGroup>
      </section>

      <section v-if="otherKitchenOrders.length" class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Tomados por otro cocinero</p>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
            {{ otherKitchenOrders.length }} en proceso
          </span>
        </div>
        <TransitionGroup name="kitchen" tag="div" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="order in otherKitchenOrders"
            :key="`kitchen-other-${order.id}`"
            class="flex min-h-[320px] flex-col rounded-[20px] p-3 shadow-sm ring-1"
            :class="orderCardClass(order)"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <p class="font-bold text-slate-900">#{{ order.id }} | {{ order.customer }}</p>
                <StatusBadge :value="'En Cocina'" />
              </div>
              <span class="rounded-full border px-2 py-1 text-[11px] font-semibold" :class="waitingBadgeClass(order.createdAt)">
                {{ waitingMinutes(order.createdAt) }} min en espera
              </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
              <span class="rounded-full px-2.5 py-1 font-semibold" :class="orderPriorityClass(order.createdAt)">
                Prioridad {{ orderPriorityLabel(order.createdAt) }}
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                {{ orderItemsCount(order) }} items
              </span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                {{ store.getEmployee(order.employeeId || 0)?.name || 'Otro cocinero' }}
              </span>
            </div>

            <div class="mt-3 rounded-[18px] bg-slate-50 p-3">
              <div class="mb-2 flex items-center gap-2">
                <ReceiptText class="h-4 w-4 text-slate-500" />
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Detalle para cocina</p>
              </div>
              <div class="space-y-2">
                <div
                  v-for="(item, index) in order.items"
                  :key="`${order.id}-${item.productId}-${index}`"
                  class="rounded-2xl bg-white px-3 py-2 ring-1 ring-slate-200"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-800">
                        {{ item.qty }}x {{ item.name || store.getProduct(item.productId)?.name || 'Producto' }}
                      </p>
                      <p v-if="itemAdjustments(item).length" class="mt-1 text-xs font-medium text-amber-700">
                        {{ itemAdjustments(item).join(' | ') }}
                      </p>
                      <p v-else class="mt-1 text-xs text-slate-400">Sin cambios.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                      Item {{ index + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-3 rounded-[18px] border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700">
              Este pedido ya fue tomado por otro cocinero.
            </div>

            <div class="mt-auto pt-3 flex flex-col gap-2">
              <AppButton class="w-full" variant="primary" :disabled="true">
                <span class="inline-flex items-center gap-1.5">
                  <PlayCircle class="h-4 w-4" />
                  Tomar / Iniciar
                </span>
              </AppButton>
              <AppButton class="w-full" variant="soft" :disabled="true">
                <span class="inline-flex items-center gap-1.5">
                  <CheckCircle2 class="h-4 w-4" />
                  Pedido terminado
                </span>
              </AppButton>
            </div>
          </div>
        </TransitionGroup>
      </section>
    </div>
  </article>
</template>

<style scoped>
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
