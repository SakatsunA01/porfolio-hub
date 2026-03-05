<template>
  <div class="relative">
    <button
      v-if="!isAuthenticated"
      type="button"
      class="inline-flex items-center justify-center rounded-[16px] border border-bg-secondary p-3 text-text-secondary transition duration-200 ease-out hover:text-text-primary"
      @click="router.push({ name: 'login' })"
    >
      <span class="sr-only">Ingresar</span>
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="1.2" />
        <path d="M4 21C4 17.6863 7.58172 15 12 15C16.4183 15 20 17.6863 20 21" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
      </svg>
    </button>

    <template v-else>
      <button
        type="button"
        class="inline-flex items-center gap-3 rounded-[16px] border border-bg-secondary px-3 py-2 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
        @click="open = !open"
      >
        <img
          v-if="user?.profile_photo_url"
          :src="user.profile_photo_url"
          :alt="user.name"
          class="h-8 w-8 rounded-full object-cover"
        />
        <span
          v-else
          class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-bg-secondary text-xs text-text-primary"
        >
          {{ initials }}
        </span>
      </button>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-out"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div
          v-if="open"
          class="absolute right-0 z-40 mt-3 min-w-56 rounded-[16px] border border-bg-secondary bg-white/95 p-3 backdrop-blur-sm"
        >
          <div class="space-y-1">
            <router-link
              to="/account"
              class="block rounded-[12px] px-4 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:bg-bg-secondary/60 hover:text-text-primary"
              @click="open = false"
            >
              Mi cuenta
            </router-link>

            <router-link
              v-if="isAdmin"
              to="/admin/dashboard"
              class="block rounded-[12px] px-4 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:bg-bg-secondary/60 hover:text-text-primary"
              @click="open = false"
            >
              Panel Admin
            </router-link>
          </div>

          <div class="my-2 border-t border-bg-secondary"></div>

          <button
            type="button"
            class="block w-full rounded-[12px] px-4 py-3 text-left text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:bg-bg-secondary/60 hover:text-text-primary"
            @click="handleLogout"
          >
            Cerrar sesión
          </button>
        </div>
      </Transition>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { useToast } from '../composables/useToast'

const authStore = useAuthStore()
const cartStore = useCartStore()
const router = useRouter()
const { showToast } = useToast()

const open = ref(false)

const user = computed(() => authStore.user)
const isAuthenticated = computed(() => authStore.isAuthenticated)
const isAdmin = computed(() => authStore.isAdmin)
const initials = computed(() =>
  user.value?.name
    ?.split(' ')
    .map((chunk) => chunk[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'US',
)

const handleLogout = async () => {
  open.value = false
  await authStore.logout()
  cartStore.clearCart()
  window.localStorage.removeItem('shoppingCart')
  router.push({ name: 'home' })
  showToast('Sesión cerrada correctamente.')
}
</script>
