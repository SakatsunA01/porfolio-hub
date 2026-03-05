<template>
  <section class="min-h-screen bg-bg-secondary py-section-sm">
    <div class="mx-auto max-w-container px-6">
      <div class="mb-12">
        <p class="text-sm tracking-wide text-text-secondary">Selección</p>
        <h1 class="mt-3 font-serif text-4xl tracking-wide text-text-primary">
          Tu carrito
        </h1>
      </div>

      <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16">
        <div class="space-y-6">
          <template v-if="items.length === 0">
            <div
              v-for="index in 3"
              :key="index"
              class="rounded-[24px] bg-bg-primary p-6"
            >
              <div class="animate-pulse space-y-4">
                <div class="h-5 w-32 bg-bg-secondary"></div>
                <div class="h-4 w-48 bg-bg-secondary"></div>
                <div class="h-24 w-full bg-bg-secondary"></div>
              </div>
            </div>
          </template>

          <article
            v-for="item in items"
            :key="item.id"
            class="rounded-[24px] bg-bg-primary p-6"
          >
            <div class="flex flex-col gap-5 sm:flex-row">
              <div class="aspect-square w-full max-w-[5.5rem] shrink-0 overflow-hidden rounded-[20px] bg-bg-secondary sm:w-[5.5rem]">
                <img
                  v-if="item.image"
                  :src="resolveImage(item.image)"
                  :alt="item.name"
                  class="h-full w-full object-cover"
                />
              </div>

              <div class="flex-1">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h2 class="font-serif text-xl tracking-wide text-text-primary">
                      {{ item.name }}
                    </h2>
                    <p v-if="item.size" class="mt-1 text-sm text-text-secondary">
                      {{ item.size }}
                    </p>
                  </div>
                  <p class="text-base text-text-primary">
                    {{ formatPrice(item.price * item.quantity) }}
                  </p>
                </div>

                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                  <div class="inline-flex w-fit items-center rounded-[24px] border border-text-primary">
                    <button
                      type="button"
                      class="px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                      @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                    >
                      -
                    </button>
                    <span class="px-4 py-3 text-sm tracking-wide text-text-primary">
                      {{ item.quantity }}
                    </span>
                    <button
                      type="button"
                      class="px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                      @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                    >
                      +
                    </button>
                  </div>

                  <button
                    type="button"
                    class="text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
                    @click="cartStore.removeItem(item.id)"
                  >
                    Quitar
                  </button>
                </div>
              </div>
            </div>
          </article>
        </div>

        <aside class="lg:sticky lg:top-8 lg:self-start">
          <div class="rounded-[24px] bg-bg-primary p-6">
            <p class="text-sm tracking-wide text-text-secondary">Resumen</p>
            <div class="mt-8 space-y-4">
              <div class="flex items-center justify-between text-sm text-text-secondary">
                <span>Productos</span>
                <span>{{ cartStore.totalItems }}</span>
              </div>
              <div class="flex items-center justify-between text-sm text-text-secondary">
                <span>Subtotal</span>
                <span>{{ formatPrice(cartStore.totalPrice) }}</span>
              </div>
            </div>

            <div class="mt-8 border-t border-bg-secondary pt-6">
              <div class="flex items-end justify-between gap-4">
                <span class="text-sm tracking-wide text-text-secondary">Total</span>
                <span class="font-serif text-3xl tracking-wide text-text-primary">
                  {{ formatPrice(cartStore.totalPrice) }}
                </span>
              </div>
            </div>

            <router-link to="/checkout" class="block">
              <BaseButton full-width class="mt-8">
                Continuar a checkout
              </BaseButton>
            </router-link>
          </div>
        </aside>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from '../components/BaseButton.vue'
import { useCartStore } from '../stores/cart'

const cartStore = useCartStore()

const items = computed(() => cartStore.items)

const formatPrice = (value: number) =>
  `$${Math.round(value).toLocaleString('es-AR')}`

const resolveImage = (path: string) => {
  if (/^(https?:)?\//.test(path)) {
    return path
  }

  return new URL(path, import.meta.url).href
}
</script>
