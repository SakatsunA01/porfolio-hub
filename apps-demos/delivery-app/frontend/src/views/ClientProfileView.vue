<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Camera, MapPin, Package, PencilLine, Repeat2, Save, ShieldCheck, Truck, UserRound } from 'lucide-vue-next'
import { useDeliveryStore } from '../stores/delivery'
import { useClientOrders } from '../composables/useClientOrders'
import { resolveAssetUrl } from '../utils/media'
import AppButton from '../components/common/AppButton.vue'
import AppModal from '../components/common/AppModal.vue'
import ProfileMenu from '../components/common/ProfileMenu.vue'

const store = useDeliveryStore()
const { clientOrders, statusLabelMap } = useClientOrders()

const recentOrders = computed(() => clientOrders.value.slice(0, 8))
const deliveredOrdersCount = computed(() => clientOrders.value.filter((order) => order.status === 'delivered').length)
const repeatConfirmOpen = ref(false)
const repeatResultMessage = ref('')
const selectedRepeatOrderId = ref<number | null>(null)
const editMode = ref(false)
const savingProfile = ref(false)
const profileMessage = ref('')
const profileMessageTone = ref<'success' | 'error'>('success')
const photoInputRef = ref<HTMLInputElement | null>(null)

const profileForm = reactive({
  displayName: '',
  phone: '',
  lastAddress: '',
  apartment: '',
  addressReference: '',
  deliveryNotes: '',
  avatarUrl: null as string | null,
})

const syncProfileForm = () => {
  profileForm.displayName = store.clientProfile.displayName || store.currentUser?.name || 'Cliente'
  profileForm.phone = store.clientProfile.phone || ''
  profileForm.lastAddress = store.clientProfile.lastAddress || ''
  profileForm.apartment = store.clientProfile.apartment || ''
  profileForm.addressReference = store.clientProfile.addressReference || ''
  profileForm.deliveryNotes = store.clientProfile.deliveryNotes || ''
  profileForm.avatarUrl = store.clientProfile.avatarUrl || null
}

watch(
  () => store.clientProfile,
  () => {
    syncProfileForm()
  },
  { immediate: true, deep: true },
)

const profileName = computed(() => profileForm.displayName || store.currentUser?.name || 'Cliente')
const profileEmail = computed(() => store.clientProfile.email || store.currentUser?.email || 'cliente@delivery.local')
const profileAvatar = computed(() => profileForm.avatarUrl || null)
const profileInitials = computed(() =>
  profileName.value
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join(''),
)
const deliverySummary = computed(() => {
  const parts = [profileForm.lastAddress, profileForm.apartment, profileForm.addressReference].filter(Boolean)
  return parts.length ? parts.join(' | ') : 'Todavia no cargaste datos de entrega.'
})
const savedFieldsCount = computed(
  () =>
    [
      profileForm.phone,
      profileForm.lastAddress,
      profileForm.apartment,
      profileForm.addressReference,
      profileForm.deliveryNotes,
      profileForm.avatarUrl,
    ].filter((item) => Boolean(item)).length,
)

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

const openPhotoPicker = () => {
  photoInputRef.value?.click()
}

const onPhotoSelected = (event: Event) => {
  const target = event.target as HTMLInputElement | null
  const file = target?.files?.[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    profileMessage.value = 'Selecciona una imagen valida.'
    profileMessageTone.value = 'error'
    return
  }
  const reader = new FileReader()
  reader.onload = () => {
    profileForm.avatarUrl = typeof reader.result === 'string' ? reader.result : null
  }
  reader.readAsDataURL(file)
}

const removePhoto = () => {
  profileForm.avatarUrl = null
}

const startEditing = async () => {
  await store.fetchClientProfile()
  syncProfileForm()
  editMode.value = true
}

const cancelEditing = () => {
  syncProfileForm()
  editMode.value = false
}

const saveProfile = async () => {
  if (!profileForm.displayName.trim()) {
    profileMessage.value = 'Ingresa tu nombre para guardar el perfil.'
    profileMessageTone.value = 'error'
    return
  }
  savingProfile.value = true
  try {
    await store.updateClientProfile({
      displayName: profileForm.displayName,
      phone: profileForm.phone,
      lastAddress: profileForm.lastAddress,
      apartment: profileForm.apartment,
      addressReference: profileForm.addressReference,
      deliveryNotes: profileForm.deliveryNotes,
      avatarUrl: profileForm.avatarUrl,
    })
    profileMessage.value = 'Perfil actualizado correctamente.'
    profileMessageTone.value = 'success'
    editMode.value = false
  } catch {
    profileMessage.value = 'No se pudo guardar el perfil.'
    profileMessageTone.value = 'error'
  } finally {
    savingProfile.value = false
    window.setTimeout(() => {
      if (profileMessage.value) profileMessage.value = ''
    }, 2400)
  }
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
  <section class="rounded-[24px] bg-[#F6F8FB] p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)] md:p-6">
    <header class="mb-4 overflow-hidden rounded-[24px] bg-[linear-gradient(135deg,#0f172a_0%,#1d4ed8_52%,#38bdf8_100%)] p-4 text-white shadow-[0_18px_40px_rgba(15,23,42,0.18)] md:p-5">
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-4">
          <div class="relative">
            <div class="grid h-16 w-16 place-items-center overflow-hidden rounded-[22px] border border-white/20 bg-white/10 text-lg font-black">
              <img v-if="profileAvatar" :src="profileAvatar" alt="Avatar del cliente" class="h-full w-full object-cover" />
              <span v-else>{{ profileInitials }}</span>
            </div>
            <button
              type="button"
              class="absolute -bottom-2 -right-2 grid h-8 w-8 place-items-center rounded-full bg-white text-slate-800 shadow-[0_10px_30px_rgba(15,23,42,0.24)]"
              @click="openPhotoPicker"
            >
              <Camera class="h-4 w-4" />
            </button>
            <input ref="photoInputRef" type="file" accept="image/*" class="hidden" @change="onPhotoSelected" />
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">Panel de usuario</p>
            <h1 class="mt-1 text-xl font-black">{{ profileName }}</h1>
            <p class="text-sm text-white/80">{{ profileEmail }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-full bg-white/12 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur"
            @click="startEditing"
          >
            <PencilLine class="h-3.5 w-3.5" />
            {{ editMode ? 'Editando' : 'Editar perfil' }}
          </button>
          <ProfileMenu :orders-route="'/cliente/pedidos'" :profile-route="'/cliente/perfil'" />
        </div>
      </div>
      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-[18px] border border-white/12 bg-white/10 p-3 backdrop-blur">
          <p class="text-[11px] uppercase tracking-wide text-white/70">Pedidos</p>
          <p class="mt-1 text-2xl font-black">{{ clientOrders.length }}</p>
        </div>
        <div class="rounded-[18px] border border-white/12 bg-white/10 p-3 backdrop-blur">
          <p class="text-[11px] uppercase tracking-wide text-white/70">Entregados</p>
          <p class="mt-1 text-2xl font-black">{{ deliveredOrdersCount }}</p>
        </div>
        <div class="rounded-[18px] border border-white/12 bg-white/10 p-3 backdrop-blur">
          <p class="text-[11px] uppercase tracking-wide text-white/70">Datos guardados</p>
          <p class="mt-1 text-2xl font-black">{{ savedFieldsCount }}/6</p>
        </div>
      </div>
    </header>

    <p
      v-if="profileMessage"
      class="mb-3 rounded-xl px-3 py-2 text-xs font-semibold"
      :class="profileMessageTone === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
    >
      {{ profileMessage }}
    </p>
    <p v-if="repeatResultMessage" class="mb-3 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
      {{ repeatResultMessage }}
    </p>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
      <section class="space-y-4">
        <article class="rounded-[22px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-base font-bold text-slate-900">Datos personales</p>
              <p class="text-xs text-slate-500">Este perfil se usa para tus proximos pedidos.</p>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">
              <UserRound class="h-3.5 w-3.5" />
              Cliente
            </span>
          </div>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <label class="space-y-1">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</span>
              <input
                v-model="profileForm.displayName"
                :disabled="!editMode"
                type="text"
                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
              />
            </label>
            <label class="space-y-1">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telefono</span>
              <input
                v-model="profileForm.phone"
                :disabled="!editMode"
                type="text"
                placeholder="+54 9 11..."
                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
              />
            </label>
            <label class="space-y-1 md:col-span-2">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</span>
              <input
                :value="profileEmail"
                disabled
                type="email"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 outline-none"
              />
            </label>
          </div>
          <div v-if="editMode" class="mt-4 flex flex-wrap items-center justify-end gap-2">
            <button
              v-if="profileForm.avatarUrl"
              type="button"
              class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600"
              @click="removePhoto"
            >
              Quitar foto
            </button>
            <button type="button" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600" @click="cancelEditing">
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-full bg-sky-600 px-4 py-2 text-xs font-semibold text-white"
              :disabled="savingProfile"
              @click="saveProfile"
            >
              <Save class="h-3.5 w-3.5" />
              {{ savingProfile ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </article>

        <article class="rounded-[22px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-base font-bold text-slate-900">Entrega</p>
              <p class="text-xs text-slate-500">Deja lista tu direccion para pedir mas rapido.</p>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700">
              <Truck class="h-3.5 w-3.5" />
              Reparto
            </span>
          </div>
          <div class="mt-4 grid gap-3">
            <label class="space-y-1">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Direccion principal</span>
              <input
                v-model="profileForm.lastAddress"
                :disabled="!editMode"
                type="text"
                placeholder="Calle, numero, barrio"
                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
              />
            </label>
            <div class="grid gap-3 md:grid-cols-2">
              <label class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Depto / piso / casa</span>
                <input
                  v-model="profileForm.apartment"
                  :disabled="!editMode"
                  type="text"
                  placeholder="2B, Casa 4, Local..."
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
                />
              </label>
              <label class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Referencia</span>
                <input
                  v-model="profileForm.addressReference"
                  :disabled="!editMode"
                  type="text"
                  placeholder="Porton negro, esquina, torre..."
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
                />
              </label>
            </div>
            <label class="space-y-1">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Indicaciones para entrega</span>
              <textarea
                v-model="profileForm.deliveryNotes"
                :disabled="!editMode"
                rows="4"
                placeholder="Ej: tocar timbre dos veces, dejar en seguridad, llamar antes de subir."
                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400"
              ></textarea>
            </label>
          </div>
        </article>
      </section>

      <section class="space-y-4">
        <article class="rounded-[22px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
          <div class="flex items-center gap-2">
            <MapPin class="h-4 w-4 text-sky-600" />
            <p class="text-base font-bold text-slate-900">Ficha de entrega</p>
          </div>
          <p class="mt-3 rounded-[18px] bg-slate-50 px-4 py-3 text-sm text-slate-600">
            {{ deliverySummary }}
          </p>
          <div class="mt-4 space-y-3">
            <div class="rounded-[18px] border border-slate-200 p-3">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Telefono de contacto</p>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ profileForm.phone || 'Sin cargar' }}</p>
            </div>
            <div class="rounded-[18px] border border-slate-200 p-3">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Notas para el repartidor</p>
              <p class="mt-1 text-sm text-slate-700">{{ profileForm.deliveryNotes || 'Sin indicaciones especiales.' }}</p>
            </div>
            <div class="rounded-[18px] border border-emerald-200 bg-emerald-50 p-3">
              <div class="flex items-center gap-2 text-emerald-700">
                <ShieldCheck class="h-4 w-4" />
                <p class="text-sm font-semibold">Perfil listo para pedir</p>
              </div>
              <p class="mt-1 text-xs text-emerald-700">
                Cuando guardas estos datos, el panel queda preparado para proximos pedidos y seguimiento.
              </p>
            </div>
          </div>
        </article>

        <article class="rounded-[22px] bg-white p-4 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.05)]">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <Package class="h-4 w-4 text-slate-700" />
              <p class="text-base font-bold text-slate-900">Pedidos recientes</p>
            </div>
            <p class="text-xs text-slate-500">{{ recentOrders.length }} pedidos</p>
          </div>
          <div v-if="recentOrders.length" class="mt-3 space-y-3">
            <article
              v-for="order in recentOrders"
              :key="`profile-order-${order.id}`"
              class="rounded-[18px] bg-slate-50 p-3"
            >
              <div class="flex items-start gap-3">
                <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-white text-xs font-bold text-slate-500">
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
          <div v-else class="mt-3 rounded-[18px] bg-slate-50 p-4 text-sm text-slate-500">
            Todavia no tenes pedidos registrados.
          </div>
        </article>
      </section>
    </div>

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
