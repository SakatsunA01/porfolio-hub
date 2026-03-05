<template>
  <section class="min-h-screen bg-bg-primary py-section-sm">
    <div class="axis-container space-y-5">
      <p class="text-sm tracking-wide text-text-secondary">Favoritos</p>
      <h1 class="font-serif text-4xl tracking-wide text-text-primary">Coleccion personal</h1>
      <p class="max-w-xl text-sm text-text-secondary">
        Este modulo queda preparado para personalizacion explicable. En esta fase muestra productos sugeridos por interes de catalogo.
      </p>

      <div class="grid gap-12 md:grid-cols-2">
        <ProductCard v-for="product in suggestedProducts" :key="product.id" :product="product" />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import ProductCard from '../components/ProductCard.vue'
import { useCatalogStore } from '../stores/catalog'

const catalogStore = useCatalogStore()
const suggestedProducts = computed(() => catalogStore.products.slice(0, 4))

onMounted(() => {
  catalogStore.fetchProducts()
})
</script>
