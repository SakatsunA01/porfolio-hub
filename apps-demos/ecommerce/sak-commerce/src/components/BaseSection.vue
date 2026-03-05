<template>
  <section :class="[
    'py-section',
    background === 'secondary' ? 'bg-bg-secondary' : 'bg-bg-primary',
  ]">
    <div class="max-w-container mx-auto px-6">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps({
  background: {
    type: String,
    default: 'primary',
  },
})
</script>
