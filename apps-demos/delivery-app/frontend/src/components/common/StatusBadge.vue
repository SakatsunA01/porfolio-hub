<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  value: string
}>()

const classes = computed(() => {
  if (['preparando', 'preparing', 'en cocina'].includes(props.value.toLowerCase())) return 'bg-amber-100 text-amber-700'
  if (['listo', 'ready', 'entregado', 'delivered'].includes(props.value.toLowerCase())) return 'bg-emerald-100 text-emerald-700'
  if (['en_camino', 'onroute', 'en camino'].includes(props.value.toLowerCase())) return 'bg-cyan-100 text-cyan-700'
  return 'bg-slate-100 text-slate-700'
})
</script>

<template>
  <span class="inline-flex rounded-full border border-slate-200/60 px-2.5 py-1 text-xs font-semibold tracking-tight" :class="classes">
    <slot>{{ value }}</slot>
  </span>
</template>
