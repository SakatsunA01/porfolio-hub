<template>
  <BaseSection>
    <div class="space-y-12">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-sm tracking-wide text-text-secondary">Categoria</p>
          <h1 class="mt-3 font-serif text-4xl tracking-wide text-text-primary md:text-5xl">
            {{ categoryName }}
          </h1>
          <p class="mt-4 text-sm text-text-secondary">
            {{ sortedProducts.length }} productos disponibles
          </p>
        </div>

        <div class="w-full sm:w-64">
          <label for="sort" class="mb-2 block text-sm tracking-wide text-text-secondary">Ordenar</label>
          <select
            id="sort"
            v-model="sortBy"
            class="w-full border border-bg-secondary bg-bg-primary px-4 py-3 text-sm tracking-wide text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
          >
            <option value="newest">Mas recientes</option>
            <option value="price-asc">Precio: menor a mayor</option>
            <option value="price-desc">Precio: mayor a menor</option>
          </select>
        </div>
      </div>

      <div v-if="sortedProducts.length" class="grid gap-12 md:grid-cols-2">
        <ProductCard v-for="product in sortedProducts" :key="product.id" :product="product" />
      </div>

      <div v-else class="border border-bg-secondary bg-bg-secondary/40 px-6 py-12 text-center">
        <p class="font-serif text-2xl tracking-wide text-text-primary">
          No hay productos en esta categoria
        </p>
        <p class="mt-3 text-sm text-text-secondary">
          Proba otra categoria o recorre el catalogo completo.
        </p>
      </div>
    </div>
  </BaseSection>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import BaseSection from '../components/BaseSection.vue'
import ProductCard from '../components/ProductCard.vue'
import { useCatalogStore } from '../stores/catalog'

const route = useRoute()
const catalogStore = useCatalogStore()
const categoryName = computed(() => decodeURIComponent(String(route.params.categoryName || '')).trim())
const sortBy = ref('newest')

const productsByCategory = computed(() => {
  const selected = categoryName.value.toLocaleLowerCase()
  return catalogStore.products.filter((product) => product.category.toLocaleLowerCase() === selected)
})

const sortedProducts = computed(() => {
  const sorted = [...productsByCategory.value]

  if (sortBy.value === 'price-asc') {
    sorted.sort((a, b) => a.price - b.price)
  } else if (sortBy.value === 'price-desc') {
    sorted.sort((a, b) => b.price - a.price)
  } else {
    sorted.sort((a, b) => b.id - a.id)
  }

  return sorted
})

onMounted(() => {
  catalogStore.fetchProducts()
})
</script>
