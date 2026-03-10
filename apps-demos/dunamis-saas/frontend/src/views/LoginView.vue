<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')

const submit = async () => {
  error.value = ''
  try {
    await auth.login({
      email: email.value,
      password: password.value,
    })
    await router.push('/dashboard')
  } catch (err) {
    error.value = err?.response?.data?.errors?.email?.[0]
      || err?.response?.data?.message
      || 'No se pudo iniciar sesion.'
  }
}

const loginDemo = async () => {
  email.value = 'demo@dunamis.local'
  password.value = 'demo1234'
  await submit()
}
</script>

<template>
  <section class="mx-auto mt-8 max-w-md surface p-6">
    <p class="m-0 text-xs uppercase tracking-widest muted">Dunamis SaaS</p>
    <h1 class="m-0 mt-2 text-2xl font-semibold">Iniciar sesion</h1>
    <p class="m-0 mt-1 text-sm muted">SPA front-first con auth Sanctum cookie-based.</p>

    <form class="mt-4 grid gap-2" @submit.prevent="submit">
      <label class="text-xs uppercase tracking-widest muted" for="email">Email</label>
      <input id="email" v-model="email" type="email" class="field" autocomplete="username" required />

      <label class="text-xs uppercase tracking-widest muted" for="password">Password</label>
      <input id="password" v-model="password" type="password" class="field" autocomplete="current-password" required />

      <p v-if="error" class="m-0 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

      <button type="submit" class="btn btn-primary w-full" :disabled="auth.state.loading">
        {{ auth.state.loading ? 'Ingresando...' : 'Ingresar' }}
      </button>
      <button type="button" class="btn btn-secondary w-full" :disabled="auth.state.loading" @click="loginDemo">
        Login demo
      </button>
    </form>
  </section>
</template>
