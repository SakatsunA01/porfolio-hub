<script setup>
import BaseTable from '@/Components/BaseTable.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ClientForm from './Partials/ClientForm.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    clients: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = ['Nombre', 'CUIT', 'Telefono', 'Acciones']
const clientes = computed(() => props.clients?.data ?? [])
const links = computed(() => props.clients?.links ?? [])

const busqueda = ref(props.filters?.search ?? '')
let timeoutBusqueda

watch(busqueda, (valor) => {
    clearTimeout(timeoutBusqueda)
    timeoutBusqueda = setTimeout(() => {
        router.get(route('clients.index'), { search: valor }, { preserveState: true, replace: true })
    }, 250)
})

onBeforeUnmount(() => clearTimeout(timeoutBusqueda))

const mostrarFormulario = ref(false)
const clienteEditando = ref(null)

const abrirCrear = () => {
    clienteEditando.value = null
    mostrarFormulario.value = true
}

const abrirEditar = (cliente) => {
    clienteEditando.value = cliente
    mostrarFormulario.value = true
}

const cerrarFormulario = () => {
    mostrarFormulario.value = false
}

const alGuardar = () => {
    mostrarFormulario.value = false
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    if (params.get('open') === 'create') {
        abrirCrear()
    }
})
</script>

<template>
    <Head title="Clientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Clientes</h2>
                    <p class="text-sm text-gray-500">Administra tus clientes.</p>
                </div>
                <PrimaryButton type="button" @click="abrirCrear">
                    Nuevo Cliente
                </PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="w-full sm:max-w-xs">
                                <label class="text-sm font-medium text-gray-700" for="search">Buscar</label>
                                <input
                                    id="search"
                                    v-model="busqueda"
                                    type="text"
                                    class="ui-input mt-1"
                                    placeholder="Nombre, CUIT o Email"
                                />
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ clients.total ?? 0 }} cliente{{ (clients.total ?? 0) === 1 ? '' : 's' }}
                            </div>
                        </div>

                        <div class="mt-6">
                            <BaseTable :headers="headers" :items="clientes">
                                <template #Nombre="{ item }">
                                    {{ item.name }}
                                </template>
                                <template #CUIT="{ item }">
                                    {{ item.tax_id || '-' }}
                                </template>
                                <template #Telefono="{ item }">
                                    {{ item.phone || '-' }}
                                </template>
                                <template #Acciones="{ item }">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="ui-inline-action-primary"
                                            @click="abrirEditar(item)"
                                        >
                                            Editar
                                        </button>
                                        <Link
                                            as="button"
                                            method="delete"
                                            :href="route('clients.destroy', item.id)"
                                            class="ui-inline-action-danger"
                                            preserve-scroll
                                        >
                                            Eliminar
                                        </Link>
                                    </div>
                                </template>
                            </BaseTable>
                        </div>

                        <div
                            v-if="links.length"
                            class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="text-sm text-gray-500">
                                Mostrando {{ clients.from ?? 0 }} - {{ clients.to ?? 0 }} de {{ clients.total ?? 0 }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="link in links"
                                    :key="link.url ?? link.label"
                                    :href="link.url || '#'"
                                    class="rounded border px-3 py-2 text-sm"
                                    :class="[
                                        link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-700',
                                        !link.url ? 'cursor-not-allowed text-gray-400' : 'hover:border-gray-300',
                                    ]"
                                    :preserve-scroll="true"
                                    preserve-state
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <ClientForm
                    v-if="mostrarFormulario"
                    :client="clienteEditando"
                    @submitted="alGuardar"
                    @cancel="cerrarFormulario"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
