<script setup lang="ts">
defineProps<{
  open: boolean
}>()

defineEmits<{
  close: []
}>()
</script>

<template>
  <Transition name="modal-fade">
    <div v-if="open" class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm" @click="$emit('close')"></div>
  </Transition>
  <Transition name="modal-slide">
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center p-4">
      <div class="relative w-full max-w-lg rounded-3xl border border-slate-200/60 bg-white/90 p-4 shadow-sm backdrop-blur-xl">
        <span class="forest-glow -right-8 -top-8"></span>
        <slot />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 180ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-slide-enter-active,
.modal-slide-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}

.modal-slide-enter-from,
.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
