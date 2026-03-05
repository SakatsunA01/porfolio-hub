<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Bike, Camera, Coins, MapPinned, PencilLine, Save, ShieldCheck, UserRound } from 'lucide-vue-next'
import { useDeliveryStore } from '../stores/delivery'
import DriverNavTabs from '../components/driver/DriverNavTabs.vue'
import { useOrdersRealtime } from '../composables/useOrdersRealtime'

const store = useDeliveryStore()
useOrdersRealtime()

const currentDriverId = computed(() => store.currentUser?.id || null)
const editMode = ref(false)
const savingProfile = ref(false)
const profileMessage = ref('')
const profileMessageTone = ref<'success' | 'error'>('success')
const photoInputRef = ref<HTMLInputElement | null>(null)

const profileForm = reactive({
  name: store.currentUser?.name || 'Repartidor',
  phone: store.currentUser?.phone || '',
  avatarUrl: store.currentUser?.avatarUrl || null as string | null,
})

const syncForm = () => {
  profileForm.name = store.currentUser?.name || 'Repartidor'
  profileForm.phone = store.currentUser?.phone || ''
  profileForm.avatarUrl = store.currentUser?.avatarUrl || null
}

const profileName = computed(() => profileForm.name || store.currentUser?.name || 'Repartidor')
const profileEmail = computed(() => store.currentUser?.email || 'repartidor@delivery.local')
const profileStatus = computed(() => {
  const driver = store.drivers.find((item) => item.id === currentDriverId.value)
  return driver?.active !== false ? 'Activo' : 'Inactivo'
})
const profileInitials = computed(() =>
  profileName.value
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('') || 'RP',
)

const driverOrders = computed(() => store.orders.filter((order) => order.driverId === currentDriverId.value))
const deliveredOrders = computed(() => driverOrders.value.filter((order) => order.status === 'delivered'))
const deliveredCount = computed(() => deliveredOrders.value.length)
const pendingCount = computed(() => driverOrders.value.filter((order) => ['ready', 'onroute'].includes(order.status)).length)
const collectedAmount = computed(() =>
  deliveredOrders.value
    .filter((order) => order.paymentMethod === 'cash')
    .reduce((sum, order) => sum + Number(order.total || 0), 0),
)
const routeOrders = computed(() => driverOrders.value.filter((order) => order.status === 'onroute').length)

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

const startEditing = () => {
  syncForm()
  editMode.value = true
}

const cancelEditing = () => {
  syncForm()
  editMode.value = false
}

const saveProfile = async () => {
  if (!profileForm.name.trim()) {
    profileMessage.value = 'Ingresa un nombre para guardar el perfil.'
    profileMessageTone.value = 'error'
    return
  }
  savingProfile.value = true
  try {
    await store.updateOwnProfile({
      name: profileForm.name,
      phone: profileForm.phone,
      avatarUrl: profileForm.avatarUrl,
    })
    profileMessage.value = 'Perfil actualizado correctamente.'
    profileMessageTone.value = 'success'
    editMode.value = false
    syncForm()
  } catch {
    profileMessage.value = 'No se pudo actualizar el perfil.'
    profileMessageTone.value = 'error'
  } finally {
    savingProfile.value = false
    window.setTimeout(() => {
      if (profileMessage.value) profileMessage.value = ''
    }, 2400)
  }
}
</script>

<template>
  <section class="relative space-y-4">
    <header class="forest-card relative z-30 overflow-visible p-4">
      <span class="forest-glow -right-8 -top-8"></span>
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Perfil del Repartidor</h2>
          <p class="text-sm text-slate-500">Datos de cuenta y resumen de actividad.</p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700"
          @click="startEditing"
        >
          <PencilLine class="h-3.5 w-3.5" />
          {{ editMode ? 'Editando' : 'Editar perfil' }}
        </button>
      </div>
      <DriverNavTabs />
    </header>

    <p
      v-if="profileMessage"
      class="rounded-xl px-3 py-2 text-xs font-semibold"
      :class="profileMessageTone === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
    >
      {{ profileMessage }}
    </p>

    <article class="forest-card p-4">
      <div class="grid gap-4 lg:grid-cols-[minmax(260px,320px)_minmax(0,1fr)]">
        <div class="rounded-[24px] bg-[linear-gradient(135deg,#0f172a_0%,#0f766e_55%,#22c55e_100%)] p-4 text-white shadow-[0_18px_40px_rgba(15,23,42,0.18)]">
          <div class="flex items-center gap-3">
            <div class="relative">
              <span class="grid h-16 w-16 place-items-center overflow-hidden rounded-[20px] border border-white/20 bg-white/10 text-lg font-black">
                <img v-if="profileForm.avatarUrl" :src="profileForm.avatarUrl" alt="Avatar repartidor" class="h-full w-full object-cover" />
                <span v-else>{{ profileInitials }}</span>
              </span>
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
              <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">Repartidor</p>
              <p class="mt-1 text-xl font-black">{{ profileName }}</p>
              <p class="text-sm text-white/80">{{ profileEmail }}</p>
            </div>
          </div>
          <div class="mt-4 rounded-[18px] border border-white/12 bg-white/10 p-3 backdrop-blur">
            <p class="text-[11px] uppercase tracking-wide text-white/70">Estado actual</p>
            <p class="mt-1 text-base font-bold">{{ profileStatus }}</p>
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <label class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Nombre</p>
            <input
              v-model="profileForm.name"
              :disabled="!editMode"
              class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 outline-none"
            />
          </label>
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
            <p class="mt-2 text-sm font-semibold text-slate-900">{{ profileEmail }}</p>
          </div>
          <label class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Telefono</p>
            <input
              v-model="profileForm.phone"
              :disabled="!editMode"
              class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 outline-none"
              placeholder="+54 9 11..."
            />
          </label>
          <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">ID repartidor</p>
            <p class="mt-2 text-sm font-semibold text-slate-900">{{ currentDriverId || 'Sin asignar' }}</p>
          </div>
        </div>
      </div>

      <div v-if="editMode" class="mt-4 flex flex-wrap justify-end gap-2">
        <button type="button" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600" @click="cancelEditing">
          Cancelar
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white"
          :disabled="savingProfile"
          @click="saveProfile"
        >
          <Save class="h-3.5 w-3.5" />
          {{ savingProfile ? 'Guardando...' : 'Guardar perfil' }}
        </button>
      </div>
    </article>

    <article class="forest-card p-4">
      <h3 class="text-sm font-semibold text-slate-900">Resumen</h3>
      <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
          <div class="flex items-center gap-2 text-amber-700">
            <MapPinned class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Pendientes</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ pendingCount }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3">
          <div class="flex items-center gap-2 text-emerald-700">
            <Bike class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Entregados</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ deliveredCount }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-3">
          <div class="flex items-center gap-2 text-sky-700">
            <Coins class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">Recaudado</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">${{ collectedAmount.toFixed(2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <div class="flex items-center gap-2 text-slate-600">
            <ShieldCheck class="h-4 w-4" />
            <p class="text-xs uppercase tracking-wide">En ruta</p>
          </div>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ routeOrders }}</p>
        </div>
      </div>
    </article>
  </section>
</template>
