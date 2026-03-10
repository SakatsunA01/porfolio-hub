<script setup>
import { onMounted, ref } from 'vue'
import { healthCheck } from '../services/api'

const loading = ref(true)
const error = ref('')
const payload = ref(null)

onMounted(async () => {
  try {
    const { data } = await healthCheck()
    payload.value = data
  } catch (err) {
    error.value = err?.response?.data?.message || err.message || 'No se pudo conectar al backend.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="mx-auto mt-8 max-w-3xl surface p-6">
    <p class="m-0 text-xs uppercase tracking-widest muted">Health</p>
    <h1 class="m-0 mt-2 text-2xl font-semibold">Estado del Backend</h1>

    <p v-if="loading" class="mt-3 muted">Consultando API...</p>
    <p v-else-if="error" class="mt-3 text-sm" style="color: rgb(var(--destructive));">{{ error }}</p>

    <pre
      v-else
      class="mt-3 overflow-auto rounded-xl border p-4 text-sm"
      style="border-color: rgb(var(--border)); background: rgba(var(--background), 0.6);"
    >{{ payload }}</pre>
  </section>
</template>
