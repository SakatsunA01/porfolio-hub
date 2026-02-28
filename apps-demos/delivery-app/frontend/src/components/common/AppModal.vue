<script setup lang="ts">
defineProps<{
  open: boolean
  maxWidthClass?: string
  scrollable?: boolean
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
    <div v-if="open" class="fixed inset-0 z-50 grid items-end p-0 md:place-items-center md:p-4">
      <div
        class="relative w-full rounded-t-3xl border border-slate-200/60 bg-white/95 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] backdrop-blur-xl md:rounded-3xl"
        :class="[
          maxWidthClass || 'max-w-lg',
          scrollable ? 'max-h-[85vh] overflow-y-auto custom-scroll' : '',
        ]"
      >
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
  transform: translateY(18px);
}

@media (min-width: 768px) {
  .modal-slide-enter-from,
  .modal-slide-leave-to {
    transform: translateY(8px);
  }
}
</style>
