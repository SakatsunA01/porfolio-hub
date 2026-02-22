<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'soft' | 'ghost'
    full?: boolean
    py?: string
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
  }>(),
  {
    variant: 'soft',
    full: false,
    py: 'py-2.5',
    type: 'button',
  },
)
</script>

<template>
  <button
    :type="props.type"
    :disabled="props.disabled"
    class="rounded-xl px-4 text-sm font-semibold tracking-tight transition duration-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-45"
    :class="[
      props.py,
      props.full ? 'w-full' : '',
      props.variant === 'primary' ? 'border border-emerald-500/60 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-600 shadow-sm' : '',
      props.variant === 'soft' ? 'border border-slate-200/70 bg-white/90 text-slate-700 hover:border-emerald-300 hover:text-slate-900' : '',
      props.variant === 'ghost' ? 'text-slate-600 hover:bg-slate-100/80 hover:text-emerald-600' : '',
    ]"
  >
    <slot />
  </button>
</template>
