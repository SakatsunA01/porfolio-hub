<script setup>
import BaseTable from '@/Components/BaseTable.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    venta: {
        type: Object,
        required: true,
    },
})

const headers = ['Producto', 'Cantidad', 'PrecioUnitario', 'Subtotal']
const items = computed(() => props.venta?.items ?? [])

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
</script>

<template>
    <Head :title="`Venta #${venta.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Venta #{{ venta.id }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Fecha: {{ formatearFecha(venta.created_at) }} - Total: {{ formatearMoneda(venta.total_amount) }}
                    </p>
                </div>
                <Link
                    :href="route('sales.index')"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                >
                    ← Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-4 grid gap-3 text-sm text-gray-700 sm:grid-cols-3">
                        <div>
                            <p class="font-semibold text-gray-800">ID</p>
                            <p>#{{ venta.id }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Vendedor</p>
                            <p>{{ venta.user?.name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Metodo de pago</p>
                            <p>{{ venta.payment_method ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Cliente</p>
                            <p>{{ venta.client?.name ?? 'Consumidor Final' }}</p>
                        </div>
                    </div>

                    <BaseTable :headers="headers" :items="items">
                        <template #Producto="{ item }">
                            {{ item.product?.name ?? item.product_name ?? 'N/A' }}
                        </template>
                        <template #Cantidad="{ item }">
                            {{ item.quantity }}
                        </template>
                        <template #PrecioUnitario="{ item }">
                            {{ formatearMoneda(item.unit_price) }}
                        </template>
                        <template #Subtotal="{ item }">
                            {{ formatearMoneda(item.total_price) }}
                        </template>
                    </BaseTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
