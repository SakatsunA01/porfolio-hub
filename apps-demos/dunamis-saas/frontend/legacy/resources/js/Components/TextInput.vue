<script setup>
import { onMounted, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: [String, Number],
    required: true,
})

const props = defineProps({
    error: {
        type: [String, Boolean],
        default: false,
    },
})

const input = ref(null)

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus()
    }
})

defineExpose({ focus: () => input.value.focus() })
</script>

<script>
export default {
    inheritAttrs: false,
}
</script>

<template>
    <div class="relative">
        <input
            v-bind="$attrs"
            class="ui-input"
            :class="{
                'border-red-400 focus:border-red-500 focus:ring-red-400': error,
                'pr-10': error,
            }"
            v-model="model"
            ref="input"
        />
        <span
            v-if="error"
            class="pointer-events-none absolute inset-y-0 right-3 inline-flex h-full items-center justify-center text-red-500"
        >
            !
        </span>
    </div>
</template>
