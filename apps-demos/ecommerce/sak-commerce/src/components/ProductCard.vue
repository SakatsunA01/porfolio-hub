<template>
  <div class="group flex h-full cursor-pointer flex-col">
    <router-link :to="`/product/${product.id}`" class="block">
      <div class="aspect-[4/5] overflow-hidden bg-bg-secondary">
        <img
          :src="getProductImageUrl(product.image)"
          :alt="product.name"
          class="h-full w-full object-cover transition duration-200 ease-out group-hover:opacity-90"
        />
      </div>

      <div class="mt-4 flex min-h-[4.5rem] flex-col justify-between space-y-1">
        <h3 class="text-sm tracking-wide text-text-primary">
          {{ product.name }}
        </h3>

        <p class="text-sm text-text-secondary">
          {{ formatPrice(product.price) }}
        </p>

        <span class="inline-block pt-1 text-xs tracking-wide text-accent-olive">
          Ver detalle
        </span>
      </div>
    </router-link>
  </div>
</template>

<script setup lang="ts">
import type { Product } from '../types/catalog'
import { formatPrice, getProductImageUrl } from '../utils/catalog'

defineProps<{
  product: Product
}>()
</script>
