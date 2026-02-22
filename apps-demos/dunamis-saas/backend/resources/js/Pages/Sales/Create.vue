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
                <p class="text-sm text-gray-500">Selecciona productos y confirma el ticket.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="space-y-4">
                        <div class="ui-card p-4 sm:p-5">
                            <input
                                v-model="busqueda"
                                type="text"
                                class="ui-input"
                                placeholder="Buscar por nombre o SKU"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="producto in productosFiltrados"
                                :key="producto.id"
                                :class="[
                                    'group relative overflow-hidden rounded-xl border border-surface-border bg-white shadow-sm transition duration-150',
                                    producto.stock_quantity <= 0
                                        ? 'cursor-not-allowed opacity-55 grayscale'
                                        : 'cursor-pointer hover:-translate-y-0.5 hover:shadow-soft',
                                    enCarrito(producto.id) ? 'ring-2 ring-brand-purple/40' : '',
                                ]"
                                @click="producto.stock_quantity > 0 && agregarAlCarrito(producto)"
                            >
                                <span
                                    v-if="enCarrito(producto.id)"
                                    class="absolute right-3 top-3 rounded-full bg-brand-purple px-2 py-1 text-[11px] font-semibold text-white shadow"
                                >
                                    En carrito
                                </span>

                                <div class="flex items-center justify-between p-4 pb-3">
                                    <p class="text-xs font-semibold tracking-wide text-gray-600">
                                        {{ producto.sku }}
                                    </p>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        Stock: {{ producto.stock_quantity }}
                                    </span>
                                </div>

                                <div class="px-4 pb-4">
                                    <h3 class="line-clamp-1 text-lg font-bold text-slate-900">
                                        {{ producto.name }}
                                    </h3>

                                    <div class="mt-3 aspect-video w-full overflow-hidden rounded-lg border border-slate-200/70 bg-slate-50">
                                        <img
                                            v-if="producto.image_path"
                                            :src="`/storage/${producto.image_path}`"
                                            alt="Producto"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full items-center justify-center text-sm text-brand-purple-soft"
                                        >
                                            Sin imagen
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-end justify-between gap-2">
                                        <p class="text-lg font-bold text-emerald-700">
                                            ${{ Number(producto.sale_price).toFixed(2) }}
                                        </p>
                                        <p v-if="producto.description" class="line-clamp-1 text-xs text-slate-500">
                                            {{ producto.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside>
                        <div class="ui-card-elevated sticky top-24 p-5 sm:p-6">
                            <h3 class="text-xl font-semibold text-slate-900">Ticket</h3>

                            <div class="mt-3 space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-slate-700" for="cliente">Cliente</label>
                                    <select id="cliente" v-model="clienteSeleccionado" class="ui-input mt-1">
                                        <option :value="null">Consumidor Final</option>
                                        <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                            {{ cliente.name }}
                                        </option>
                                    </select>
                                </div>

                                <div
                                    v-if="!carrito.length"
                                    class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500"
                                >
                                    Aun no hay productos en el carrito.
                                </div>

                                <div
                                    v-for="item in carrito"
                                    :key="item.id"
                                    class="flex items-start justify-between rounded-lg border border-slate-200 p-3"
                                    :title="item.description || 'Sin descripcion'"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-slate-800">{{ item.name }}</p>
                                        <p class="text-xs text-slate-500">{{ item.sku }} · ${{ item.precio_unitario.toFixed(2) }}</p>

                                        <button
                                            v-if="item.description"
                                            type="button"
                                            class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800"
                                            @click="toggleDescripcion(item.id)"
                                        >
                                            {{ detallesVisibles[item.id] ? 'Ocultar descripcion' : 'Ver descripcion' }}
                                        </button>

                                        <p v-if="item.description && detallesVisibles[item.id]" class="text-[11px] text-slate-600">
                                            {{ item.description }}
                                        </p>

                                        <div class="flex items-center gap-2 text-xs text-slate-600">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[30px] min-w-[30px] items-center justify-center rounded bg-slate-100 px-2 py-1 hover:bg-slate-200"
                                                @click="actualizarCantidad(item.id, -1)"
                                            >
                                                -
                                            </button>
                                            <span class="font-semibold">{{ item.cantidad }}</span>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[30px] min-w-[30px] items-center justify-center rounded bg-slate-100 px-2 py-1 hover:bg-slate-200"
                                                @click="actualizarCantidad(item.id, 1)"
                                            >
                                                +
                                            </button>
                                            <span class="text-slate-400">/</span>
                                            <span>Stock {{ item.stock_quantity }}</span>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-800">
                                            ${{ (item.cantidad * item.precio_unitario).toFixed(2) }}
                                        </p>
                                        <button
                                            type="button"
                                            class="mt-2 text-sm font-semibold text-red-600 hover:text-red-700"
                                            @click="quitarDelCarrito(item.id)"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3 border-t border-slate-200 pt-4">
                                <div class="flex items-center justify-between text-lg font-semibold text-slate-800">
                                    <span>Total (ARS)</span>
                                    <span class="text-3xl font-bold text-slate-900">${{ totalVenta.toFixed(2) }}</span>
                                </div>

                                <div>
                                    <label for="moneda" class="text-sm font-medium text-slate-700">Moneda de cobro</label>
                                    <select id="moneda" v-model="monedaCobro" class="ui-input mt-1" @change="sincronizarFormulario">
                                        <option value="ARS">ARS</option>
                                        <option value="USD" :disabled="!dolarBlueNumber || dolarBlueNumber <= 0">USD (Blue)</option>
                                    </select>
                                </div>

                                <div v-if="monedaCobro === 'USD'" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                    <p>Tasa Blue: {{ dolarBlueNumber ? dolarBlueNumber.toFixed(2) : 'N/D' }}</p>
                                    <p class="font-semibold">
                                        Total en USD:
                                        <span class="text-lg font-bold text-indigo-700">{{ totalUSD.toFixed(2) }}</span>
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="ui-btn-primary mt-4 w-full text-base font-bold"
                                :disabled="!carrito.length || form.processing"
                                @click="enviarVenta"
                            >
                                COBRAR
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
