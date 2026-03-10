<script setup>
import { useUiStore } from '../stores/ui'

const ui = useUiStore()
</script>

<template>
  <teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 flex w-[min(92vw,360px)] flex-col gap-2">
      <article
        v-for="item in ui.state.toasts"
        :key="item.id"
        class="surface flex items-start justify-between gap-3 px-3 py-2 text-sm"
      >
        <p class="m-0">{{ item.message }}</p>
        <button type="button" class="icon-btn muted text-xs" @click="ui.removeToast(item.id)">Cerrar</button>
      </article>
    </div>
  </teleport>
</template>
