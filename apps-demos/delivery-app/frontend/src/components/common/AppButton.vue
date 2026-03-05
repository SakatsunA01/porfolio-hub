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
      props.variant === 'primary' ? 'btn-primary-themed shadow-sm' : '',
      props.variant === 'soft' ? 'btn-soft-themed border border-slate-200/70 bg-white/90 text-slate-700' : '',
      props.variant === 'ghost' ? 'btn-ghost-themed text-slate-600' : '',
    ]"
  >
    <slot />
  </button>
</template>

<style scoped>
.btn-primary-themed {
  border: 1px solid color-mix(in srgb, var(--brand-primary, #10b981) 60%, transparent);
  background: var(--brand-primary, #10b981);
  color: #fff;
}

.btn-primary-themed:hover {
  border-color: var(--brand-primary-dark, #059669);
  background: var(--brand-primary-dark, #059669);
}

.btn-soft-themed:hover {
  border-color: color-mix(in srgb, var(--brand-primary, #10b981) 45%, white);
  color: #0f172a;
}

.btn-ghost-themed:hover {
  background: color-mix(in srgb, var(--brand-primary, #10b981) 10%, white);
  color: var(--brand-primary, #10b981);
}
</style>
