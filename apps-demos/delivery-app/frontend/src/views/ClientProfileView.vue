<script setup lang="ts">
import { computed, ref } from 'vue'
import { PencilLine, Repeat2 } from 'lucide-vue-next'
import { useDeliveryStore } from '../stores/delivery'
import { useClientOrders } from '../composables/useClientOrders'
import { resolveAssetUrl } from '../utils/media'
import AppButton from '../components/common/AppButton.vue'
import AppModal from '../components/common/AppModal.vue'
import ProfileMenu from '../components/common/ProfileMenu.vue'

const store = useDeliveryStore()
const { clientOrders, statusLabelMap } = useClientOrders()

const profileName = computed(() => store.currentUser?.name || 'Cliente')
const profileEmail = computed(() => store.currentUser?.email || 'cliente@delivery.local')
const profileInitials = computed(() =>
  profileName.value
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join(''),
)
const recentOrders = computed(() => clientOrders.value.slice(0, 8))
const repeatConfirmOpen = ref(false)
const repeatResultMessage = ref('')
const selectedRepeatOrderId = ref<number | null>(null)

const statusPillClass = (status: string) => {
  if (status === 'delivered') return 'bg-emerald-100 text-emerald-700'
  if (status === 'canceled' || status === 'rejected') return 'bg-slate-200 text-slate-600'
  return 'bg-amber-100 text-amber-700'
}

const orderPreviewImage = (order: (typeof clientOrders.value)[number]) => {
  const first = order.items[0]
  if (!first?.productId) return null
  const image = store.getProduct(first.productId)?.imageUrl
  return resolveAssetUrl(image || null)
}

const orderDateLabel = (createdAt: number) =>
  new Date(createdAt).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: 'short',
  })

const selectedRepeatOrder = computed(() => {
  if (!selectedRepeatOrderId.value) return null
  return clientOrders.value.find((order) => order.id === selectedRepeatOrderId.value) || null
})

const openRepeatConfirm = (orderId: number) => {
  selectedRepeatOrderId.value = orderId
  repeatConfirmOpen.value = true
}

const processRepeatOrder = () => {
  const source = selectedRepeatOrder.value
  if (!source) return
  const ok = store.createOrder({
    customer: source.customer,
    address: source.address,
    paymentMethod: source.paymentMethod,
    paymentStatus: source.paymentStatus === 'refunded' ? 'pending' : source.paymentStatus,
    cashReceived: source.paymentMethod === 'cash' ? source.cashReceived : null,
    items: source.items.map((item) => ({
      productId: item.productId,
      qty: item.qty,
      name: item.name,
      excludedIngredientIds: item.excludedIngredientIds || [],
      extras: item.extras || [],
    })),
  })
  repeatConfirmOpen.value = false
  repeatResultMessage.value = ok ? 'Pedido repetido correctamente.' : 'No se pudo repetir el pedido.'
  window.setTimeout(() => {
    if (repeatResultMessage.value) repeatResultMessage.value = ''
  }, 2200)
}
</script>

<template>
  <section class="rounded-[24px] bg-[#F9FAFB] p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)] md:p-6">
    <header class="mb-3 rounded-[20px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
          <span class="grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700">{{ profileInitials }}</span>
          <div>
            <p class="text-lg font-bold text-slate-900">{{ profileName }}</p>
            <p class="text-xs text-slate-500">{{ profileEmail }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
            <PencilLine class="h-3.5 w-3.5" />
            Editar perfil
          </button>
          <ProfileMenu :orders-route="'/cliente/pedidos'" :profile-route="'/cliente/perfil'" />
        </div>
      </div>
    </header>

    <section class="space-y-3">
      <p v-if="repeatResultMessage" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
        {{ repeatResultMessage }}
      </p>
      <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-semibold text-slate-900">Pedidos recientes</p>
        <p class="text-xs text-slate-500">{{ recentOrders.length }} pedidos</p>
      </div>
      <div v-if="recentOrders.length" class="grid gap-3 md:grid-cols-2">
          <article
            v-for="order in recentOrders"
            :key="`profile-order-${order.id}`"
            class="rounded-[18px] bg-white p-3 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]"
          >
            <div class="flex items-start gap-3">
              <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-100 text-xs font-bold text-slate-500">
                <img v-if="orderPreviewImage(order)" :src="orderPreviewImage(order) || undefined" alt="Pedido" class="h-full w-full object-cover" />
                <span v-else>#{{ order.id }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-semibold text-slate-700">#ORD-{{ String(order.id).padStart(4, '0') }}</span>
                  <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusPillClass(order.status)">
                    {{ statusLabelMap[order.status] || order.status }}
                  </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ orderDateLabel(order.createdAt) }} | Total ${{ Number(order.total || 0).toFixed(2) }}</p>
                <p class="mt-1 truncate text-xs text-slate-500">{{ order.items.map((item) => `${item.qty}x ${item.name || 'Producto'}`).join(' | ') }}</p>
              </div>
            </div>
            <div class="mt-3 flex justify-end">
              <button
                type="button"
                @click="openRepeatConfirm(order.id)"
                class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-emerald-700 shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition active:scale-[0.98]"
                title="Repetir pedido"
              >
                <Repeat2 class="h-4 w-4" />
              </button>
            </div>
          </article>
      </div>
      <div v-else class="rounded-[18px] bg-white p-4 text-sm text-slate-500 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
        Todavia no tenes pedidos registrados.
      </div>
    </section>
    <AppModal :open="repeatConfirmOpen" max-width-class="max-w-md" @close="repeatConfirmOpen = false">
      <div class="space-y-3 p-1">
        <h3 class="text-base font-semibold text-slate-900">Repetir pedido</h3>
        <p class="text-sm text-slate-600">
          Queres repetir este pedido con la misma direccion y metodo de pago?
        </p>
        <p v-if="selectedRepeatOrder" class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
          {{ selectedRepeatOrder.items.map((item) => `${item.qty}x ${item.name || 'Producto'}`).join(' | ') }} - ${{ Number(selectedRepeatOrder.total || 0).toFixed(2) }}
        </p>
        <div class="flex justify-end gap-2">
          <AppButton variant="ghost" @click="repeatConfirmOpen = false">Cancelar</AppButton>
          <AppButton variant="primary" @click="processRepeatOrder">Si, repetir</AppButton>
        </div>
      </div>
    </AppModal>
  </section>
</template>
