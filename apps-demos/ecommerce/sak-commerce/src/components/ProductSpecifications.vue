<template>
  <section class="bg-bg-primary py-section-sm">
    <div class="mx-auto max-w-container px-6">
      <div class="max-w-3xl">
        <div
          v-for="(feature, index) in product.details"
          :key="feature.name"
          class="border-b border-bg-secondary py-6"
        >
          <button
            type="button"
            class="flex w-full items-center justify-between gap-6 text-left"
            @click="toggle(index)"
          >
            <span class="font-serif text-lg tracking-wide text-text-primary">
              {{ feature.name }}
            </span>
            <span class="text-sm tracking-wide text-text-secondary">
              {{ openIndexes.includes(index) ? 'Cerrar' : 'Abrir' }}
            </span>
          </button>

          <Transition
            enter-active-class="transition-all duration-200 ease-out overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-40 opacity-100"
            leave-active-class="transition-all duration-200 ease-out overflow-hidden"
            leave-from-class="max-h-40 opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="openIndexes.includes(index)" class="pr-12 pt-4 text-text-secondary">
              <p>{{ feature.description }}</p>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Product } from '../types/catalog'

defineProps<{ product: Product }>()

const openIndexes = ref<number[]>([0])

const toggle = (index: number) => {
  if (openIndexes.value.includes(index)) {
    openIndexes.value = openIndexes.value.filter((item) => item !== index)
    return
  }

  openIndexes.value = [...openIndexes.value, index]
}
</script>
