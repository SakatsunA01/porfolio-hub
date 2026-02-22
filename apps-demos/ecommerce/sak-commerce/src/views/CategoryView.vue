<template>
  <section class="bg-axis-primary py-12">
    <div class="axis-container">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm font-medium uppercase tracking-[0.14em] text-slate-500">Catalogo</p>
          <h1 class="mt-2 text-4xl font-display font-bold text-axis-secondary">{{ categoryName }}</h1>
          <p class="mt-2 text-sm text-slate-500">{{ sortedProducts.length }} productos disponibles</p>
        </div>

        <div class="flex items-center gap-3">
          <label for="sort" class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Ordenar</label>
          <select
            id="sort"
            v-model="sortBy"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option value="newest">Mas recientes</option>
            <option value="price-asc">Precio: menor a mayor</option>
            <option value="price-desc">Precio: mayor a menor</option>
          </select>
        </div>
      </div>

      <div v-if="sortedProducts.length" class="mt-8 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
        <ProductCard v-for="product in sortedProducts" :key="product.id" :product="product" />
      </div>

      <div
        v-else
        class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-6 py-12 text-center shadow-sm"
      >
        <p class="text-lg font-semibold text-slate-800">No hay productos en esta categoria</p>
        <p class="mt-2 text-sm text-slate-500">Proba otra categoria del menu para seguir explorando.</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import ProductCard from '../components/ProductCard.vue';
import { products } from '../data/products';

const route = useRoute();
const categoryName = computed(() => route.params.categoryName as string);
const sortBy = ref('newest');

const productsByCategory = computed(() => {
  return products.filter((product) => product.category === categoryName.value);
});

const sortedProducts = computed(() => {
  const sorted = [...productsByCategory.value];

  if (sortBy.value === 'price-asc') {
    sorted.sort((a, b) => a.price - b.price);
  } else if (sortBy.value === 'price-desc') {
    sorted.sort((a, b) => b.price - a.price);
  } else {
    sorted.sort((a, b) => b.id - a.id);
  }

  return sorted;
});
</script>
