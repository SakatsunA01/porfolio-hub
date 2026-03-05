<template>
  <BaseSection>
    <div class="grid items-center gap-12 md:grid-cols-2">
      <div class="space-y-6">
        <h1 class="font-serif text-5xl leading-tight tracking-wide text-text-primary">
          Objetos tecnologicos para espacios conscientes.
        </h1>

        <p class="max-w-md text-text-secondary">
          {{ philosophyLead }}
        </p>

        <router-link to="/catalog" class="inline-block">
          <BaseButton>
            Explorar tienda
          </BaseButton>
        </router-link>
      </div>

      <img :src="heroImage" alt="Axis hero" class="w-full object-cover" />
    </div>
  </BaseSection>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseButton from './BaseButton.vue'
import BaseSection from './BaseSection.vue'
import heroImage from '@/assets/Axis Noise-Cancelling Headphones/auri1.png'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'

const storefrontSettings = useStorefrontSettingsStore()
const philosophyLead = computed(() =>
  storefrontSettings.settings.philosophy_text
  || 'Diseno propio, materiales nobles y produccion responsable.',
)
</script>
