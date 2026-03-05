<template>
  <form class="space-y-8" @submit.prevent="submitForm">
    <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
      <div class="mb-6">
        <p class="text-sm tracking-wide text-text-secondary">Producto</p>
        <h2 class="mt-2 font-serif text-2xl tracking-wide text-text-primary">
          Información principal
        </h2>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Nombre</label>
          <input v-model="form.name" type="text" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
        </div>

        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Precio</label>
          <input v-model="form.price" type="number" step="0.01" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
        </div>

        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Tipo</label>
          <select v-model="form.type" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary">
            <option value="permanent">Permanent</option>
            <option value="limited">Limited</option>
            <option value="preorder">Preorder</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Descripción corta</label>
          <textarea v-model="form.descriptionShort" rows="3" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Descripción extensa</label>
          <textarea v-model="form.descriptionLong" rows="5" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"></textarea>
        </div>

        <div v-if="form.type === 'preorder'">
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Fecha estimada de envío</label>
          <input v-model="form.preorderShippingDate" type="date" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
        </div>
      </div>
    </section>

    <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
      <div class="mb-6 flex items-end justify-between gap-4">
        <div>
          <p class="text-sm tracking-wide text-text-secondary">Variantes</p>
          <h2 class="mt-2 font-serif text-2xl tracking-wide text-text-primary">
            Gestión de tamaños
          </h2>
        </div>

        <button
          type="button"
          class="rounded-[16px] border border-text-primary px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
          @click="addVariant"
        >
          Agregar variante
        </button>
      </div>

      <div class="space-y-4">
        <div
          v-for="(variant, index) in form.variants"
          :key="variant.id"
          class="grid gap-4 rounded-[16px] border border-bg-secondary p-4 md:grid-cols-[1fr_1fr_auto]"
        >
          <div>
            <label class="mb-2 block text-sm tracking-wide text-text-secondary">Tamaño</label>
            <input v-model="variant.size" type="text" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
          </div>

          <div>
            <label class="mb-2 block text-sm tracking-wide text-text-secondary">Stock</label>
            <input v-model="variant.stock" type="number" min="0" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
          </div>

          <div class="flex items-end">
            <button
              type="button"
              class="rounded-[16px] border border-bg-secondary px-4 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
              @click="removeVariant(index)"
            >
              Quitar
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
      <div class="mb-6">
        <p class="text-sm tracking-wide text-text-secondary">Imágenes</p>
        <h2 class="mt-2 font-serif text-2xl tracking-wide text-text-primary">
          Subida múltiple
        </h2>
      </div>

      <label class="flex min-h-32 cursor-pointer items-center justify-center rounded-[16px] border border-dashed border-bg-secondary px-6 py-10 text-center text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:border-text-secondary">
        <input type="file" multiple class="hidden" @change="handleImagesUpload" />
        Seleccionar imágenes
      </label>

      <div v-if="imagePreviews.length > 0" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="preview in imagePreviews"
          :key="preview.id"
          class="overflow-hidden rounded-[16px] border border-bg-secondary"
        >
          <img :src="preview.url" alt="Preview" class="h-32 w-full object-cover" />
        </div>
      </div>
    </section>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <p v-if="successMessage" class="text-sm tracking-wide text-text-secondary">
        {{ successMessage }}
      </p>
      <div class="flex gap-3 sm:ml-auto">
        <button
          type="button"
          class="rounded-[16px] border border-bg-secondary px-5 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
          @click="$emit('cancel')"
        >
          Cancelar
        </button>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-[16px] bg-text-primary px-5 py-3 text-sm tracking-wide text-bg-primary transition duration-200 ease-out hover:opacity-90"
          :disabled="saving"
        >
          <span v-if="saving">Guardando...</span>
          <span v-else>Guardar producto</span>
        </button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'

type ProductVariantForm = {
  id: string
  size: string
  stock: number
}

type ProductImagePreview = {
  id: string
  url: string
}

type ProductFormInput = {
  name?: string
  price?: number
  type?: 'permanent' | 'limited' | 'preorder'
  descriptionShort?: string
  descriptionLong?: string
  preorderShippingDate?: string | null
  variants?: Array<{
    size?: string
    stock?: number
  }>
}

const props = defineProps<{
  product?: ProductFormInput | null
}>()

const emit = defineEmits<{
  save: [payload: ProductFormInput & { variants: ProductVariantForm[]; images: File[] }]
  cancel: []
}>()

const saving = ref(false)
const successMessage = ref('')
const uploadedImages = ref<File[]>([])
const imagePreviews = ref<ProductImagePreview[]>([])

const createVariant = (size = '', stock = 0): ProductVariantForm => ({
  id: crypto.randomUUID(),
  size,
  stock,
})

const form = reactive({
  name: '',
  price: 0,
  type: 'permanent' as 'permanent' | 'limited' | 'preorder',
  descriptionShort: '',
  descriptionLong: '',
  preorderShippingDate: '',
  variants: [createVariant()],
})

watch(
  () => props.product,
  (product) => {
    form.name = product?.name ?? ''
    form.price = product?.price ?? 0
    form.type = product?.type ?? 'permanent'
    form.descriptionShort = product?.descriptionShort ?? ''
    form.descriptionLong = product?.descriptionLong ?? ''
    form.preorderShippingDate = product?.preorderShippingDate ?? ''
    form.variants = product?.variants?.length
      ? product.variants.map((variant) => createVariant(variant.size ?? '', variant.stock ?? 0))
      : [createVariant()]
  },
  { immediate: true },
)

const addVariant = () => {
  form.variants.push(createVariant())
}

const removeVariant = (index: number) => {
  if (form.variants.length === 1) {
    form.variants[0] = createVariant()
    return
  }

  form.variants.splice(index, 1)
}

const handleImagesUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])

  uploadedImages.value = files
  imagePreviews.value = files.map((file) => ({
    id: crypto.randomUUID(),
    url: URL.createObjectURL(file),
  }))
}

const normalizedVariants = computed(() =>
  form.variants.filter((variant) => variant.size.trim() !== ''),
)

const submitForm = async () => {
  successMessage.value = ''
  saving.value = true

  try {
    await new Promise((resolve) => window.setTimeout(resolve, 700))

    emit('save', {
      name: form.name,
      price: form.price,
      type: form.type,
      descriptionShort: form.descriptionShort,
      descriptionLong: form.descriptionLong,
      preorderShippingDate: form.type === 'preorder' ? form.preorderShippingDate : null,
      variants: normalizedVariants.value,
      images: uploadedImages.value,
    })

    successMessage.value = 'Guardado correctamente.'
  } finally {
    saving.value = false
  }
}
</script>
