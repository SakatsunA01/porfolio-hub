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
        class="ui-btn-secondary text-xs uppercase tracking-wide px-4 py-2"
    >
        <slot />
    </button>
</template>
