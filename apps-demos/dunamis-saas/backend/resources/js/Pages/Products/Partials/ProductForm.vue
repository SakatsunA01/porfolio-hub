<script setup>
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    tasaBlue: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits(['submitted', 'cancel'])

const form = useForm({
    sku: '',
    name: '',
    description: '',
    cost_ars: 0,
    cost_usd: null,
    sale_price: 0,
    stock_quantity: 0,
    min_stock_quantity: 0,
    image: null,
})

const isEditing = computed(() => Boolean(props.product?.id))
const vistaPrevia = ref(null)
const tasaNumero = computed(() => Number(props.tasaBlue || 0))

watch(
    () => props.product,
    (product) => {
        form.clearErrors()
        form.sku = product?.sku ?? ''
        form.name = product?.name ?? ''
        form.description = product?.description ?? ''
        form.cost_ars = product?.cost_ars ?? product?.cost_price ?? 0
        form.cost_usd = product?.cost_usd ?? null
        form.sale_price = product?.sale_price ?? 0
        form.stock_quantity = product?.stock_quantity ?? 0
        form.min_stock_quantity = product?.min_stock_quantity ?? 0
        form.image = null
        vistaPrevia.value = product?.image_path ? `/storage/${product.image_path}` : null
    },
    { immediate: true }
)

const margenGanancia = computed(() => {
    const costo = Number(form.cost_ars || 0)
    const venta = Number(form.sale_price || 0)
    if (!costo) return 0
    return ((venta - costo) / costo) * 100
})

const gananciaDinero = computed(() => {
    const costo = Number(form.cost_ars || 0)
    const venta = Number(form.sale_price || 0)
    return venta - costo
})

const colorMargen = computed(() => {
    if (margenGanancia.value < 20) return 'text-red-600'
    if (margenGanancia.value > 30) return 'text-emerald-600'
    return 'text-gray-700'
})

const onFileChange = (e) => {
    const file = e.target.files?.[0]
    form.image = file || null
    if (file) {
        const reader = new FileReader()
        reader.onload = (event) => {
            vistaPrevia.value = event.target?.result
        }
        reader.readAsDataURL(file)
    }
}

const normalizarNumero = (valor) => {
    if (valor === null || valor === undefined || valor === '') return null
    const n = Number(valor)
    return Number.isFinite(n) ? Number(n.toFixed(2)) : null
}

const sincronizarDesdeArs = () => {
    form.cost_ars = normalizarNumero(form.cost_ars) ?? 0
    if (tasaNumero.value > 0 && form.cost_ars !== null) {
        form.cost_usd = Number((Number(form.cost_ars) / tasaNumero.value).toFixed(2))
    }
}

const sincronizarDesdeUsd = () => {
    form.cost_usd = normalizarNumero(form.cost_usd)
    if (tasaNumero.value > 0 && form.cost_usd !== null) {
        form.cost_ars = Number((Number(form.cost_usd) * tasaNumero.value).toFixed(2))
    }
}

const submit = () => {
    if (isEditing.value) {
        form.put(route('products.update', props.product.id), {
            preserveScroll: true,
            onSuccess: () => emit('submitted'),
        })
    } else {
        form.post(route('products.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                emit('submitted')
            },
        })
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex h-full w-full items-center justify-center bg-gray-600 bg-opacity-50 overflow-y-auto px-4">
        <div class="relative mx-auto w-full max-w-3xl rounded-md border bg-white p-6 shadow-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ isEditing ? 'Editar producto' : 'Nuevo producto' }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        Completa los campos para {{ isEditing ? 'actualizar' : 'crear' }} el producto.
                    </p>
                </div>
                <button
                    type="button"
                    class="text-gray-400 transition hover:text-gray-600"
                    @click="emit('cancel')"
                    aria-label="Cerrar"
                >
                    ✕
                </button>
            </div>

            <form @submit.prevent="submit" class="mt-4 space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <InputLabel for="sku" value="SKU" />
                        <TextInput
                            id="sku"
                            v-model="form.sku"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autocomplete="off"
                            :error="form.errors.sku"
                        />
                        <InputError :message="form.errors.sku" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="name" value="Nombre" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autocomplete="off"
                            :error="form.errors.name"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="description" value="Descripción" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="grid gap-4 md:col-span-2 md:grid-cols-2">
                        <div>
                            <InputLabel for="cost_ars" value="Costo ARS" />
                            <TextInput
                                id="cost_ars"
                                v-model="form.cost_ars"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                                :error="form.errors.cost_ars"
                                @blur="sincronizarDesdeArs"
                            />
                            <InputError :message="form.errors.cost_ars" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="cost_usd" value="Costo USD" />
                            <TextInput
                                id="cost_usd"
                                v-model="form.cost_usd"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                :error="form.errors.cost_usd"
                                @blur="sincronizarDesdeUsd"
                            />
                            <InputError :message="form.errors.cost_usd" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="sale_price" value="Precio Venta" />
                            <TextInput
                                id="sale_price"
                                v-model="form.sale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                                :error="form.errors.sale_price"
                                @blur="form.sale_price = normalizarNumero(form.sale_price) ?? 0"
                            />
                            <InputError :message="form.errors.sale_price" class="mt-2" />
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="rounded-md border border-gray-200 p-3">
                            <p class="text-sm font-semibold text-gray-700">Margen de ganancia</p>
                            <p class="mt-1 text-xs text-gray-500">Se calcula automáticamente con costo y precio de venta.</p>
                            <div class="mt-2 flex items-center gap-4">
                                <div>
                                    <span class="text-xs text-gray-500">Porcentaje</span>
                                    <div :class="['text-lg font-bold', colorMargen]">
                                        {{ margenGanancia.toFixed(2) }}%
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Ganancia $</span>
                                    <div class="text-lg font-bold text-gray-800">
                                        ${{ gananciaDinero.toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="stock_quantity" value="Stock" />
                            <TextInput
                                id="stock_quantity"
                                v-model.number="form.stock_quantity"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                required
                                :error="form.errors.stock_quantity"
                            />
                            <InputError :message="form.errors.stock_quantity" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="min_stock_quantity" value="Stock Mínimo" />
                            <TextInput
                                id="min_stock_quantity"
                                v-model.number="form.min_stock_quantity"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                required
                                :error="form.errors.min_stock_quantity"
                            />
                            <InputError :message="form.errors.min_stock_quantity" class="mt-2" />
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="image" value="Imagen" />
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-700"
                            @change="onFileChange"
                        />
                        <InputError :message="form.errors.image" class="mt-2" />
                        <div class="mt-3 h-32 w-32 overflow-hidden rounded-md border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                            <img
                                v-if="vistaPrevia"
                                :src="vistaPrevia"
                                alt="Previsualización"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-xs text-gray-400">Sin imagen</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="emit('cancel')">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing" :processing="form.processing">
                        {{ isEditing ? 'Actualizar' : 'Crear' }} producto
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>
