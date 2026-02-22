<template>
  <section class="bg-axis-primary py-12">
    <div v-if="product" class="axis-container">
      <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.1fr_1fr] lg:gap-10">
        <div class="space-y-4">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <img :src="selectedImage" :alt="product.name" class="h-full w-full object-cover" />
          </div>

          <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
            <button
              v-for="(image, index) in product.images"
              :key="index"
              type="button"
              class="overflow-hidden rounded-xl border bg-white transition hover:border-slate-300"
              :class="selectedImage === getImageUrl(image) ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200'"
              @click="selectedImage = getImageUrl(image)"
            >
              <img :src="getImageUrl(image)" :alt="product.name" class="aspect-square w-full object-cover" />
            </button>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-6 shadow-sm backdrop-blur-sm sm:p-8">
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ product.category }}</p>
          <h1 class="mt-2 text-3xl font-display font-bold text-slate-900">{{ product.name }}</h1>
          <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ formatPrice(product.price) }}</p>

          <p class="mt-4 text-sm leading-6 text-slate-600">{{ product.description }}</p>

          <div class="mt-6">
            <h3 class="text-sm font-semibold text-slate-900">Color</h3>
            <div class="mt-2 flex items-center gap-2">
              <span
                v-for="(color, index) in ['#111827', '#f8fafc', '#64748b']"
                :key="index"
                :style="{ backgroundColor: color }"
                class="h-7 w-7 rounded-full border border-slate-300"
              />
            </div>
          </div>

          <button
            type="button"
            @click="addToCart"
            :class="addedToCart ? 'bg-emerald-500' : 'bg-emerald-600 hover:bg-emerald-500 active:translate-y-px'"
            class="mt-7 inline-flex w-full items-center justify-center rounded-md px-5 py-3 text-sm font-semibold text-white shadow-sm transition"
          >
            {{ addedToCart ? 'Agregado al carrito' : 'Agregar al carrito' }}
          </button>

          <div class="mt-7 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <h3 class="text-sm font-semibold text-slate-900">Envio y devoluciones</h3>
            <p class="mt-1.5 text-sm text-slate-600">
              Envio gratis en 24/48 horas. Devoluciones sin costo durante 30 dias.
            </p>
          </div>
        </div>
      </div>
    </div>

    <ProductSpecifications v-if="product" :product="product" />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import ProductSpecifications from '../components/ProductSpecifications.vue';
import { products } from '../data/products';

const route = useRoute();
const productId = ref(parseInt(route.params.id as string));
const addedToCart = ref(false);

const product = computed(() => products.find((p) => p.id === productId.value));

const getImageUrl = (path: string) => {
  if (!path) return '';
  return new URL(path, import.meta.url).href;
};

const selectedImage = ref('');

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(price);
};

const addToCart = () => {
  addedToCart.value = true;
  setTimeout(() => {
    addedToCart.value = false;
  }, 1600);
};

watch(
  () => route.params.id,
  (newId) => {
    productId.value = parseInt(newId as string);
    selectedImage.value = getImageUrl(product.value?.image || '');
  },
  { immediate: true },
);
</script>
