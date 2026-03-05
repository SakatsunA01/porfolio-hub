<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-40 bg-text-primary/30 backdrop-blur-[2px]" @click="$emit('close')" />
  </transition>

  <transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <aside
      v-if="open"
      class="fixed inset-x-0 bottom-0 z-50 max-h-[88vh] border-t border-bg-secondary bg-bg-primary/95 backdrop-blur-xl"
      aria-label="Mini cart"
    >
      <div class="axis-container py-5">
        <header class="mb-4 flex items-start justify-between gap-4">
          <div>
            <h2 class="font-serif text-2xl tracking-wide text-text-primary">Tu carrito</h2>
            <p class="mt-1 text-sm text-text-secondary">{{ items.length }} productos</p>
          </div>
          <button
            type="button"
            class="min-h-12 min-w-12 border border-bg-secondary px-3 py-2 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:border-text-secondary hover:text-text-primary"
            @click="$emit('close')"
          >
            Cerrar
          </button>
        </header>

        <div v-if="items.length === 0" class="border border-dashed border-bg-secondary bg-bg-secondary/30 px-4 py-8 text-center text-sm text-text-secondary">
          Tu carrito esta vacio.
        </div>

        <div v-else class="max-h-[48vh] space-y-4 overflow-y-auto pr-1">
          <article v-for="item in items" :key="item.id" class="flex items-start gap-3 border-b border-bg-secondary pb-4">
            <div class="h-14 w-14 shrink-0 overflow-hidden bg-bg-secondary">
              <img
                v-if="item.image"
                :src="resolveImage(item.image)"
                :alt="item.name"
                class="h-full w-full object-cover"
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="truncate text-sm tracking-wide text-text-primary">{{ item.name }}</p>
                  <p v-if="item.size" class="text-xs text-text-secondary">{{ item.size }}</p>
                </div>
                <p class="text-sm text-text-primary">{{ formatPrice(item.price * item.quantity) }}</p>
              </div>

              <div class="mt-3 flex items-center justify-between gap-3">
                <div class="inline-flex min-h-12 items-center border border-text-primary">
                  <button
                    type="button"
                    class="min-h-12 min-w-12 px-3 py-2 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                    @click="$emit('updateQuantity', item.id, item.quantity - 1)"
                  >
                    -
                  </button>
                  <span class="px-3 text-sm tracking-wide text-text-primary">{{ item.quantity }}</span>
                  <button
                    type="button"
                    class="min-h-12 min-w-12 px-3 py-2 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                    @click="$emit('updateQuantity', item.id, item.quantity + 1)"
                  >
                    +
                  </button>
                </div>

                <button
                  type="button"
                  class="min-h-12 px-2 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
                  @click="$emit('removeItem', item.id)"
                >
                  Quitar
                </button>
              </div>
            </div>
          </article>
        </div>

        <footer class="mt-5 border-t border-bg-secondary pt-4">
          <div class="mb-4 flex items-end justify-between gap-4">
            <p class="text-sm tracking-wide text-text-secondary">Total</p>
            <p class="font-serif text-3xl tracking-wide text-text-primary">{{ formatPrice(total) }}</p>
          </div>

          <button
            type="button"
            class="inline-flex min-h-12 w-full items-center justify-center bg-text-primary px-6 py-3 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90"
            @click="$emit('checkout')"
          >
            Finalizar compra
          </button>
        </footer>
      </div>
    </aside>
  </transition>
</template>

<script setup lang="ts">
import type { CartItem } from '@/stores/cart'

defineEmits<{
  close: []
  checkout: []
  removeItem: [id: string]
  updateQuantity: [id: string, quantity: number]
}>()

defineProps<{
  open: boolean
  items: CartItem[]
  total: number
}>()

const formatPrice = (value: number) =>
  `$${Math.round(value).toLocaleString('es-AR')}`

const resolveImage = (path: string) => {
  if (/^(https?:)?\//.test(path)) {
    return path
  }

  return new URL(path, import.meta.url).href
}
</script>
