<script setup>
import { computed } from 'vue'

const props = defineProps({
    padding: {
        type: String,
        default: 'p-5',
    },
    elevated: {
        type: Boolean,
        default: true,
    },
})

const baseClasses = computed(() => [
    'rounded-card',
    'bg-white',
    'border',
    'border-surface-border',
    props.elevated ? 'shadow-soft-lg' : 'shadow-soft',
    props.padding,
])
</script>

<template>
    <div :class="[baseClasses, $attrs.class]" v-bind="$attrs">
        <slot />
    </div>
</template>
