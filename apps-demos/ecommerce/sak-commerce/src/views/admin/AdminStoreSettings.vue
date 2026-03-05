<template>
  <AdminLayout>
    <div class="space-y-8">
      <div>
        <p class="text-sm tracking-wide text-text-secondary">Configuracion</p>
        <h1 class="mt-2 font-serif text-4xl tracking-wide text-text-primary">
          Identidad de la tienda
        </h1>
      </div>

      <form class="space-y-6" @submit.prevent="saveSettings">
        <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
          <h2 class="font-serif text-2xl tracking-wide text-text-primary">Branding</h2>

          <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Nombre del local</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
              />
            </div>

            <div class="md:col-span-2">
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Logo</label>
              <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="h-20 w-20 overflow-hidden rounded-[16px] border border-bg-secondary bg-bg-secondary">
                  <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-cover" />
                </div>
                <input
                  type="file"
                  accept="image/*"
                  class="text-sm text-text-secondary"
                  @change="onLogoChange"
                />
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Paletas recomendadas</label>
              <div class="grid gap-3 md:grid-cols-2">
                <button
                  v-for="palette in recommendedPalettes"
                  :key="palette.title"
                  type="button"
                  class="space-y-3 rounded-[16px] border border-bg-secondary bg-bg-primary p-4 text-left transition duration-200 ease-out hover:border-text-secondary"
                  @click="applyRecommendedPalette(palette.colors)"
                >
                  <p class="text-sm tracking-wide text-text-primary">{{ palette.title }}</p>
                  <div class="grid grid-cols-5 gap-2">
                    <span
                      v-for="(color, index) in palette.colors"
                      :key="`${palette.title}-${index}`"
                      class="h-8 w-full rounded-[10px] border border-bg-secondary"
                      :style="{ backgroundColor: color }"
                    />
                  </div>
                  <p class="text-xs tracking-wide text-text-secondary">Usar paleta</p>
                </button>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Paleta custom (5)</label>
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div v-for="(color, index) in form.brand_palette" :key="index" class="space-y-2">
                  <p class="text-xs tracking-wide text-text-secondary">{{ paletteSlots[index].label }}</p>
                  <input
                    v-model="form.brand_palette[index]"
                    type="color"
                    class="h-12 w-full cursor-pointer rounded-[12px] border border-bg-secondary bg-bg-primary"
                  />
                  <input
                    v-model="form.brand_palette[index]"
                    type="text"
                    class="w-full rounded-[12px] border border-bg-secondary bg-bg-primary px-2 py-1 text-xs text-text-secondary outline-none"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
          <h2 class="font-serif text-2xl tracking-wide text-text-primary">Vista previa de impacto</h2>
          <p class="mt-2 text-sm text-text-secondary">
            Esta preview muestra como impactan los colores en fondo, textos, cards y botones antes de guardar.
          </p>

          <div class="mt-5 grid gap-4 md:grid-cols-2">
            <article
              class="border p-4"
              :style="{ backgroundColor: previewPalette[0], borderColor: previewPalette[1], color: previewPalette[2] }"
            >
              <h3 class="font-serif text-2xl tracking-wide">Titular de seccion</h3>
              <p class="mt-2 text-sm" :style="{ color: previewPalette[3] }">
                Texto secundario para descripcion y cuerpo editorial.
              </p>
            </article>

            <article
              class="border p-4"
              :style="{ backgroundColor: previewPalette[1], borderColor: previewPalette[1] }"
            >
              <p class="text-sm tracking-wide" :style="{ color: previewPalette[3] }">Card / Surface</p>
              <p class="mt-2 font-serif text-2xl tracking-wide" :style="{ color: previewPalette[2] }">Producto destacado</p>
              <div class="mt-4 flex gap-3">
                <button class="px-4 py-2 text-sm tracking-wide" :style="{ backgroundColor: previewPalette[2], color: previewPalette[0] }">
                  Boton primario
                </button>
                <button class="px-4 py-2 text-sm tracking-wide border" :style="{ borderColor: previewPalette[2], color: previewPalette[2] }">
                  Boton secundario
                </button>
              </div>
              <p class="mt-4 text-sm tracking-wide" :style="{ color: previewPalette[4] }">Color de acento (links/badges)</p>
            </article>
          </div>
        </section>

        <section class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6">
          <h2 class="font-serif text-2xl tracking-wide text-text-primary">Contenido editorial</h2>
          <div class="mt-5 grid gap-5">
            <div>
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Texto de Manifiesto</label>
              <textarea v-model="form.manifesto_text" rows="4" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
            </div>

            <div>
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Nuestra Filosofia</label>
              <textarea v-model="form.philosophy_text" rows="3" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
            </div>

            <div>
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Contactanos</label>
              <textarea v-model="form.contact_text" rows="3" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
            </div>

            <div>
              <label class="mb-2 block text-sm tracking-wide text-text-secondary">Conoce a Nuestro Equipo</label>
              <textarea v-model="form.team_text" rows="3" class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
            </div>
          </div>
        </section>

        <div class="flex justify-end">
          <button
            type="submit"
            class="rounded-[16px] bg-text-primary px-6 py-3 text-sm tracking-wide text-bg-primary transition duration-200 ease-out hover:opacity-90"
            :disabled="saving"
          >
            {{ saving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '../../components/admin/AdminLayout.vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'

type StoreSettings = {
  name: string
  logo_url: string | null
  brand_palette: string[]
  manifesto_text: string | null
  philosophy_text: string | null
  contact_text: string | null
  team_text: string | null
}

const { success, error } = useToast()
const saving = ref(false)
const selectedLogo = ref<File | null>(null)
const logoPreview = ref<string | null>(null)

const form = ref<StoreSettings>({
  name: '',
  logo_url: null,
  brand_palette: ['#F7F5F0', '#ECE7DF', '#22221F', '#5A5A55', '#4F5D47'],
  manifesto_text: '',
  philosophy_text: '',
  contact_text: '',
  team_text: '',
})

const paletteSlots = [
  { label: 'Fondo principal' },
  { label: 'Fondo secundario' },
  { label: 'Texto principal' },
  { label: 'Texto secundario' },
  { label: 'Acento' },
]

const recommendedPalettes = [
  {
    title: 'Quiet Luxury Warm',
    colors: ['#F7F2EA', '#E8DDD1', '#1F1F1F', '#4C3F36', '#1C3F5A'],
  },
  {
    title: 'Porcelain & Sage',
    colors: ['#F5F7F6', '#E2E9E4', '#1E2421', '#3C4A43', '#B66A3C'],
  },
  {
    title: 'Minimal Tech Luxury',
    colors: ['#F6F7FB', '#E6E9F2', '#111318', '#3A4350', '#2F5BFF'],
  },
  {
    title: 'Heritage Premium',
    colors: ['#FBF6EE', '#EFE1D4', '#1C1A18', '#5A3E35', '#7A1E2C'],
  },
  {
    title: 'Biofilico Contemporaneo',
    colors: ['#F3EFE7', '#E7D9CB', '#20211F', '#4A463F', '#1F5B3A'],
  },
]

const previewPalette = computed(() => {
  const fallback = ['#F7F5F0', '#ECE7DF', '#22221F', '#5A5A55', '#4F5D47']
  if (!Array.isArray(form.value.brand_palette) || form.value.brand_palette.length < 5) {
    return fallback
  }
  return form.value.brand_palette.map((color, index) => color || fallback[index])
})

const loadSettings = async () => {
  const response = await api.get('/admin/store-settings')
  const data = response.data.data as StoreSettings
  form.value = {
    name: data.name || '',
    logo_url: data.logo_url,
    brand_palette: data.brand_palette?.length === 5 ? data.brand_palette : ['#F7F5F0', '#ECE7DF', '#22221F', '#5A5A55', '#4F5D47'],
    manifesto_text: data.manifesto_text || '',
    philosophy_text: data.philosophy_text || '',
    contact_text: data.contact_text || '',
    team_text: data.team_text || '',
  }
  logoPreview.value = data.logo_url
}

const onLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  selectedLogo.value = file || null

  if (file) {
    logoPreview.value = URL.createObjectURL(file)
  }
}

const applyRecommendedPalette = (colors: string[]) => {
  form.value.brand_palette = [...colors]
}

const saveSettings = async () => {
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('name', form.value.name)
    form.value.brand_palette.forEach((color, index) => {
      const normalized = color.startsWith('#') ? color.toUpperCase() : `#${color.toUpperCase()}`
      payload.append(`brand_palette[${index}]`, normalized)
    })
    payload.append('manifesto_text', form.value.manifesto_text || '')
    payload.append('philosophy_text', form.value.philosophy_text || '')
    payload.append('contact_text', form.value.contact_text || '')
    payload.append('team_text', form.value.team_text || '')

    if (selectedLogo.value) {
      payload.append('logo', selectedLogo.value)
    }

    await api.post('/admin/store-settings', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    success('Configuracion guardada')
    await loadSettings()
  } catch (saveError) {
    console.error(saveError)
    error('No se pudo guardar la configuracion')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>
