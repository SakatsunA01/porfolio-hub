<template>
  <div>
    <Hero />

    <BaseSection id="philosophy" background="secondary">
      <SectionTitle>
        Nuestra Filosofia
      </SectionTitle>
      <p v-if="storefrontSettings.settings.philosophy_text" class="mb-10 max-w-3xl text-sm text-text-secondary">
        {{ storefrontSettings.settings.philosophy_text }}
      </p>

      <div class="grid gap-12 md:grid-cols-3">
        <article class="space-y-5">
          <svg class="h-8 w-8 text-text-secondary" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M7 23L16 9L25 23" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M11 19H21" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
          </svg>
          <h3 class="text-sm tracking-wide text-text-primary">
            Diseno atemporal
          </h3>
          <p class="text-sm text-text-secondary">
            Objetos creados para integrarse con naturalidad y permanecer vigentes en el tiempo.
          </p>
        </article>

        <article class="space-y-5">
          <svg class="h-8 w-8 text-text-secondary" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M16 6V26" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
            <path d="M9 12C9 9.79086 12.134 8 16 8C19.866 8 23 9.79086 23 12C23 14.2091 19.866 16 16 16C12.134 16 9 17.7909 9 20C9 22.2091 12.134 24 16 24C19.866 24 23 22.2091 23 20" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
          </svg>
          <h3 class="text-sm tracking-wide text-text-primary">
            Produccion responsable
          </h3>
          <p class="text-sm text-text-secondary">
            Materiales nobles, procesos conscientes y una escala de catalogo breve e intencional.
          </p>
        </article>

        <article class="space-y-5">
          <svg class="h-8 w-8 text-text-secondary" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M10 16.5L13.5 20L22 11.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M16 27C22.0751 27 27 22.0751 27 16C27 9.92487 22.0751 5 16 5C9.92487 5 5 9.92487 5 16C5 22.0751 9.92487 27 16 27Z" stroke="currentColor" stroke-width="1.2" />
          </svg>
          <h3 class="text-sm tracking-wide text-text-primary">
            Garantia extendida
          </h3>
          <p class="text-sm text-text-secondary">
            Cobertura clara y acompanamiento postventa para que la compra conserve valor con el uso.
          </p>
        </article>
      </div>
    </BaseSection>

    <BaseSection>
      <div v-if="featuredProduct" class="grid items-center gap-16 md:grid-cols-2">
        <div>
          <img :src="getProductImageUrl(featuredProduct.image)" :alt="featuredProduct.name" class="w-full" />
        </div>

        <div class="space-y-6">
          <h2 class="font-serif text-3xl tracking-wide text-text-primary">
            {{ featuredProduct.name }}
          </h2>
          <p class="text-lg text-text-secondary">
            {{ formatPrice(featuredProduct.price) }}
          </p>
          <p class="max-w-md text-text-secondary">
            {{ featuredProduct.description }}
          </p>
          <router-link :to="`/product/${featuredProduct.id}`" class="inline-block">
            <BaseButton>
              Ver producto
            </BaseButton>
          </router-link>
        </div>
      </div>
    </BaseSection>

    <BaseSection id="store">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <SectionTitle>
          Coleccion
        </SectionTitle>
        <router-link
          to="/catalog"
          class="text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
        >
          Ver catalogo completo
        </router-link>
      </div>

      <div class="grid gap-12 md:grid-cols-2">
        <ProductCard v-for="product in curatedProducts" :key="product.id" :product="product" />
      </div>
    </BaseSection>

    <BaseSection id="editions" background="secondary">
      <SectionTitle>
        Ediciones limitadas
      </SectionTitle>

      <div class="grid gap-16 md:grid-cols-2">
        <article v-for="edition in limitedEditions" :key="edition.id" class="space-y-5">
          <h3 class="font-serif text-2xl tracking-wide text-text-primary">
            {{ edition.name }}
          </h3>
          <p class="max-w-md text-text-secondary">
            {{ edition.description }}
          </p>
          <p class="text-sm tracking-wide text-text-secondary">
            Disponible en {{ edition.units }} unidades
          </p>
        </article>
      </div>
    </BaseSection>

    <BaseSection>
      <SectionTitle>
        Manifiesto
      </SectionTitle>

      <div class="mx-auto max-w-3xl text-center font-serif leading-relaxed tracking-wide text-text-secondary">
        <p>
          {{ manifestoText }}
        </p>
      </div>
    </BaseSection>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import BaseButton from '../components/BaseButton.vue'
import BaseSection from '../components/BaseSection.vue'
import Hero from '../components/Hero.vue'
import ProductCard from '../components/ProductCard.vue'
import SectionTitle from '../components/SectionTitle.vue'
import { useCatalogStore } from '../stores/catalog'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'
import { formatPrice, getProductImageUrl } from '../utils/catalog'

const catalogStore = useCatalogStore()
const storefrontSettings = useStorefrontSettingsStore()

const featuredProduct = computed(() =>
  catalogStore.products.find((product) => product.slug === 'axis-noise-cancelling-headphones')
  ?? catalogStore.products[0],
)

const curatedProducts = computed(() => catalogStore.products.slice(0, 4))

const limitedEditions = computed(() =>
  catalogStore.products
    .filter((product) => product.type === 'limited')
    .slice(0, 2)
    .map((product) => ({
      id: product.id,
      name: product.name,
      description: product.description,
      units: product.stock,
    })),
)

const manifestoText = computed(() =>
  storefrontSettings.settings.manifesto_text
  || 'Creemos en una tecnologia que no necesita imponerse para ser valiosa. Preferimos los objetos que conviven con la luz y los materiales con equilibrio y claridad.',
)

onMounted(() => {
  catalogStore.fetchProducts()
  storefrontSettings.fetchSettings()
})
</script>
