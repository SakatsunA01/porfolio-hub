<template>
  <section class="bg-bg-primary py-12">
    <div class="axis-container">
      <div class="mb-6">
        <h2 class="font-serif text-3xl tracking-wide text-text-primary">Explora por categoria</h2>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <router-link
          v-for="category in categories"
          :key="category.id"
          :to="`/category/${encodeURIComponent(category.name)}`"
          class="border border-bg-secondary bg-bg-primary p-6 text-center transition duration-200 ease-out hover:bg-bg-secondary/40"
        >
          <h3 class="text-lg tracking-wide text-text-primary">{{ category.name }}</h3>
          <p class="mt-2 text-xs tracking-wide text-text-secondary">{{ category.products_count }} productos</p>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useCatalogStore } from '../stores/catalog'

const catalogStore = useCatalogStore()
const categories = computed(() => catalogStore.categories)

onMounted(() => {
  catalogStore.fetchCategories()
})
</script>
