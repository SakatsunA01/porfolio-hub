<template>
  <footer id="footer" class="bg-bg-secondary py-section">
    <div class="mx-auto max-w-container px-6">
      <div class="grid gap-12 md:grid-cols-3">
        <div class="space-y-4">
          <p class="font-serif text-lg tracking-wide text-text-primary">{{ storefrontSettings.settings.name }}</p>
          <p class="max-w-xs text-sm text-text-secondary">
            {{ footerLead }}
          </p>
        </div>

        <div class="space-y-4">
          <p class="text-sm tracking-wide text-text-primary">Navegacion</p>
          <div class="space-y-3">
            <a href="#store" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Tienda</a>
            <a href="#philosophy" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Filosofia</a>
            <a href="#editions" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Ediciones</a>
          </div>
        </div>

        <div class="space-y-4">
          <p class="text-sm tracking-wide text-text-primary">Informacion</p>
          <div class="space-y-3">
            <router-link to="/contact" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Contacto</router-link>
            <router-link to="/privacy" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Privacidad</router-link>
            <router-link to="/terms" class="block text-sm text-text-secondary transition duration-200 ease-out hover:text-text-primary">Terminos</router-link>
          </div>
        </div>
      </div>

      <p class="mt-16 text-sm text-text-secondary">
        &copy; 2026 {{ storefrontSettings.settings.name }}. Todos los derechos reservados.
      </p>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useStorefrontSettingsStore } from '../stores/storefrontSettings'

const storefrontSettings = useStorefrontSettingsStore()

const footerLead = computed(() => {
  const manifesto = storefrontSettings.settings.manifesto_text
  if (!manifesto) {
    return 'Objetos tecnologicos para espacios conscientes.'
  }

  return manifesto.length > 90 ? `${manifesto.slice(0, 90)}...` : manifesto
})
</script>
