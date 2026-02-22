<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'neutral',
    },
    text: {
        type: String,
        default: '',
    },
})

const variantClasses = computed(() => {
    const map = {
        success: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70',
        warning: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70',
        danger: 'bg-red-50 text-red-700 ring-1 ring-red-200/70',
        info: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/70',
        neutral: 'bg-gray-50 text-gray-700 ring-1 ring-gray-200/70',
    }

    return map[props.type] || map.neutral
})
</script>

<template>
    <span class="px-2.5 inline-flex text-xs leading-5 font-semibold rounded-full" :class="variantClasses">
        {{ text }}
    </span>
</template>
