<template>
  <button
    :class="[
      fullWidth ? 'w-full md:w-auto' : '',
      'px-7 py-3 text-sm uppercase tracking-widest transition duration-200 ease-out',
      variant === 'secondary'
        ? 'border border-text-primary text-text-primary'
        : 'bg-text-primary text-bg-primary hover:opacity-90',
    ]"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
defineProps({
  variant: {
    type: String,
    default: 'primary',
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
})
</script>
