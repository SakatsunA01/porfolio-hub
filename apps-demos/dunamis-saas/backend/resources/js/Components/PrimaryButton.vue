<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
})

const isDisabled = computed(() => props.disabled || props.processing)
</script>

<template>
    <button
        :type="type"
        :disabled="isDisabled"
        :aria-busy="processing"
        class="ui-btn-primary uppercase tracking-wide"
        :class="{ 'opacity-60 cursor-not-allowed': isDisabled }"
    >
        <svg
            v-if="processing"
            class="mr-2 h-4 w-4 animate-spin text-white"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
        </svg>
        <slot />
    </button>
</template>
