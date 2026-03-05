<template>
  <section class="flex min-h-screen items-center justify-center bg-bg-secondary px-6 py-section-sm">
    <div class="w-full max-w-lg rounded-[24px] border border-bg-secondary bg-bg-primary p-8 md:p-10">
      <div class="mb-10 text-center">
        <p class="text-sm tracking-wide text-text-secondary">Acceso</p>
        <h1 class="mt-3 font-serif text-4xl tracking-wide text-text-primary">
          Ingresar
        </h1>
      </div>

      <form class="space-y-6" @submit.prevent="handleLogin">
        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-xl border bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out"
            :class="fieldClass(errors.email)"
            @input="validateField('email')"
          />
          <p v-if="errors.email" class="mt-2 text-sm text-state-error">{{ errors.email }}</p>
        </div>

        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-xl border bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out"
            :class="fieldClass(errors.password)"
            @input="validateField('password')"
          />
          <p v-if="errors.password" class="mt-2 text-sm text-state-error">{{ errors.password }}</p>
        </div>

        <p v-if="submitError" class="text-sm text-state-error">{{ submitError }}</p>

        <button
          type="submit"
          class="inline-flex w-full items-center justify-center rounded-xl bg-text-primary px-6 py-4 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90"
        >
          Ingresar
        </button>
      </form>

      <div class="mt-6 rounded-[20px] border border-bg-secondary bg-bg-primary/70 p-5">
        <p class="text-xs uppercase tracking-[0.2em] text-text-secondary">Login demo</p>
        <p class="mt-2 text-sm text-text-secondary">Acceso rapido para testear la tienda y el panel admin.</p>
        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
          <div class="text-sm text-text-secondary">
            <p><span class="text-text-primary">Email:</span> admin@admin.com</p>
            <p><span class="text-text-primary">Password:</span> password</p>
          </div>
          <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl border border-text-primary px-5 py-3 text-sm uppercase tracking-widest text-text-primary transition duration-200 ease-out hover:opacity-90 md:w-auto"
            @click="loginDemo"
          >
            Entrar demo
          </button>
        </div>
      </div>

      <p class="mt-8 text-center text-sm text-text-secondary">
        No tenes cuenta?
        <router-link to="/register" class="text-text-primary transition duration-200 ease-out hover:opacity-80">
          Crear cuenta
        </router-link>
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { getCsrfCookie } from '../services/api'

type LoginField = 'email' | 'password'

const authStore = useAuthStore()
const router = useRouter()

const form = reactive({
  email: '',
  password: '',
})

const errors = reactive<Record<LoginField, string>>({
  email: '',
  password: '',
})

const submitError = ref('')

const fieldClass = (error: string) =>
  error ? 'border-state-error' : 'border-bg-secondary focus:border-text-secondary'

const validateField = (field: LoginField) => {
  const value = form[field].trim()

  if (field === 'email') {
    errors.email = /\S+@\S+\.\S+/.test(value) ? '' : 'Ingresa un email valido.'
    return
  }

  errors.password = value.length >= 6 ? '' : 'Ingresa una contrasena valida.'
}

const handleLogin = async () => {
  validateField('email')
  validateField('password')
  submitError.value = ''

  if (errors.email || errors.password) {
    return
  }

  try {
    await getCsrfCookie()
    const response = await authStore.login({
      email: form.email,
      password: form.password,
    })

    router.push(response.redirect || '/account')
  } catch (error: any) {
    submitError.value = error.response?.data?.message || 'No pudimos iniciar sesion.'
  }
}

const loginDemo = async () => {
  form.email = 'admin@admin.com'
  form.password = 'password'
  await handleLogin()
}
</script>
