<template>
  <section class="relative overflow-hidden bg-axis-primary">
    <div class="absolute inset-0 transition-transform duration-100 ease-out" :style="parallaxStyle">
      <img class="h-full w-full object-cover" src="@/assets/hero-background.png" alt="Axis Tech" />
      <div class="absolute inset-0 bg-axis-secondary/55"></div>
    </div>

    <div class="relative axis-container py-24 sm:py-32 lg:py-44">
      <h1 class="max-w-3xl text-4xl font-display font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
        Diseno que simplifica tu dia
      </h1>
      <p class="mt-4 max-w-2xl text-sm text-white/85 sm:text-base">
        Tecnologia cotidiana con estetica premium y enfoque funcional.
      </p>
      <div class="mt-8">
        <router-link
          to="/category/Audio"
          class="inline-flex items-center rounded-md bg-axis-accent px-8 py-3 font-medium text-white transition hover:brightness-110"
        >
          Descubrir Coleccion
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

const scrollY = ref(0);

const handleScroll = () => {
  scrollY.value = window.scrollY;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const parallaxStyle = computed(() => ({
  transform: `translateY(${scrollY.value * 0.2}px)`,
}));
</script>
