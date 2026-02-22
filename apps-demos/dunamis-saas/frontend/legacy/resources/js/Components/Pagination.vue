<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
})

const normalizados = computed(() =>
    props.links.map((link, index) => {
        const esPrimero = index === 0
        const esUltimo = index === props.links.length - 1
        let label = link.label || ''
        if (esPrimero) label = 'Anterior'
        else if (esUltimo) label = 'Siguiente'
        else label = label.replace(/&laquo;|&raquo;/g, '').trim()
        return { ...link, display: label }
    })
)
</script>

<template>
    <nav class="flex justify-center">
        <ul class="inline-flex items-center gap-1 text-sm">
            <li v-for="link in normalizados" :key="link.url ?? link.display">
                <span
                    v-if="!link.url"
                    class="rounded border border-gray-200 px-3 py-1 text-gray-400"
                >
                    {{ link.display }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    class="rounded border px-3 py-1"
                    :class="[
                        link.active
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300',
                    ]"
                    :preserve-scroll="true"
                    preserve-state
                    @click="$emit('navigate', link.url)"
                >
                    {{ link.display }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
