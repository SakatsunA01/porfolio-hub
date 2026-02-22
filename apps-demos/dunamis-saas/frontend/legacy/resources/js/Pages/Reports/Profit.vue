<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BaseCard from '@/Components/BaseCard.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

const props = defineProps({
    ventas: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({ from: '', to: '' }),
    },
})

const filtros = reactive({
    from: props.filters.from || '',
    to: props.filters.to || '',
})

const formatearMoneda = (valor) => {
    const numero = Number(valor ?? 0)
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(numero)
}

const aplicarFiltros = () => {
    router.get(route('reports.profit'), { from: filtros.from, to: filtros.to }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Reporte de Ganancias" />

    <AuthenticatedLayout>
        <div class="bg-neutral-light-2 py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-primary-dark">Reporte de Ganancias</h1>
                        <p class="text-sm text-neutral-light-1">Margen y costos por venta, con filtro por rango de fechas.</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex flex-col">
                            <label class="text-xs font-semibold text-secondary-dark" for="from">Desde</label>
                            <input
                                id="from"
                                type="date"
                                v-model="filtros.from"
                                class="ui-input"
                            />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-semibold text-secondary-dark" for="to">Hasta</label>
                            <input
                                id="to"
                                type="date"
                                v-model="filtros.to"
                                class="ui-input"
                            />
                        </div>
                        <PrimaryButton type="button" @click="aplicarFiltros">
                            Filtrar
                        </PrimaryButton>
                    </div>
                </div>

                <BaseCard class="p-0">
                    <div class="border-b border-neutral-light-2/80 px-6 py-4">
                        <h2 class="text-lg font-semibold text-secondary-dark">Detalle de ventas</h2>
                    </div>
                    <div class="ui-table-wrap">
                        <table class="ui-table text-secondary-dark">
                            <thead>
                                <tr>
                                    <th class="ui-th">Fecha</th>
                                    <th class="ui-th">Venta #</th>
                                    <th class="ui-th">Cliente</th>
                                    <th class="ui-th text-right">Total Venta</th>
                                    <th class="ui-th text-right">Costo Total</th>
                                    <th class="ui-th text-right">Ganancia</th>
                                    <th class="ui-th text-right">Margen %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!ventas?.data?.length">
                                    <td colspan="7" class="px-4 py-6 text-center text-neutral-light-1">
                                        No hay ventas en este rango.
                                    </td>
                                </tr>
                                <tr
                                    v-for="venta in ventas.data"
                                    :key="venta.id"
                                    class="ui-row-hover bg-white/70"
                                >
                                    <td class="ui-td">
                                        {{ new Date(venta.created_at).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </td>
                                    <td class="ui-td">#{{ venta.id }}</td>
                                    <td class="ui-td">{{ venta.cliente }}</td>
                                    <td class="ui-td text-right font-semibold">{{ formatearMoneda(venta.total) }}</td>
                                    <td class="ui-td text-right">{{ formatearMoneda(venta.costo_total) }}</td>
                                    <td class="ui-td text-right" :class="venta.ganancia >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ formatearMoneda(venta.ganancia) }}
                                    </td>
                                    <td class="ui-td text-right" :class="venta.margen >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ venta.margen.toFixed(2) }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="ventas?.links" class="flex items-center justify-between px-6 py-4 text-sm">
                        <div class="text-neutral-light-1">
                            Mostrando {{ ventas.from }} - {{ ventas.to }} de {{ ventas.total }}
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-for="link in ventas.links"
                                :key="link.label"
                                v-html="link.label"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url, {}, { preserveState: true })"
                                class="rounded-lg px-3 py-1 text-sm"
                                :class="[
                                    link.active ? 'bg-accent-indigo text-white' : 'bg-white text-secondary-dark border border-neutral-light-2',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-neutral-light-2/60',
                                ]"
                            />
                        </div>
                    </div>
                </BaseCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
