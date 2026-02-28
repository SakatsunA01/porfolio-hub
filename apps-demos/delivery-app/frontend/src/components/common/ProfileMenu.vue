<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronDown, LogOut, ReceiptText, User } from 'lucide-vue-next'
import { useDeliveryStore } from '../../stores/delivery'

const props = withDefaults(
  defineProps<{
    profileRoute?: string | null
    ordersRoute?: string | null
    compact?: boolean
  }>(),
  {
    profileRoute: null,
    ordersRoute: null,
    compact: false,
  },
)

const store = useDeliveryStore()
const router = useRouter()
const menuRef = ref<HTMLElement | null>(null)
const open = ref(false)

const displayName = computed(() => store.currentUser?.name || 'Usuario')
const initials = computed(() => {
  const parts = (displayName.value || '')
    .split(' ')
    .map((part) => part.trim())
    .filter(Boolean)
    .slice(0, 2)
  const value = parts.map((part) => part.charAt(0).toUpperCase()).join('')
  return value || 'US'
})

const toggleMenu = () => {
  open.value = !open.value
}

const closeMenu = () => {
  open.value = false
}

const goProfile = () => {
  if (!props.profileRoute) return
  closeMenu()
  router.push(props.profileRoute)
}

const goOrders = () => {
  if (!props.ordersRoute) return
  closeMenu()
  router.push(props.ordersRoute)
}

const logout = async () => {
  closeMenu()
  await store.logout()
  router.push('/login')
}

const onDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null
  if (!menuRef.value || !target) return
  if (menuRef.value.contains(target)) return
  closeMenu()
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<template>
  <div v-if="store.isAuthenticated" ref="menuRef" class="relative">
    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:text-emerald-700 active:scale-[0.98]"
      :class="compact ? 'h-10 w-10 justify-center' : 'px-3 py-2 text-xs font-semibold'"
      @click.stop="toggleMenu"
    >
      <span
        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700"
        :class="compact ? 'h-6 w-6 text-[10px]' : ''"
      >
        {{ initials }}
      </span>
      <span v-if="!compact" class="max-w-[120px] truncate">{{ displayName }}</span>
      <ChevronDown v-if="!compact" class="h-3.5 w-3.5" />
    </button>

    <div v-if="open" class="absolute right-0 z-50 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
      <button
        v-if="ordersRoute"
        type="button"
        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100"
        @click="goOrders"
      >
        <ReceiptText class="h-4 w-4" />
        Mis pedidos
      </button>
      <button
        v-if="profileRoute"
        type="button"
        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100"
        @click="goProfile"
      >
        <User class="h-4 w-4" />
        Mi perfil
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700"
        @click="logout"
      >
        <LogOut class="h-4 w-4" />
        Cerrar sesion
      </button>
    </div>
  </div>
</template>
