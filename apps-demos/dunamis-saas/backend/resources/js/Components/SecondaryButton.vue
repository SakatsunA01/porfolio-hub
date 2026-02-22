<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="ui-btn-secondary text-xs uppercase tracking-wide"
    >
        <slot />
    </button>
</template>
