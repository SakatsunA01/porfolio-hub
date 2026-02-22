<script setup>
import BaseTable from '@/Components/BaseTable.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    ventas: {
        type: Object,
        required: true,
    },
})

const headers = ['ID', 'Fecha', 'Vendedor', 'Cliente', 'Total', 'payment_method', 'Acciones']
const datos = computed(() => props.ventas?.data ?? [])
const links = computed(() => props.ventas?.links ?? [])
const cargando = ref(false)

watch(
    () => props.ventas,
    () => {
        cargando.value = false
    }
)

const formatearFecha = (fecha) => {
    if (!fecha) return ''
    const date = new Date(fecha)
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

const formatearMoneda = (valor) => {
    const numero = Number(valor ?? 0)
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(numero)
}

const irAPagina = (url) => {
    if (!url) return
    cargando.value = true
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <Head title="Historial de Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Historial de Ventas</h2>
                    <p class="text-sm text-gray-500">Revisa las ventas mas recientes.</p>
                </div>
                <Link
                    :href="route('sales.create')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    Nueva venta
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-4">
                        <BaseTable :headers="headers" :items="datos" :processing="cargando">
                            <template #ID="{ item }">
                                {{ item.id }}
                            </template>
                            <template #Fecha="{ item }">
                                {{ formatearFecha(item.created_at) }}
                            </template>
                            <template #Vendedor="{ item }">
                                {{ item.user?.name ?? 'N/A' }}
                            </template>
                            <template #Cliente="{ item }">
                                {{ item.client?.name ?? 'Consumidor Final' }}
                            </template>
                            <template #Total="{ item }">
                                <StatusBadge type="success" :text="formatearMoneda(item.total_amount)" />
                            </template>
                            <template #payment_method="{ item }">
                                {{ item.payment_method ?? 'N/A' }}
                            </template>
                            <template #Acciones="{ item }">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('sales.show', item.id)"
                                        class="inline-flex items-center text-indigo-600 hover:text-indigo-800"
                                    >
                                        Ver detalle
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <Link
                                        :href="route('sales.ticket', item.id)"
                                        class="inline-flex items-center text-green-600 hover:text-green-800"
                                    >
                                        Imprimir
                                    </Link>
                                </div>
                            </template>
                        </BaseTable>
                    </div>
                </div>

                <div v-if="links.length" class="space-y-2 text-sm text-gray-500">
                    <div>
                        Mostrando {{ props.ventas.from ?? 0 }} - {{ props.ventas.to ?? 0 }} de {{ props.ventas.total ?? 0 }}
                    </div>
                    <Pagination :links="links" @navigate="irAPagina" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
