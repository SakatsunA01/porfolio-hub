<script setup>
import BaseTable from '@/Components/BaseTable.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ProductForm from './Partials/ProductForm.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    tasaBlue: {
        type: [Number, String],
        default: 0,
    },
})

const products = computed(() => props.products ?? {})
const headers = ['IMAGEN', 'SKU', 'Nombre', 'Precio', 'Stock', 'Estado', 'Acciones', 'VentaRapida']
const productsData = computed(() => products.value?.data ?? [])
const paginationLinks = computed(() => products.value?.links ?? [])
const cargando = ref(false)

const search = ref(props.filters?.search ?? '')
let searchTimeout

watch(search, (value) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        cargando.value = true
        router.get(route('products.index'), { search: value }, {
            preserveState: true,
            replace: true,
            onFinish: () => { cargando.value = false },
        })
    }, 250)
})

onBeforeUnmount(() => {
    clearTimeout(searchTimeout)
})

const showForm = ref(false)
const editingProduct = ref(null)

const openCreate = () => {
    editingProduct.value = null
    showForm.value = true
}

const openEdit = (product) => {
    editingProduct.value = product
    showForm.value = true
}

const closeForm = () => {
    showForm.value = false
}

const handleSubmitted = () => {
    showForm.value = false
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    if (params.get('open') === 'create') {
        openCreate()
    }
})

const badgeFor = (product) => {
    const min = Number(product.min_stock_alert ?? 0)
    const stock = Number(product.stock_quantity ?? 0)
    if (stock <= 0) return { type: 'danger', text: 'Sin stock' }
    if (min > 0 && stock <= min) return { type: 'danger', text: 'Crítico' }
    if (min > 0 && stock < min * 2) return { type: 'warning', text: 'Stock medio' }
    return { type: 'success', text: 'Stock óptimo' }
}

const formatPrice = (value) => {
    const numberValue = Number(value ?? 0)
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(numberValue)
}

const destroyProduct = (product) => {
    if (confirm('¿Deseas eliminar este producto?')) {
        router.delete(route('products.destroy', product.id), {
            preserveScroll: true,
        })
    }
}

const irAPagina = (url) => {
    if (!url) return
    cargando.value = true
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { cargando.value = false },
    })
}
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Productos</h2>
                    <p class="text-sm text-gray-500">Gestiona el catálogo de tu organización.</p>
                </div>
                <PrimaryButton type="button" @click="openCreate">
                    Nuevo producto
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="w-full sm:max-w-xs">
                                <label class="text-sm font-medium text-gray-700" for="search">Buscar</label>
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    name="search"
                                    placeholder="SKU o nombre"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ products.total ?? 0 }} resultado{{ (products.total ?? 0) === 1 ? '' : 's' }}
                            </div>
                        </div>

                        <div class="mt-6">
                            <BaseTable :headers="headers" :items="productsData" :processing="cargando">
                                <template #IMAGEN="{ item }">
                                    <div class="h-12 w-12 overflow-hidden rounded-full bg-gray-100 flex items-center justify-center">
                                        <img
                                            v-if="item.image_path"
                                            :src="`/storage/${item.image_path}`"
                                            alt="Miniatura"
                                            class="h-full w-full object-cover"
                                        />
                                        <svg
                                            v-else
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            class="h-6 w-6 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14" />
                                            <path d="M3 19a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2" />
                                            <circle cx="12" cy="10" r="3" />
                                            <path d="M3 15s2-2 5-2 4 2 4 2" />
                                        </svg>
                                    </div>
                                </template>
                                <template #SKU="{ item }">
                                    {{ item.sku }}
                                </template>
                                <template #Nombre="{ item }">
                                    {{ item.name }}
                                </template>
                                <template #Precio="{ item }">
                                    {{ formatPrice(item.sale_price) }}
                                </template>
                                <template #Stock="{ item }">
                                    {{ item.stock_quantity }}
                                </template>
                                <template #Estado="{ item }">
                                    <StatusBadge
                                        :type="badgeFor(item).type"
                                        :text="badgeFor(item).text"
                                    />
                                </template>
                                <template #Acciones="{ item }">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            @click="openEdit(item)"
                                        >
                                            Editar
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button
                                            type="button"
                                            class="text-red-600 hover:text-red-800"
                                            @click="destroyProduct(item)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </template>
                                <template #VentaRapida="{ item }">
                                    <Link
                                        :href="route('sales.create', { product: item.id })"
                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Vender
                                    </Link>
                                </template>
                            </BaseTable>
                        </div>

                        <div class="mt-4 space-y-2" v-if="paginationLinks.length">
                            <div class="text-sm text-gray-500">
                                Mostrando {{ products.from ?? 0 }} - {{ products.to ?? 0 }} de {{ products.total ?? 0 }}
                            </div>
                            <Pagination :links="paginationLinks" @navigate="irAPagina" />
                        </div>
                    </div>
                </div>

                <ProductForm
                    v-if="showForm"
                    :product="editingProduct"
                    :tasa-blue="tasaBlue"
                    @submitted="handleSubmitted"
                    @cancel="closeForm"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
