<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    productos: {
        type: Array,
        default: () => [],
    },
    clientes: {
        type: Array,
        default: () => [],
    },
    dolarBlue: {
        type: [Number, String, null],
        default: null,
    },
})

const carrito = ref([])
const busqueda = ref('')
const clienteSeleccionado = ref(null)
const detallesVisibles = ref({})
const monedaCobro = ref('ARS')

const productosFiltrados = computed(() => {
    if (!busqueda.value) return props.productos
    const termino = busqueda.value.toLowerCase()
    return props.productos.filter(
        (producto) =>
            producto.name.toLowerCase().includes(termino) ||
            producto.sku.toLowerCase().includes(termino)
    )
})

const totalVenta = computed(() =>
    carrito.value.reduce((total, item) => total + item.cantidad * item.precio_unitario, 0)
)

const dolarBlueNumber = computed(() => {
    const n = parseFloat(props.dolarBlue)
    return isNaN(n) ? 0 : n
})

const totalUSD = computed(() => {
    if (!dolarBlueNumber.value || dolarBlueNumber.value <= 0) return 0
    return totalVenta.value / dolarBlueNumber.value
})

const form = useForm({
    items: [],
    total: 0,
    client_id: null,
    moneda_cobro: 'ARS',
    exchange_rate_used: null,
})

const sincronizarFormulario = () => {
    form.items = carrito.value.map((item) => ({
        product_id: item.id,
        cantidad: item.cantidad,
        precio_unitario: item.precio_unitario,
    }))
    form.total = totalVenta.value
    form.client_id = clienteSeleccionado.value
    form.moneda_cobro = monedaCobro.value
    form.exchange_rate_used = monedaCobro.value === 'USD' ? dolarBlueNumber.value : null
}

const agregarAlCarrito = (producto) => {
    const existente = carrito.value.find((item) => item.id === producto.id)
    if (existente) {
        if (existente.cantidad < producto.stock_quantity) {
            existente.cantidad += 1
        }
    } else {
        carrito.value.push({
            ...producto,
            cantidad: 1,
            precio_unitario: Number(producto.sale_price),
        })
    }
    sincronizarFormulario()
}

const enCarrito = (id) => carrito.value.some((item) => item.id === id)

const quitarDelCarrito = (productoId) => {
    carrito.value = carrito.value.filter((item) => item.id !== productoId)
    sincronizarFormulario()
}

const actualizarCantidad = (productoId, delta) => {
    const item = carrito.value.find((i) => i.id === productoId)
    if (!item) return
    const nuevaCantidad = item.cantidad + delta
    if (nuevaCantidad <= 0) {
        quitarDelCarrito(productoId)
        return
    }
    if (nuevaCantidad > item.stock_quantity) return
    item.cantidad = nuevaCantidad
    sincronizarFormulario()
}

const toggleDescripcion = (id) => {
    detallesVisibles.value[id] = !detallesVisibles.value[id]
}

const enviarVenta = () => {
    if (!carrito.value.length) return
    sincronizarFormulario()
    form.post(route('sales.store'), {
        preserveScroll: true,
        onSuccess: () => {
            carrito.value = []
            form.reset()
            clienteSeleccionado.value = null
            monedaCobro.value = 'ARS'
        },
    })
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    const productId = params.get('product')
    if (productId) {
        const prod = props.productos.find((p) => String(p.id) === String(productId))
        if (prod && prod.stock_quantity > 0) {
            agregarAlCarrito(prod)
        }
    }
})
</script>

<template>
    <Head title="Punto de Venta" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Punto de Venta</h2>
                <p class="text-sm text-gray-500">Selecciona productos y genera la venta.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-12 gap-6">
                    <!-- Catalogo -->
                    <div class="col-span-12 space-y-4 lg:col-span-8">
                        <div>
                            <input
                                v-model="busqueda"
                                type="text"
                                class="ui-input text-lg"
                                placeholder="Buscar por nombre o SKU"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="producto in productosFiltrados"
                                :key="producto.id"
                                :class="[
                                    'relative ui-card cursor-pointer active:scale-95 duration-150 h-full',
                                    producto.stock_quantity <= 0
                                        ? 'opacity-50 grayscale cursor-not-allowed'
                                        : 'hover:-translate-y-0.5',
                                    enCarrito(producto.id) ? 'ring-2 ring-brand-purple/40 border-brand-purple' : '',
                                ]"
                                @click="producto.stock_quantity > 0 && agregarAlCarrito(producto)"
                            >
                                <span
                                    v-if="enCarrito(producto.id)"
                                    class="absolute right-3 top-3 rounded-full bg-accent-indigo px-2 py-1 text-[11px] font-semibold text-white shadow"
                                >
                                    En carrito
                                </span>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-gray-700">
                                        {{ producto.sku }}
                                    </p>
                                    <span
                                        class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600"
                                    >
                                        Stock: {{ producto.stock_quantity }}
                                    </span>
                                </div>
                                <h3 class="mt-2 text-lg font-bold text-gray-900">
                                    {{ producto.name }}
                                </h3>
                                    <div class="mt-2 h-36 w-full overflow-hidden rounded-md bg-gray-50">
                                        <img
                                            v-if="producto.image_path"
                                            :src="`/storage/${producto.image_path}`"
                                            alt="Producto"
                                            class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full items-center justify-center text-brand-purple-soft text-sm"
                                    >
                                        Sin imagen
                                    </div>
                                </div>
                                <p class="mt-2 font-semibold text-indigo-600">
                                    ${{ Number(producto.sale_price).toFixed(2) }}
                                </p>
                                <p v-if="producto.description" class="mt-1 text-xs text-gray-500 line-clamp-2">
                                    {{ producto.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket -->
                    <div class="col-span-12 lg:col-span-4">
                        <div class="sticky top-4 ui-card-elevated p-6">
                            <h3 class="text-lg font-semibold text-gray-800">Ticket</h3>
                            <div class="mt-3 space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700" for="cliente">
                                        Cliente
                                    </label>
                                    <select
                                        id="cliente"
                                        v-model="clienteSeleccionado"
                                        class="ui-input h-12 text-lg"
                                    >
                                        <option :value="null">Consumidor Final</option>
                                        <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                            {{ cliente.name }}
                                        </option>
                                    </select>
                                </div>

                                <div
                                    v-if="!carrito.length"
                                    class="rounded border border-dashed border-gray-300 p-4 text-sm text-gray-500"
                                >
                                    Aun no hay productos en el carrito.
                                </div>

                                <div
                                    v-for="item in carrito"
                                    :key="item.id"
                                    class="flex items-start justify-between rounded border border-gray-200 p-3"
                                    :title="item.description || 'Sin descripción'"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ item.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ item.sku }} · ${{ item.precio_unitario.toFixed(2) }}
                                        </p>
                                        <button
                                            v-if="item.description"
                                            type="button"
                                            class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800"
                                            @click="toggleDescripcion(item.id)"
                                        >
                                            {{ detallesVisibles[item.id] ? 'Ocultar descripción' : 'Ver descripción' }}
                                        </button>
                                        <p
                                            v-if="item.description && detallesVisibles[item.id]"
                                            class="text-[11px] text-gray-600"
                                        >
                                            {{ item.description }}
                                        </p>
                                        <div class="flex items-center gap-2 text-xs text-gray-600">
                                            <button
                                                type="button"
                                                class="rounded bg-gray-100 px-2 py-1 hover:bg-gray-200"
                                                @click="actualizarCantidad(item.id, -1)"
                                            >
                                                -
                                            </button>
                                            <span class="font-semibold">{{ item.cantidad }}</span>
                                            <button
                                                type="button"
                                                class="rounded bg-gray-100 px-2 py-1 hover:bg-gray-200"
                                                @click="actualizarCantidad(item.id, 1)"
                                            >
                                                +
                                            </button>
                                            <span class="text-gray-400">/</span>
                                            <span>Stock {{ item.stock_quantity }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-800">
                                            ${{ (item.cantidad * item.precio_unitario).toFixed(2) }}
                                        </p>
                                        <button
                                            type="button"
                                            class="mt-2 text-sm font-semibold text-red-600 hover:text-red-700"
                                            @click="quitarDelCarrito(item.id)"
                                        >
                                            X
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 border-t border-gray-200 pt-4 space-y-2">
                                <div class="flex items-center justify-between text-lg font-semibold text-gray-800">
                                    <span>Total (ARS)</span>
                                    <span class="text-3xl font-bold text-gray-900">
                                        ${{ totalVenta.toFixed(2) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label for="moneda" class="text-sm font-medium text-gray-700">Moneda de cobro</label>
                                    <select
                                        id="moneda"
                                        v-model="monedaCobro"
                                        class="ui-input h-12 text-lg"
                                        @change="sincronizarFormulario"
                                    >
                                        <option value="ARS">ARS</option>
                                        <option value="USD" :disabled="!dolarBlueNumber || dolarBlueNumber <= 0">USD (Blue)</option>
                                    </select>
                                </div>
                                <div v-if="monedaCobro === 'USD'" class="text-sm text-gray-700">
                                    <p>Tasa Blue: {{ dolarBlueNumber ? dolarBlueNumber.toFixed(2) : 'N/D' }}</p>
                                    <p class="font-semibold">
                                        Total en USD: <span class="text-lg font-bold text-indigo-700">{{ totalUSD.toFixed(2) }}</span>
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="ui-btn-primary w-full h-14 text-xl font-bold"
                                :disabled="!carrito.length || form.processing"
                                @click="enviarVenta"
                            >
                                COBRAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
