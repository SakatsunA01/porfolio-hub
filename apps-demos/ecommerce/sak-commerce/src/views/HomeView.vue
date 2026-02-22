<template>
  <div>
    <Hero />
    <Category />

    <section class="bg-axis-primary py-12">
      <div class="axis-container">
        <div class="mb-8 flex items-end justify-between gap-4">
          <h2 class="text-3xl font-display font-bold text-axis-secondary">Productos destacados</h2>
          <router-link to="/category/Audio" class="text-sm font-semibold text-axis-accent hover:underline">
            Ver todo
          </router-link>
        </div>

        <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
          <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
        </div>
      </div>
    </section>

    <section class="bg-axis-neutral py-16">
      <div class="axis-container">
        <div class="grid grid-cols-1 items-center gap-10 md:grid-cols-2">
          <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
            <img :src="manifestoBackground" alt="Manifiesto de marca" class="h-full w-full object-cover" />
          </div>

          <div>
            <h2 class="text-3xl font-display font-bold text-axis-secondary">Manifiesto de marca</h2>
            <p class="mt-4 max-w-xl text-axis-tertiary">
              En Axis Tech creemos que la tecnologia debe integrarse a la vida cotidiana de forma simple y elegante.
              Disenamos productos funcionales, sobrios y durables, pensados para uso real.
            </p>
            <p class="mt-4 max-w-xl text-sm text-slate-500">
              Cada linea de producto se construye con enfoque en rendimiento, materiales de calidad y una experiencia de compra clara.
            </p>
          </div>
        </div>
      </div>
    </section>

    <Testimonials />
  </div>
</template>

<script setup lang="ts">
import Hero from '../components/Hero.vue';
import Category from '../components/Category.vue';
import ProductCard from '../components/ProductCard.vue';
import Testimonials from '../components/Testimonials.vue';
import { products } from '../data/products';
import manifestoBackground from '../assets/manifiesto-backgroud.png';

const featuredProducts = products.slice(0, 4);
</script>
