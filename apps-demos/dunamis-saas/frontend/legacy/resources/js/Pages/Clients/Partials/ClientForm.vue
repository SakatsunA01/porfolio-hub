<script setup>
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

const props = defineProps({
    client: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['submitted', 'cancel'])

const form = useForm({
    name: '',
    tax_id: '',
    email: '',
    phone: '',
    address: '',
})

const editando = computed(() => Boolean(props.client?.id))

watch(
    () => props.client,
    (cliente) => {
        form.clearErrors()
        form.name = cliente?.name ?? ''
        form.tax_id = cliente?.tax_id ?? ''
        form.email = cliente?.email ?? ''
        form.phone = cliente?.phone ?? ''
        form.address = cliente?.address ?? ''
    },
    { immediate: true }
)

const guardarCliente = () => {
    if (editando.value) {
        form.put(route('clients.update', props.client.id), {
            preserveScroll: true,
            onSuccess: () => emit('submitted'),
        })
    } else {
        form.post(route('clients.store'), {
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
    <div class="fixed inset-0 z-50 flex h-full w-full items-center justify-center bg-gray-600 bg-opacity-50 overflow-y-auto">
        <div class="relative mx-auto w-full max-w-2xl rounded-md border bg-white p-5 shadow-lg">
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ editando ? 'Editar cliente' : 'Nuevo cliente' }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        Completa los campos para {{ editando ? 'actualizar' : 'crear' }} el cliente.
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

            <form @submit.prevent="guardarCliente" class="mt-4 space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="name" value="Nombre" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :error="form.errors.name"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="tax_id" value="CUIT/DNI" />
                        <TextInput
                            id="tax_id"
                            v-model="form.tax_id"
                            type="text"
                            class="mt-1 block w-full"
                            :error="form.errors.tax_id"
                        />
                        <InputError :message="form.errors.tax_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            :error="form.errors.email"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="phone" value="Teléfono" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            class="mt-1 block w-full"
                            :error="form.errors.phone"
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="address" value="Dirección" />
                        <TextInput
                            id="address"
                            v-model="form.address"
                            type="text"
                            class="mt-1 block w-full"
                            :error="form.errors.address"
                        />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="emit('cancel')">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing" :processing="form.processing">
                        {{ editando ? 'Actualizar' : 'Crear' }} cliente
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>
