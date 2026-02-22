<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BaseCard from '@/Components/BaseCard.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    ventas_hoy: { type: Number, default: 0 },
    cantidad_ventas_hoy: { type: Number, default: 0 },
    stock_bajo: { type: Number, default: 0 },
    ultimas_ventas: { type: Array, default: () => [] },
    dolar_blue: { type: Number, default: null },
    dolar_blue_actualizado: { type: [String, Date, null], default: null },
})

const formatearMoneda = (valor) => {
    const numero = Number(valor ?? 0)
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(numero)
}

const fechaLegible = computed(() => {
    if (!props.dolar_blue_actualizado) return ''
    const fecha = new Date(props.dolar_blue_actualizado)
    return fecha.toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
})

const cards = computed(() => [
    {
        key: 'ventas',
        titulo: 'Ventas Hoy',
        valor: formatearMoneda(props.ventas_hoy),
        descripcion: 'Ingresos del dia',
        estilo: 'bg-white border border-surface-border text-text-main',
        barra: 'bg-brand-purple',
    },
    {
        key: 'transacciones',
        titulo: 'Transacciones',
        valor: props.cantidad_ventas_hoy,
        descripcion: 'Ventas registradas hoy',
        estilo: 'bg-white border border-surface-border text-text-main',
        barra: 'bg-brand-navy',
    },
    {
        key: 'stock',
        titulo: 'Alertas Stock',
        valor: props.stock_bajo,
        descripcion: 'Productos bajo minimo',
        estilo: 'bg-white border border-surface-border text-text-main',
        barra: props.stock_bajo > 0 ? 'bg-red-400' : 'bg-brand-purple-soft',
    },
    {
        key: 'dolar',
        titulo: 'Dolar Blue (Venta)',
        valor: props.dolar_blue && props.dolar_blue > 0 ? formatearMoneda(props.dolar_blue) : 'Sin datos',
        descripcion: props.dolar_blue && props.dolar_blue > 0 ? `Actualizado: ${fechaLegible.value || 'Sin fecha'}` : 'Cotizacion no disponible',
        estilo: 'bg-white border border-surface-border text-text-main',
        barra: 'bg-brand-purple',
        mostrarAccion: true,
    },
])

const cargandoDolar = ref(false)
const actualizarDolar = () => {
    cargandoDolar.value = true
    router.post(route('dolar.fetch'), {}, {
        preserveScroll: true,
        onFinish: () => { cargandoDolar.value = false },
    })
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="bg-canvas py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <BaseCard
                        v-for="card in cards"
                        :key="card.key"
                        class="relative overflow-hidden"
                        :class="card.estilo"
                    >
                        <div class="absolute left-0 top-0 h-full w-1.5" :class="card.barra"></div>
                        <div class="flex items-start justify-between pl-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">{{ card.titulo }}</p>
                                <p class="mt-3 text-4xl font-extrabold leading-tight text-text-main">
                                    {{ card.valor }}
                                </p>
                            </div>
                            <button
                                v-if="card.mostrarAccion"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-surface-border bg-white px-3 py-1 text-xs font-semibold text-brand-navy shadow-sm hover:bg-brand-purple-soft/30 disabled:cursor-not-allowed disabled:opacity-60 transition"
                                :disabled="cargandoDolar"
                                @click="actualizarDolar"
                            >
                                <svg
                                    v-if="cargandoDolar"
                                    class="h-4 w-4 animate-spin text-brand-navy"
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
                                <span>Actualizar</span>
                            </button>
                        </div>
                        <p class="mt-4 text-sm text-text-muted">
                            {{ card.descripcion }}
                        </p>
                    </BaseCard>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <BaseCard class="lg:col-span-2">
                        <div class="flex items-center justify-between border-b border-surface-border pb-4">
                            <h3 class="text-lg font-semibold text-text-main">Ultimas Ventas</h3>
                        </div>
                        <div class="pt-4">
                            <div class="overflow-x-auto">
                                <table class="ui-table">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left">Hora</th>
                                            <th class="px-3 py-2 text-left">Cliente</th>
                                            <th class="px-3 py-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!ultimas_ventas.length">
                                            <td colspan="3" class="px-3 py-3 text-center text-text-muted">
                                                No hay ventas recientes.
                                            </td>
                                        </tr>
                                        <tr v-for="venta in ultimas_ventas" :key="venta.id">
                                            <td class="px-3 py-2">
                                                {{ new Date(venta.created_at).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }) }}
                                            </td>
                                            <td class="px-3 py-2">
                                                {{ venta.client?.name ?? 'Consumidor Final' }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-semibold">
                                                {{ formatearMoneda(venta.total_amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </BaseCard>

                    <BaseCard>
                        <div class="border-b border-surface-border pb-4">
                            <h3 class="text-lg font-semibold text-text-main">Accesos Rapidos</h3>
                        </div>
                        <div class="space-y-3 pt-4">
                            <Link
                                :href="route('sales.create')"
                                class="ui-btn-primary w-full justify-center"
                            >
                                Nueva Venta
                            </Link>
                            <Link
                                :href="route('products.index', { open: 'create' })"
                                class="ui-btn-secondary w-full justify-center"
                            >
                                Nuevo Producto
                            </Link>
                            <Link
                                :href="route('clients.index', { open: 'create' })"
                                class="ui-btn-secondary w-full justify-center"
                            >
                                Nuevo Cliente
                            </Link>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
