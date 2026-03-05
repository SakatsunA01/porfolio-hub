<template>
  <nav
    v-if="isVisible"
    class="fixed inset-x-0 bottom-0 z-30 border-t border-bg-secondary bg-bg-primary/95 backdrop-blur-sm md:hidden"
  >
    <ul class="grid grid-cols-5">
      <li>
        <router-link to="/" class="flex min-h-14 flex-col items-center justify-center text-xs tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary">
          Inicio
        </router-link>
      </li>
      <li>
        <router-link to="/catalog" class="flex min-h-14 flex-col items-center justify-center text-xs tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary">
          Buscar
        </router-link>
      </li>
      <li>
        <router-link to="/favorites" class="flex min-h-14 flex-col items-center justify-center text-xs tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary">
          Favoritos
        </router-link>
      </li>
      <li>
        <button
          type="button"
          class="relative flex min-h-14 w-full flex-col items-center justify-center text-xs tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
          @click="openCart"
        >
          Carrito
          <span v-if="cartCount > 0" class="absolute right-4 top-2 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-text-primary px-1 text-[10px] text-bg-primary">
            {{ cartCount }}
          </span>
        </button>
      </li>
      <li>
        <router-link to="/account" class="flex min-h-14 flex-col items-center justify-center text-xs tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary">
          Mas
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useCart } from '../composables/useCart'

const route = useRoute()
const cartStore = useCartStore()
const { openCart } = useCart()

const cartCount = computed(() => cartStore.totalItems)
const isVisible = computed(() => !['/checkout'].includes(route.path) && !route.path.startsWith('/admin'))
</script>
