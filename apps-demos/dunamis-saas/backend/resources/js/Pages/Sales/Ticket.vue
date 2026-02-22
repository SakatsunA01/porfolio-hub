<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

const props = defineProps({
    venta: {
        type: Object,
        required: true,
    },
    organizacion: {
        type: Object,
        default: null,
    },
})

const total = computed(() => Number(props.venta?.total_amount ?? 0))

const formatearMoneda = (valor) => {
    const numero = Number(valor ?? 0)
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(numero)
}

const fechaVenta = computed(() => {
    if (!props.venta?.created_at) return ''
    const fecha = new Date(props.venta.created_at)
    return fecha.toLocaleString('es-AR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
})

onMounted(() => {
    window.print()
})
</script>

<template>
    <Head :title="`Ticket #${venta.id}`" />
    <div class="min-h-screen bg-gray-100 py-8 print:bg-white">
        <div class="mx-auto max-w-sm rounded-lg bg-white p-6 shadow print:shadow-none">
            <div class="text-center">
                <div class="text-lg font-bold text-gray-900">
                    {{ organizacion?.name ?? 'Mi Organización' }}
                </div>
                <p class="text-xs text-gray-500">
                    Ticket #{{ venta.id.toString().padStart(4, '0') }}
                </p>
            </div>

            <div class="mt-4 text-xs text-gray-700">
                <p>Fecha: {{ fechaVenta }}</p>
                <p>Cliente: {{ venta.client?.name ?? 'Consumidor Final' }}</p>
                <p v-if="venta.client?.tax_id">DNI/CUIT: {{ venta.client.tax_id }}</p>
            </div>

            <div class="my-4 border-t border-dashed border-gray-300"></div>

            <div>
                <table class="w-full text-xs text-gray-800">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <th class="py-2 text-left">Cant</th>
                            <th class="py-2 text-left">Producto</th>
                            <th class="py-2 text-right">Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in venta.items" :key="item.id" class="border-b border-gray-100">
                            <td class="py-1 align-top">{{ item.quantity }}</td>
                            <td class="py-1 align-top">
                                {{ item.product?.name ?? item.product_name }}
                            </td>
                            <td class="py-1 text-right align-top">
                                {{ formatearMoneda(item.total_price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-base font-bold text-gray-900">
                <span>Total</span>
                <span>{{ formatearMoneda(total) }}</span>
            </div>

            <div class="mt-6 text-center text-xs text-gray-500">
                ¡Gracias por su compra!
            </div>

            <div class="mt-6 flex justify-between text-sm print:hidden">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 shadow-sm hover:bg-gray-50"
                    @click="router.visit(route('sales.index'))"
                >
                    Volver
                </button>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-indigo-700"
                    @click="window.print()"
                >
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</template>
