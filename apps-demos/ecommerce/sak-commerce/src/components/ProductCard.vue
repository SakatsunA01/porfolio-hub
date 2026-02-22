<template>
  <article class="group relative overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
    <div class="aspect-square w-full overflow-hidden bg-axis-neutral">
      <img :src="getImageUrl(product.image)" :alt="product.name" class="h-full w-full object-cover object-center transition duration-300 group-hover:scale-[1.03]" />
    </div>
    <div class="space-y-2 p-4">
      <p class="text-xs font-semibold uppercase tracking-[0.08em] text-axis-tertiary">{{ product.category }}</p>
      <div class="flex items-start justify-between gap-3">
      <div>
          <h3 class="text-sm font-semibold text-axis-secondary">
          <router-link :to="`/product/${product.id}`">
            <span aria-hidden="true" class="absolute inset-0" />
            {{ product.name }}
          </router-link>
        </h3>
      </div>
        <p class="text-sm font-semibold text-axis-secondary">{{ formatCurrency(product.price) }}</p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';
import type { Product } from '../data/products';

defineProps<{ product: Product }>();

const getImageUrl = (path: string) => {
  return new URL(path, import.meta.url).href;
};

const formatCurrency = (value: number) =>
  new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'USD' }).format(value);
</script>
