<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
    message: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'success',
    },
    duration: {
        type: Number,
        default: 4000,
    },
})

const emit = defineEmits(['close'])
const visible = ref(true)
let timeoutId = null

const startTimer = () => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
        visible.value = false
        emit('close')
    }, props.duration)
}

watch(
    () => props.message,
    (val) => {
        if (val) {
            visible.value = true
            startTimer()
        }
    },
    { immediate: true }
)

onMounted(startTimer)
onUnmounted(() => clearTimeout(timeoutId))

const styles = {
    success: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    error: 'bg-red-50 text-red-800 border-red-200',
}
</script>

<template>
    <transition
        enter-active-class="transform ease-out duration-200 transition"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed right-4 top-4 z-50 w-80 rounded-lg border px-4 py-3 shadow-lg"
            :class="styles[type] || styles.success"
            role="alert"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 text-sm font-medium">
                    {{ message }}
                </div>
                <button
                    type="button"
                    class="text-xs font-semibold text-gray-500 hover:text-gray-700"
                    @click="() => emit('close')"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </transition>
</template>
