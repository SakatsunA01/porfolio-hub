<template>
  <BaseSection>
    <div class="space-y-10">
      <div class="space-y-4">
        <p class="text-sm tracking-wide text-text-secondary">Catalogo</p>
        <h1 class="font-serif text-4xl tracking-wide text-text-primary md:text-5xl">
          Curaduria completa
        </h1>
        <p class="max-w-2xl text-sm text-text-secondary">
          Busqueda asistida con control manual: intencion, filtros y resultados consistentes.
        </p>
      </div>

      <div v-if="featureFlags.hybridSearchV2026" class="space-y-4 border border-bg-secondary bg-bg-secondary/35 p-4">
        <label for="catalog-search" class="block text-sm tracking-wide text-text-secondary">
          Buscar por nombre, SKU o categoria
        </label>
        <div class="grid gap-3 md:grid-cols-[1fr_auto]">
          <input
            id="catalog-search"
            v-model="catalogStore.searchQuery"
            type="search"
            class="w-full border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
            placeholder="Ej: auricular bamboo"
            @input="onSearchInput"
          />
          <button
            type="button"
            class="border border-text-primary px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-text-primary hover:text-bg-primary"
            @click="resetSearch"
          >
            Limpiar
          </button>
        </div>

        <div class="flex flex-wrap gap-2 pt-1">
          <button
            v-for="chip in availableIntentChips"
            :key="chip"
            type="button"
            class="border px-3 py-2 text-xs tracking-wide transition duration-200 ease-out"
            :class="catalogStore.intent === chip ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
            @click="toggleIntent(chip)"
          >
            {{ chip }}
          </button>
        </div>
      </div>

      <div v-if="featureFlags.hybridSearchV2026" class="grid gap-3 md:grid-cols-4">
        <select
          v-model="categoryFacet"
          class="border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
          @change="runSearch"
        >
          <option :value="null">Todas las categorias</option>
          <option v-for="category in catalogStore.categories" :key="category.id" :value="category.name">
            {{ category.name }}
          </option>
        </select>

        <select
          v-model="typeFacet"
          class="border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
          @change="runSearch"
        >
          <option :value="null">Todos los tipos</option>
          <option value="permanent">Permanente</option>
          <option value="limited">Edicion limitada</option>
          <option value="preorder">Preventa</option>
        </select>

        <select
          v-model="priceFacet"
          class="border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
          @change="runSearch"
        >
          <option value="all">Todos los precios</option>
          <option value="0-100000">Hasta $100.000</option>
          <option value="100000-250000">$100.000 a $250.000</option>
          <option value="250000+">Desde $250.000</option>
        </select>

        <label class="inline-flex min-h-12 items-center gap-3 border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary">
          <input
            v-model="inStockFacet"
            type="checkbox"
            class="h-5 w-5"
            @change="runSearch"
          />
          Solo disponibles
        </label>
      </div>

      <div v-if="featureFlags.socialProofV2026 && catalogStore.socialProof" class="flex flex-wrap items-center gap-4 text-sm text-text-secondary">
        <span>
          {{ catalogStore.socialProof.rating.average.toFixed(1) }} / 5 · {{ catalogStore.socialProof.rating.count }} opiniones verificadas
        </span>
        <span>{{ catalogStore.socialProof.signals.delivery_notice }}</span>
        <span v-if="catalogStore.socialProof.signals.high_demand">Alta demanda esta semana</span>
      </div>

      <div v-if="catalogStore.isSearching || catalogStore.isLoadingProducts" class="grid gap-12 md:grid-cols-2">
        <div v-for="skeleton in 4" :key="skeleton" class="space-y-4">
          <div class="aspect-[4/5] animate-pulse bg-bg-secondary" />
          <div class="h-4 w-2/3 animate-pulse bg-bg-secondary" />
          <div class="h-4 w-1/3 animate-pulse bg-bg-secondary" />
        </div>
      </div>

      <div v-else-if="results.length === 0" class="border border-bg-secondary bg-bg-secondary/30 px-6 py-12 text-center">
        <p class="font-serif text-2xl text-text-primary">Sin resultados para esta busqueda</p>
        <p class="mt-2 text-sm text-text-secondary">Ajusta filtros o limpia la consulta para ver todo el catalogo.</p>
      </div>

      <div v-else class="grid gap-12 md:grid-cols-2">
        <ProductCard v-for="product in results" :key="product.id" :product="product" />
      </div>
    </div>
  </BaseSection>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import BaseSection from '../components/BaseSection.vue'
import ProductCard from '../components/ProductCard.vue'
import { useCatalogStore } from '../stores/catalog'
import type { SearchIntent } from '../types/commerce'
import { featureFlags } from '../config/features'

const catalogStore = useCatalogStore()

let searchDebounce: ReturnType<typeof setTimeout> | null = null

const availableIntentChips = computed(() => {
  return (catalogStore.suggestedIntents.length ? catalogStore.suggestedIntents : ['Audio', 'Carga', 'Accesorios']) as SearchIntent[]
})

const categoryFacet = computed({
  get: () => catalogStore.facets.category,
  set: (value: string | null) => catalogStore.setFacet('category', value),
})

const typeFacet = computed({
  get: () => catalogStore.facets.type,
  set: (value: 'permanent' | 'limited' | 'preorder' | null) => catalogStore.setFacet('type', value),
})

const priceFacet = computed({
  get: () => catalogStore.facets.priceRange,
  set: (value: 'all' | '0-100000' | '100000-250000' | '250000+') => catalogStore.setFacet('priceRange', value),
})

const inStockFacet = computed({
  get: () => catalogStore.facets.inStockOnly,
  set: (value: boolean) => catalogStore.setFacet('inStockOnly', value),
})

const results = computed(() => catalogStore.activeProducts)

const runSearch = () => {
  catalogStore.searchStorefront()
}

const onSearchInput = () => {
  if (searchDebounce) {
    clearTimeout(searchDebounce)
  }

  searchDebounce = setTimeout(() => {
    runSearch()
  }, 180)
}

const toggleIntent = (chip: SearchIntent) => {
  catalogStore.setIntent(catalogStore.intent === chip ? null : chip)
  runSearch()
}

const resetSearch = () => {
  catalogStore.clearSearch()
}

onMounted(() => {
  catalogStore.hydrateStorefront()
})
</script>
