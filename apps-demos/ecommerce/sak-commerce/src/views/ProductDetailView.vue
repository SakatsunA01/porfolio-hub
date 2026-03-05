<template>
  <section class="bg-bg-primary py-section-sm">
    <div v-if="product" class="axis-container">
      <nav class="mb-8 flex items-center gap-2 text-sm text-text-secondary">
        <router-link to="/" class="transition duration-200 ease-out hover:text-text-primary">Home</router-link>
        <span>/</span>
        <router-link :to="`/category/${product.category}`" class="transition duration-200 ease-out hover:text-text-primary">
          {{ product.category }}
        </router-link>
        <span>/</span>
        <span class="text-text-primary">{{ product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 gap-16 md:grid-cols-2">
        <div class="space-y-4">
          <div class="aspect-[4/5] overflow-hidden bg-bg-secondary">
            <img
              :key="selectedImage"
              :src="selectedImage"
              :alt="product.name"
              class="h-full w-full object-cover transition duration-200 ease-out"
            />
          </div>

          <div class="flex flex-wrap gap-3">
            <button
              v-for="(image, index) in product.images"
              :key="image"
              type="button"
              class="h-20 w-20 overflow-hidden border bg-bg-secondary transition duration-200 ease-out hover:border-text-secondary/50"
              :class="selectedImage === getProductImageUrl(image) ? 'border-text-secondary' : 'border-bg-secondary'"
              @click="selectImage(index)"
            >
              <img :src="getProductImageUrl(image)" :alt="product.name" class="h-20 w-20 object-cover" />
            </button>
          </div>
        </div>

        <div class="space-y-6">
          <h1 class="font-serif text-3xl tracking-wide text-text-primary md:text-4xl">
            {{ product.name }}
          </h1>
          <p ref="priceMarker" class="text-lg text-text-primary">
            {{ formatPrice(product.price) }}
          </p>
          <p class="max-w-md text-text-secondary">
            {{ product.description }}
          </p>

          <div v-if="colorOptions.length > 0">
            <h3 class="text-sm tracking-wide text-text-primary">Color</h3>
            <div class="mt-3 flex items-center gap-3">
              <button
                v-for="(option, index) in colorOptions"
                :key="option.name"
                type="button"
                class="inline-flex items-center gap-3 border px-3 py-2 text-sm tracking-wide transition duration-200 ease-out"
                :class="selectedColorIndex === index ? 'border-text-primary text-text-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary/50'"
                @click="selectColor(index)"
              >
                <span
                  class="h-7 w-7 rounded-full border border-bg-secondary"
                  :style="{ backgroundColor: option.hex }"
                />
                <span>{{ option.name }}</span>
              </button>
            </div>
          </div>

          <div>
            <h3 class="text-sm tracking-wide text-text-primary">Tamano</h3>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-3">
              <button
                v-for="size in sizeOptions"
                :key="size"
                type="button"
                class="border border-text-primary px-4 py-2 text-sm tracking-wide transition duration-200 ease-out"
                :class="selectedSize === size ? 'bg-text-primary text-bg-primary' : 'text-text-primary'"
                @click="selectedSize = size"
              >
                {{ size }}
              </button>
            </div>
          </div>

          <p class="pt-4 text-sm tracking-wide text-text-secondary">
            {{ availabilityLabel }}
          </p>

          <BaseButton full-width @click="handleAddToCart">
            Agregar a la seleccion
          </BaseButton>

          <div class="border-t border-bg-secondary pt-6">
            <h3 class="text-sm tracking-wide text-text-primary">Envio y devoluciones</h3>
            <p class="mt-2 max-w-md text-sm text-text-secondary">
              Envio a todo el pais. Devoluciones sin costo durante 30 dias.
            </p>
          </div>
        </div>
      </div>
    </div>

    <ProductSpecifications v-if="product" :product="product" />

    <BaseSection v-if="relatedProducts.length > 0">
      <SectionTitle>
        Tambien puede interesarte
      </SectionTitle>

      <div class="grid gap-16 md:grid-cols-2">
        <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
      </div>
    </BaseSection>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <div
        v-if="product && showStickyAddToCart"
        class="fixed inset-x-4 bottom-4 z-30 border border-bg-secondary bg-bg-primary p-4 lg:hidden"
      >
        <div class="flex items-center justify-between gap-4">
          <div class="min-w-0">
            <p class="truncate text-sm tracking-wide text-text-primary">{{ product.name }}</p>
            <p class="text-sm text-text-secondary">{{ formatPrice(product.price) }}</p>
          </div>
          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center bg-text-primary px-5 py-3 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90"
            @click="handleAddToCart"
          >
            Agregar
          </button>
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import BaseButton from '../components/BaseButton.vue'
import BaseSection from '../components/BaseSection.vue'
import ProductSpecifications from '../components/ProductSpecifications.vue'
import ProductCard from '../components/ProductCard.vue'
import SectionTitle from '../components/SectionTitle.vue'
import { useCart } from '@/composables/useCart'
import { useCatalogStore } from '../stores/catalog'
import { formatPrice, getAvailabilityLabel, getProductImageUrl } from '../utils/catalog'

const route = useRoute()
const { addToCart, openCart } = useCart()
const catalogStore = useCatalogStore()

const productId = ref(parseInt(route.params.id as string))
const selectedImage = ref('')
const selectedColorIndex = ref(0)
const selectedSize = ref('Unico')
const priceMarker = ref<HTMLElement | null>(null)
const showStickyAddToCart = ref(false)

const product = computed(() => catalogStore.productById(productId.value))

const relatedProducts = computed(() =>
  catalogStore.products.filter((item) => item.id !== productId.value).slice(0, 2),
)

const colorLabels = ['Negro grafito', 'Blanco nieve', 'Gris mineral', 'Arcilla']

const colorOptions = computed(() =>
  (product.value?.images || []).map((image, index) => ({
    name: colorLabels[index] || `Tono ${index + 1}`,
    hex: ['#22221F', '#F7F5F0', '#5A5A55', '#8A6F5A'][index] || '#22221F',
    image,
  })),
)

const sizeOptions = computed(() => {
  if (product.value?.variants.length) {
    return product.value.variants.map((variant) => variant.size)
  }

  return ['Unico']
})

const availabilityLabel = computed(() => (product.value ? getAvailabilityLabel(product.value) : ''))

const selectImage = (index: number) => {
  if (!product.value?.images[index]) {
    return
  }

  selectedColorIndex.value = index
  selectedImage.value = getProductImageUrl(product.value.images[index])
}

const selectColor = (index: number) => {
  selectImage(index)
}

const handleAddToCart = () => {
  if (!product.value) {
    return
  }

  addToCart(
    product.value,
    { name: colorOptions.value[selectedColorIndex.value]?.name || 'Default' },
    { name: selectedSize.value },
    1,
  )
  openCart()
}

const syncStickyCta = () => {
  if (!priceMarker.value) {
    showStickyAddToCart.value = false
    return
  }

  const rect = priceMarker.value.getBoundingClientRect()
  showStickyAddToCart.value = rect.bottom < 0
}

watch(
  () => route.params.id,
  async (newId) => {
    productId.value = parseInt(newId as string)
    await catalogStore.fetchProduct(productId.value)
    selectedColorIndex.value = 0
    selectedSize.value = sizeOptions.value[0] || 'Unico'
    selectedImage.value = getProductImageUrl(product.value?.image || '')
    await nextTick()
    syncStickyCta()
  },
  { immediate: true },
)

onMounted(() => {
  catalogStore.fetchProducts()
  syncStickyCta()
  window.addEventListener('scroll', syncStickyCta, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', syncStickyCta)
})
</script>
