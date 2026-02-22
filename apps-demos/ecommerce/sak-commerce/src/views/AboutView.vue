<template>
  <div class="bg-white">
    <!-- Parte 1: El Encabezado - La Fusión de Mundos -->
    <div class="relative h-96 overflow-hidden">
      <div class="absolute inset-0 transform translate-y-0 transition-transform duration-100 ease-out"
        :style="parallaxStyle">
        <img class="w-full h-full object-cover" :src="getImageUrl('../assets/Fusión de Tecnología y Diseño.png')"
          alt="Fusión de Tecnología y Diseño">
        <div class="absolute inset-0 bg-black/50"></div>
      </div>
      <div class="relative flex items-center justify-center h-full text-center px-4">
        <div class="max-w-3xl">
          <h1 class="text-4xl font-display font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
            En Axis Tech, no solo creamos tecnología; <span class="text-axis-accent">diseñamos experiencias</span>.
          </h1>
          <p class="mt-4 text-lg leading-8 text-white font-sans">
            Fundada en la creencia de que la innovación debe ser sinónimo de simplicidad y elegancia, nuestra misión es
            integrar la funcionalidad más avanzada en objetos cotidianos que no solo resuelvan problemas, sino que
            también inspiren y deleiten.
          </p>
        </div>
      </div>
    </div>

    <!-- Parte 2: Los Pilares - La Filosofía en Acción -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
      <h2 class="text-3xl font-bold tracking-tight text-axis-secondary sm:text-4xl font-display text-center">Nuestra
        Filosofía</h2>
      <div class="mt-16 grid grid-cols-1 gap-x-8 gap-y-16 lg:grid-cols-3">
        <!-- Calidad Inquebrantable -->
        <div class="flex flex-col items-center text-center">
          <img class="w-full h-64 object-cover rounded-lg shadow-lg"
            :src="getImageUrl('../assets/Atención al Detalle.png')" alt="Atención al Detalle">
          <h3 class="mt-6 text-xl font-bold text-axis-secondary font-display">Calidad Inquebrantable</h3>
          <p class="mt-4 text-base leading-7 text-axis-tertiary">
            Cada producto es el resultado de una meticulosa atención al detalle, desde la selección de materiales
            premium hasta la interfaz más fluida.
          </p>
        </div>

        <!-- Diseño Intuitivo -->
        <div class="flex flex-col items-center text-center">
          <img class="w-full h-64 object-cover rounded-lg shadow-lg" :src="getImageUrl('../assets/diseño.png')"
            alt="Conexión Humana">
          <h3 class="mt-6 text-xl font-bold text-axis-secondary font-display">Diseño Intuitivo</h3>
          <p class="mt-4 text-base leading-7 text-axis-tertiary">
            Creemos que la verdadera tecnología es aquella que se desvanece en el fondo, permitiéndote vivir y crear sin
            distracciones.
          </p>
        </div>

        <!-- Conexión Humana -->
        <div class="flex flex-col items-center text-center">
          <img class="w-full h-64 object-cover rounded-lg shadow-lg" :src="getImageUrl('../assets/Conexión Humana.png')"
            alt="Conexión Humana">
          <h3 class="mt-6 text-xl font-bold text-axis-secondary font-display">Conexión Humana</h3>
          <p class="mt-4 text-base leading-7 text-axis-tertiary">
            La innovación con propósito y el diseño centrado en el usuario son el corazón de todo lo que hacemos.
          </p>
        </div>
      </div>
    </div>

    <!-- Parte 3: El Futuro - Visión y Valores -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
      <!-- Nuestra Visión -->
      <div class="mx-auto max-w-3xl text-center">
        <h2 class="text-3xl font-bold tracking-tight text-axis-secondary sm:text-4xl font-display">Nuestra Visión</h2>
        <p class="mt-6 text-lg leading-8 text-axis-tertiary">
          Visualizamos un futuro donde la tecnología no es una barrera, sino un puente hacia una vida más conectada,
          eficiente y hermosa. Nos esforzamos por ser líderes en el diseño de productos que no solo satisfagan las
          necesidades actuales, sino que anticipen las del mañana, siempre con un enfoque en la sostenibilidad y la
          responsabilidad.
        </p>
      </div>

      <!-- Nuestros Valores -->
      <div class="mx-auto mt-16 max-w-5xl">
        <h2 class="text-3xl font-bold tracking-tight text-axis-secondary sm:text-4xl font-display text-center">Nuestros
          Valores</h2>
        <dl class="mt-10 grid grid-cols-1 gap-x-8 gap-y-10 text-base leading-7 text-axis-tertiary lg:grid-cols-3">
          <div class="relative pl-9">
            <dt class="font-semibold text-axis-secondary">
              <svg class="absolute left-0 top-1 h-5 w-5 text-axis-accent" viewBox="0 0 20 20" fill="currentColor"
                aria-hidden="true">
                <path fill-rule="evenodd"
                  d="M5.5 17a4.5 4.5 0 01-1.44-4.488C2.39 10.767 3.61 8.38 6.298 8.38c.546 0 1.067.116 1.534.33A7.48 7.48 0 0110 6.5a7.48 7.48 0 012.168 1.912c.467-.214.988-.33 1.534-.33 2.688 0 3.909 2.387 2.332 4.132a4.5 4.5 0 01-1.44 4.488L12 20l-2-2-2 2-2-2-2 2z"
                  clip-rule="evenodd" />
              </svg>
              Innovación con Propósito.
            </dt>
            <dd>Cada avance tecnológico que implementamos está pensado para mejorar la vida de nuestros usuarios, no
              solo para impresionar.</dd>
          </div>
          <div class="relative pl-9">
            <dt class="font-semibold text-axis-secondary">
              <svg class="absolute left-0 top-1 h-5 w-5 text-axis-accent" viewBox="0 0 20 20" fill="currentColor"
                aria-hidden="true">
                <path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.5c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6.75z"
                  clip-rule="evenodd" />
              </svg>
              Diseño Centrado en el Usuario.
            </dt>
            <dd>La experiencia del usuario es el corazón de todo lo que hacemos. La simplicidad y la intuición son
              nuestras guías.</dd>
          </div>
          <div class="relative pl-9">
            <dt class="font-semibold text-axis-secondary">
              <svg class="absolute left-0 top-1 h-5 w-5 text-axis-accent" viewBox="0 0 20 20" fill="currentColor"
                aria-hidden="true">
                <path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.5c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6.75z"
                  clip-rule="evenodd" />
              </svg>
              Sostenibilidad y Responsabilidad.
            </dt>
            <dd>Nos comprometemos a minimizar nuestro impacto ambiental y a operar de manera ética en cada etapa de
              nuestra cadena de valor.</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const scrollY = ref(0);

const handleScroll = () => {
  scrollY.value = window.scrollY;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const parallaxStyle = ref({});

// Adjust the parallax effect strength by changing the division factor
// A smaller number means a stronger effect
parallaxStyle.value = {
  transform: `translateY(${scrollY.value * 0.3}px)`,
};

const getImageUrl = (path: string) => {
  return new URL(path, import.meta.url).href;
};
</script>