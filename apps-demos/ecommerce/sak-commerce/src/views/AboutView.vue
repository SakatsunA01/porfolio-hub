<template>
  <section class="bg-bg-primary py-section">
    <div class="mx-auto max-w-container space-y-14 px-6">
      <header class="space-y-5">
        <p class="text-sm tracking-wide text-text-secondary">Nosotros</p>
        <h1 class="max-w-4xl font-serif text-5xl tracking-wide text-text-primary">
          Tecnologia integrada al espacio, no protagonista del espacio.
        </h1>
        <p class="max-w-3xl text-text-secondary">
          {{ philosophyText }}
        </p>
      </header>

      <div class="grid gap-8 md:grid-cols-3">
        <article v-for="pillar in pillars" :key="pillar.title" class="border border-bg-secondary bg-bg-secondary/25 p-6">
          <h2 class="font-serif text-2xl tracking-wide text-text-primary">{{ pillar.title }}</h2>
          <p class="mt-3 text-sm text-text-secondary">{{ pillar.body }}</p>
        </article>
      </div>

      <section class="border-t border-bg-secondary pt-10">
        <h2 class="font-serif text-3xl tracking-wide text-text-primary">Manifiesto</h2>
        <p class="mt-4 max-w-4xl leading-relaxed text-text-secondary">
          {{ manifestoText }}
        </p>
      </section>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'

const storefrontSettings = useStorefrontSettingsStore()

const philosophyText = computed(() =>
  storefrontSettings.settings.philosophy_text
  || 'Diseño esencial, materiales nobles y procesos conscientes para objetos de uso diario.',
)

const manifestoText = computed(() =>
  storefrontSettings.settings.manifesto_text
  || 'Creemos en una tecnologia serena: menos ruido, mas permanencia y claridad en cada decision de diseno.',
)

const pillars = [
  {
    title: 'Diseno atemporal',
    body: 'Lineas limpias y proporciones pensadas para durar mas alla de una tendencia.',
  },
  {
    title: 'Produccion responsable',
    body: 'Escala controlada, materiales consistentes y foco en calidad de largo plazo.',
  },
  {
    title: 'Soporte humano',
    body: 'Acompanamiento postventa y procesos claros para compras sin friccion.',
  },
]
</script>
