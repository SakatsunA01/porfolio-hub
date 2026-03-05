<template>
  <section class="bg-bg-primary py-section">
    <div class="mx-auto max-w-container space-y-12 px-6">
      <header class="space-y-4 text-center">
        <p class="text-sm tracking-wide text-text-secondary">Contacto</p>
        <h1 class="font-serif text-5xl tracking-wide text-text-primary">Contactanos</h1>
        <p class="mx-auto max-w-3xl text-text-secondary">
          {{ contactText }}
        </p>
      </header>

      <div class="grid gap-6 md:grid-cols-3">
        <article v-for="card in contactCards" :key="card.title" class="border border-bg-secondary bg-bg-secondary/30 p-6">
          <h2 class="font-serif text-2xl tracking-wide text-text-primary">{{ card.title }}</h2>
          <p class="mt-3 text-sm text-text-secondary">{{ card.body }}</p>
          <a :href="card.href" class="mt-5 inline-block text-sm tracking-wide text-accent-olive transition duration-200 ease-out hover:opacity-80">
            {{ card.cta }}
          </a>
        </article>
      </div>

      <section class="space-y-4 border-t border-bg-secondary pt-10 text-center">
        <h2 class="font-serif text-3xl tracking-wide text-text-primary">Conoce a Nuestro Equipo</h2>
        <p class="mx-auto max-w-3xl text-text-secondary">
          {{ teamText }}
        </p>
      </section>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'

const storefrontSettings = useStorefrontSettingsStore()

const contactText = computed(() =>
  storefrontSettings.settings.contact_text
  || 'Estamos para ayudarte con soporte, ventas y postventa.',
)

const teamText = computed(() =>
  storefrontSettings.settings.team_text
  || 'Personas reales disponibles para ayudarte en cada compra.',
)

const contactCards = [
  {
    title: 'Soporte por email',
    body: 'Respondemos consultas tecnicas y de compra en menos de 24h habiles.',
    cta: 'soporte@axistech.com',
    href: 'mailto:soporte@axistech.com',
  },
  {
    title: 'Atencion telefonica',
    body: 'Lunes a viernes, 9:00 a 18:00. Ayuda comercial y seguimiento de ordenes.',
    cta: '+54 11 5555 5555',
    href: 'tel:+541155555555',
  },
  {
    title: 'Mensajeria',
    body: 'Contacto rapido para estado de pedidos y consultas de stock.',
    cta: 'Enviar mensaje',
    href: 'https://wa.me/5491111111111',
  },
]
</script>
