<template>
  <section class="flex min-h-screen items-center justify-center bg-bg-secondary px-6 py-section-sm">
    <div class="w-full max-w-lg rounded-[24px] border border-bg-secondary bg-bg-primary p-8 md:p-10">
      <div class="mb-10 text-center">
        <p class="text-sm tracking-wide text-text-secondary">Registro</p>
        <h1 class="mt-3 font-serif text-4xl tracking-wide text-text-primary">
          Crear cuenta
        </h1>
      </div>

      <form class="space-y-6" @submit.prevent="handleRegister">
        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Nombre</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-xl border bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out"
            :class="fieldClass(errors.name)"
            @input="validateField('name')"
          />
          <p v-if="errors.name" class="mt-2 text-sm text-state-error">{{ errors.name }}</p>
        </div>

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

        <div>
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">Confirmar password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="w-full rounded-xl border bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out"
            :class="fieldClass(errors.password_confirmation)"
            @input="validateField('password_confirmation')"
          />
          <p v-if="errors.password_confirmation" class="mt-2 text-sm text-state-error">
            {{ errors.password_confirmation }}
          </p>
        </div>

        <p v-if="submitError" class="text-sm text-state-error">{{ submitError }}</p>

        <button
          type="submit"
          class="inline-flex w-full items-center justify-center rounded-xl bg-text-primary px-6 py-4 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90"
        >
          Crear cuenta
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-text-secondary">
        ¿Ya tenés cuenta?
        <router-link to="/login" class="text-text-primary transition duration-200 ease-out hover:opacity-80">
          Ingresar
        </router-link>
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getCsrfCookie, register as apiRegister } from '../services/api'

type RegisterField = 'name' | 'email' | 'password' | 'password_confirmation'

const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive<Record<RegisterField, string>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submitError = ref('')

const fieldClass = (error: string) =>
  error ? 'border-state-error' : 'border-bg-secondary focus:border-text-secondary'

const validateField = (field: RegisterField) => {
  const value = form[field].trim()

  switch (field) {
    case 'name':
      errors.name = value.length >= 3 ? '' : 'Ingresá un nombre válido.'
      break
    case 'email':
      errors.email = /\S+@\S+\.\S+/.test(value) ? '' : 'Ingresá un email válido.'
      break
    case 'password':
      errors.password = value.length >= 6 ? '' : 'La contraseña debe tener al menos 6 caracteres.'
      if (form.password_confirmation) {
        validateField('password_confirmation')
      }
      break
    case 'password_confirmation':
      errors.password_confirmation = value === form.password ? '' : 'Las contraseñas no coinciden.'
      break
  }
}

const handleRegister = async () => {
  ;(['name', 'email', 'password', 'password_confirmation'] as RegisterField[]).forEach(validateField)
  submitError.value = ''

  if (Object.values(errors).some(Boolean)) {
    return
  }

  try {
    await getCsrfCookie()
    await apiRegister({ ...form })
    router.push({ name: 'login' })
  } catch (error: any) {
    const validationErrors = error.response?.data?.errors
    submitError.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : error.response?.data?.message || 'No pudimos crear la cuenta.'
  }
}
</script>
