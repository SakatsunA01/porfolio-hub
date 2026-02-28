<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Bike, CheckCircle2, ChefHat, Clock3, Flame, MessageCircle, Phone, Star } from 'lucide-vue-next'
import { useDeliveryStore } from '../stores/delivery'
import { useClientOrders } from '../composables/useClientOrders'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'
import ProfileMenu from '../components/common/ProfileMenu.vue'

const store = useDeliveryStore()
const { activeClientOrders, deliveredClientOrdersCount, statusLabelMap, clientOrders } = useClientOrders()
useOrdersRealtime()

const deliveredOrderSummary = computed(() => `${deliveredClientOrdersCount.value} pedidos entregados`)
const deliveredRecentOrders = computed(() => clientOrders.value.filter((order) => order.status === 'delivered').slice(0, 4))
const ratingOpenOrderId = ref<number | null>(null)
const feedbackByOrderId = reactive<Record<number, { rating: number; tags: string[]; notes: string }>>({})
const feedbackSavedMessage = ref('')

const trackingSteps = [
  { key: 'preparing', label: 'En cocina', icon: ChefHat, activeClass: 'bg-amber-100 text-amber-700', doneClass: 'bg-amber-500 text-white' },
  { key: 'onroute', label: 'En camino', icon: Bike, activeClass: 'bg-sky-100 text-sky-700', doneClass: 'bg-sky-500 text-white' },
  { key: 'delivered', label: 'Entregado', icon: CheckCircle2, activeClass: 'bg-emerald-100 text-emerald-700', doneClass: 'bg-emerald-500 text-white' },
]

const orderTrackingIndex = (status: string) => {
  if (status === 'received' || status === 'preparing' || status === 'ready') return 0
  if (status === 'onroute') return 1
  if (status === 'delivered') return 2
  return 0
}

const orderEtaLabel = (eta: number) => {
  const from = Math.max(5, Number(eta || 20) - 5)
  const to = Math.max(from + 5, Number(eta || 20))
  return `Llega en ${from}-${to} min`
}

const orderDriverName = (driverId: number | null) => {
  if (!driverId) return 'Repartidor asignado'
  const driver = store.drivers.find((item) => item.id === driverId)
  if (!driver?.name) return 'Repartidor asignado'
  const parts = driver.name.trim().split(' ')
  return parts.length > 1 ? `${parts[0]} ${parts[1][0]}.` : parts[0]
}

const orderDriverAvatar = (driverId: number | null) => {
  const name = orderDriverName(driverId) || 'Repartidor'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=0ea5e9&color=ffffff&bold=true`
}

const orderWhatsappLink = (orderId: number) => {
  const text = encodeURIComponent(`Hola, quiero consultar por mi pedido #ORD-${String(orderId).padStart(4, '0')}.`)
  return `https://wa.me/?text=${text}`
}

const orderPhoneLink = () => 'tel:+5491111111111'

const feedbackTags = ['Llego frio', 'Falto algo', 'Excelente sabor', 'Entrega rapida']

const feedbackModel = (orderId: number) => {
  if (!feedbackByOrderId[orderId]) {
    feedbackByOrderId[orderId] = { rating: 0, tags: [], notes: '' }
  }
  return feedbackByOrderId[orderId]
}

const toggleFeedbackTag = (orderId: number, tag: string) => {
  const model = feedbackModel(orderId)
  model.tags = model.tags.includes(tag) ? model.tags.filter((item) => item !== tag) : [...model.tags, tag]
}

const saveFeedback = (orderId: number) => {
  const model = feedbackModel(orderId)
  const key = `delivery-order-feedback-${orderId}`
  localStorage.setItem(key, JSON.stringify(model))
  ratingOpenOrderId.value = null
  feedbackSavedMessage.value = 'Gracias por tu feedback.'
  window.setTimeout(() => {
    if (feedbackSavedMessage.value) feedbackSavedMessage.value = ''
  }, 2200)
}
</script>

<template>
  <section class="rounded-[24px] bg-[#F9FAFB] p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)] md:p-6">
    <header class="mb-3 rounded-[20px] bg-white p-3 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="text-sm font-semibold text-slate-800">Estado de tu pedido</p>
          <p class="text-xs text-slate-500">Seguimiento visual en tiempo real.</p>
        </div>
        <ProfileMenu :orders-route="'/cliente/pedidos'" :profile-route="'/cliente/perfil'" />
      </div>
    </header>
    <p v-if="feedbackSavedMessage" class="mb-3 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
      {{ feedbackSavedMessage }}
    </p>

    <section v-if="activeClientOrders.length" class="rounded-[20px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
      <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pedidos entregados</p>
        <p class="text-xs font-semibold text-slate-700">{{ deliveredOrderSummary }}</p>
      </div>
      <p class="mt-1 text-[11px] text-slate-500">Tu contador se actualiza automaticamente cada vez que un pedido se entrega.</p>
      <div class="mt-3 space-y-3">
        <article
          v-for="order in activeClientOrders"
          :key="`tracking-order-${order.id}`"
          class="rounded-[18px] bg-slate-50 p-3 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold text-slate-700">#ORD-{{ String(order.id).padStart(4, '0') }}</p>
            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
              {{ statusLabelMap[order.status] || order.status }}
            </span>
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2">
            <div v-for="(step, index) in trackingSteps" :key="`${order.id}-${step.key}`" class="text-center">
              <div
                class="mx-auto grid h-9 w-9 place-items-center rounded-full text-xs font-semibold transition"
                :class="index <= orderTrackingIndex(order.status) ? step.doneClass : step.activeClass"
              >
                <component
                  :is="step.icon"
                  class="h-4 w-4"
                  :class="{
                    'driver-bob': step.key === 'onroute' && order.status === 'onroute',
                    'kitchen-fire': step.key === 'preparing' && ['received', 'preparing', 'ready'].includes(order.status),
                  }"
                />
              </div>
              <p class="mt-1 text-[10px]" :class="index <= orderTrackingIndex(order.status) ? 'text-slate-800' : 'text-slate-400'">
                {{ step.label }}
              </p>
            </div>
          </div>
          <div class="mt-3 rounded-xl bg-white px-3 py-2">
            <p class="inline-flex items-center gap-1 text-sm font-bold text-slate-900">
              <Clock3 class="h-4 w-4 text-emerald-600" />
              {{ orderEtaLabel(order.etaMin) }}
            </p>
          </div>
          <div v-if="order.status === 'onroute'" class="mt-2 rounded-xl bg-sky-50 px-3 py-2">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center overflow-hidden rounded-full bg-white text-[11px] font-bold text-sky-700">
                  <img :src="orderDriverAvatar(order.driverId)" :alt="orderDriverName(order.driverId)" class="h-full w-full object-cover" />
                </span>
                <p class="text-xs font-semibold text-sky-800">Tu pedido lo lleva {{ orderDriverName(order.driverId) }}</p>
              </div>
              <div class="flex items-center gap-1.5">
                <a :href="orderPhoneLink()" class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                  <Phone class="h-3.5 w-3.5" />
                  Llamar
                </a>
                <a
                  :href="orderWhatsappLink(order.id)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-sky-700"
                >
                  <MessageCircle class="h-3.5 w-3.5" />
                  Chat
                </a>
              </div>
            </div>
          </div>
          <div v-if="['received', 'preparing', 'ready'].includes(order.status)" class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
            <Flame class="h-3.5 w-3.5 kitchen-fire" />
            Preparando tu pedido
          </div>
        </article>
      </div>
    </section>

    <div v-else class="rounded-[20px] bg-white p-4 text-sm text-slate-500 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
      No tenes pedidos activos en este momento. Total entregados: <span class="font-semibold text-slate-700">{{ deliveredOrderSummary }}</span>.
    </div>

    <section v-if="deliveredRecentOrders.length" class="mt-4 rounded-[20px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
      <p class="text-sm font-semibold text-slate-900">Pedidos entregados recientemente</p>
      <div class="mt-3 space-y-3">
        <article v-for="order in deliveredRecentOrders" :key="`delivered-${order.id}`" class="rounded-[16px] bg-emerald-50 p-3">
          <p class="text-sm font-semibold text-emerald-800">Que lo disfrutes, {{ order.customer.split(' ')[0] || 'Cliente' }}!</p>
          <p class="mt-1 text-xs text-emerald-700">Pedido #ORD-{{ String(order.id).padStart(4, '0') }} entregado.</p>
          <button
            type="button"
            class="mt-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700"
            @click="ratingOpenOrderId = ratingOpenOrderId === order.id ? null : order.id"
          >
            Calificar pedido
          </button>
          <div v-if="ratingOpenOrderId === order.id" class="mt-3 rounded-xl bg-white p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Como estuvo tu experiencia?</p>
            <div class="mt-2 flex items-center gap-1.5">
              <button
                v-for="star in 5"
                :key="`rating-${order.id}-${star}`"
                type="button"
                class="rounded-full p-1.5 transition active:scale-[0.95]"
                @click="feedbackModel(order.id).rating = star"
              >
                <Star class="h-6 w-6" :class="star <= feedbackModel(order.id).rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300'" />
              </button>
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="tag in feedbackTags"
                :key="`${order.id}-tag-${tag}`"
                type="button"
                class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition"
                :class="feedbackModel(order.id).tags.includes(tag) ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600'"
                @click="toggleFeedbackTag(order.id, tag)"
              >
                {{ tag }}
              </button>
            </div>
            <textarea
              v-if="feedbackModel(order.id).rating > 0 && feedbackModel(order.id).rating <= 2"
              v-model="feedbackModel(order.id).notes"
              class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700"
              rows="3"
              placeholder="Lamentamos eso, que salio mal?"
            ></textarea>
            <div class="mt-2 flex justify-end">
              <button type="button" class="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white" @click="saveFeedback(order.id)">
                Guardar
              </button>
            </div>
          </div>
        </article>
      </div>
    </section>
  </section>
</template>

<style scoped>
.driver-bob {
  animation: driverBob 1.35s ease-in-out infinite;
}

@keyframes driverBob {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-1.5px);
  }
}

.kitchen-fire {
  animation: kitchenFire 1s ease-in-out infinite;
}

@keyframes kitchenFire {
  0%,
  100% {
    transform: scale(1) rotate(-3deg);
  }
  50% {
    transform: scale(1.08) rotate(3deg);
  }
}
</style>
