<script setup>
import { computed } from 'vue'

const props = defineProps({
    headers: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    processing: {
        type: Boolean,
        default: false,
    },
})

const hasData = computed(() => props.items && props.items.length > 0)
</script>

<template>
    <div class="ui-table-wrap transition-opacity" :class="{ 'opacity-50 pointer-events-none': processing }">
        <table class="ui-table">
            <thead>
                <tr>
                    <th
                        v-for="(header, index) in headers"
                        :key="`header-${index}`"
                        scope="col"
                        class="ui-th"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-if="hasData">
                    <tr
                        v-for="(item, rowIndex) in items"
                        :key="rowIndex"
                        class="ui-row-hover"
                    >
                        <td
                            v-for="(header, colIndex) in headers"
                            :key="`cell-${rowIndex}-${colIndex}`"
                            class="ui-td"
                            :data-label="header"
                        >
                            <slot
                                :name="header"
                                :item="item"
                                :value="item[header]"
                                :row-index="rowIndex"
                                :col-index="colIndex"
                            >
                                {{ item[header] }}
                            </slot>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td
                        :colspan="headers.length || 1"
                        class="px-4 py-6 text-center text-text-muted"
                    >
                        No hay datos
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
