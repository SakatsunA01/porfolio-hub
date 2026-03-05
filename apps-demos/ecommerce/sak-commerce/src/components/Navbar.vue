<template>
  <header class="border-b border-bg-secondary/70 bg-bg-primary/85 py-6 backdrop-blur-md">
    <div class="axis-container flex items-center justify-between gap-4">
      <router-link to="/" class="inline-flex items-center gap-3 font-serif text-base tracking-wide text-text-primary">
        <img v-if="storefrontSettings.settings.logo_url" :src="storefrontSettings.settings.logo_url" :alt="storefrontSettings.settings.name" class="h-8 w-8 rounded-full object-cover" />
        <span>{{ storefrontSettings.settings.name }}</span>
      </router-link>

      <nav class="hidden items-center gap-8 md:flex">
        <router-link
          v-for="item in navigationItems"
          :key="item.label"
          :to="item.to"
          class="text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-accent-olive"
        >
          {{ item.label }}
        </router-link>
      </nav>

      <div class="flex items-center gap-3">
        <button
          type="button"
          class="relative min-h-12 min-w-12 border border-accent-olive px-3 py-2 text-sm tracking-wide text-accent-olive transition duration-200 ease-out hover:opacity-90"
          @click="openCart"
        >
          Carrito
          <span
            v-if="cartCount > 0"
            class="absolute -right-2 -top-2 inline-flex min-h-6 min-w-6 items-center justify-center rounded-full bg-accent-olive px-1 text-[11px] text-bg-primary transition duration-200 ease-out"
          >
            {{ cartCount }}
          </span>
        </button>

        <UserMenu />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import UserMenu from './UserMenu.vue'
import { useCartStore } from '../stores/cart'
import { useCart } from '../composables/useCart'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'

const cartStore = useCartStore()
const { openCart } = useCart()
const storefrontSettings = useStorefrontSettingsStore()

const cartCount = computed(() => cartStore.totalItems)

const navigationItems = computed(() => [
  { label: 'Tienda', to: '/catalog' },
  { label: 'Filosofia', to: '/#philosophy' },
  { label: 'Ediciones', to: '/#editions' },
  { label: 'Contacto', to: '/contact' },
])
</script>
