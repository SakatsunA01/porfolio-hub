<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition duration-200 ease-out"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-2"
  >
    <div
      v-if="isVisible"
      class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-[16px] border border-bg-secondary bg-bg-primary/95 px-5 py-3 text-sm tracking-wide text-text-secondary backdrop-blur-sm"
    >
      {{ message }}
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { useToast } from '../composables/useToast'

const { isVisible, message } = useToast()
</script>
