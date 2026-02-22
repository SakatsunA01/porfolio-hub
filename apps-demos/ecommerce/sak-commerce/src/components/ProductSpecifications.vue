<template>
  <section class="bg-axis-primary pb-16 pt-8">
    <div class="axis-container grid max-w-7xl grid-cols-1 items-center gap-x-8 gap-y-12 lg:grid-cols-2">
      <div>
        <h2 class="text-3xl font-display font-bold tracking-tight text-axis-secondary sm:text-4xl">Especificaciones tecnicas</h2>
        <p class="mt-4 text-axis-tertiary">{{ product.description }}</p>

        <dl class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2">
          <div v-for="feature in product.details" :key="feature.name" class="rounded-xl border border-slate-200 bg-white/70 p-4">
            <dt class="text-sm font-semibold text-axis-secondary">{{ feature.name }}</dt>
            <dd class="mt-2 text-sm text-axis-tertiary">{{ feature.description }}</dd>
          </div>
        </dl>
      </div>

      <div class="grid grid-cols-2 grid-rows-2 gap-4 sm:gap-6">
        <img
          v-for="(image, index) in product.images.slice(0, 4)"
          :key="index"
          :src="getImageUrl(image)"
          :alt="product.name"
          class="h-full w-full rounded-xl border border-slate-200 bg-axis-neutral object-cover"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Product } from '../data/products';

defineProps<{ product: Product }>();

const getImageUrl = (path: string) => {
  return new URL(path, import.meta.url).href;
};
</script>
