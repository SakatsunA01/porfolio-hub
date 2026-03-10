<script setup>
import { onMounted, reactive, ref } from 'vue'
import { profileApi } from '../services/api'
import { useUiStore } from '../stores/ui'
import PageHeader from '../components/PageHeader.vue'

const ui = useUiStore()
const loading = ref(true)
const error = ref('')
const profileForm = reactive({ name: '', email: '' })
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await profileApi.show()
    profileForm.name = data.data.name
    profileForm.email = data.data.email
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo cargar el perfil.'
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  error.value = ''
  try {
    await profileApi.update({ ...profileForm })
    ui.toast('Perfil actualizado.', 'success')
  } catch (err) {
    error.value = err?.response?.data?.message || 'No se pudo actualizar el perfil.'
  }
}

const savePassword = async () => {
  error.value = ''
  try {
    await profileApi.updatePassword({ ...passwordForm })
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    ui.toast('Contrasena actualizada.', 'success')
  } catch (err) {
    error.value = err?.response?.data?.errors?.current_password?.[0]
      || err?.response?.data?.message
      || 'No se pudo actualizar la contrasena.'
  }
}

onMounted(load)
</script>

<template>
  <section class="grid gap-4">
    <PageHeader title="Perfil" subtitle="Configuracion de usuario y seguridad." />

    <p v-if="error" class="surface px-3 py-2 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

    <div class="grid gap-3 lg:grid-cols-2">
      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Informacion basica</h2>
        <div v-if="loading" class="mt-3 grid gap-2">
          <div class="skeleton h-10 rounded"></div>
          <div class="skeleton h-10 rounded"></div>
        </div>
        <form v-else class="mt-3 grid gap-2" @submit.prevent="saveProfile">
          <input v-model="profileForm.name" class="field" placeholder="Nombre" required />
          <input v-model="profileForm.email" class="field" type="email" placeholder="Email" required />
          <button type="submit" class="btn btn-primary">Guardar perfil</button>
        </form>
      </article>

      <article class="surface p-4">
        <h2 class="m-0 text-base font-semibold">Seguridad</h2>
        <form class="mt-3 grid gap-2" @submit.prevent="savePassword">
          <input v-model="passwordForm.current_password" class="field" type="password" placeholder="Contrasena actual" required />
          <input v-model="passwordForm.password" class="field" type="password" placeholder="Nueva contrasena" required />
          <input v-model="passwordForm.password_confirmation" class="field" type="password" placeholder="Confirmar contrasena" required />
          <button type="submit" class="btn btn-secondary">Actualizar contrasena</button>
        </form>
      </article>
    </div>
  </section>
</template>
