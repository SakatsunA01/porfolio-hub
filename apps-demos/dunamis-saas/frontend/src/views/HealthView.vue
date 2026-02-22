<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const loading = ref(true)
const error = ref('')
const payload = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/health')
    payload.value = data
  } catch (err) {
    error.value = err?.response?.data?.message || err.message || 'No se pudo conectar al backend.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section>
    <h2>Estado del Backend</h2>

    <p v-if="loading">Consultando API...</p>
    <p v-else-if="error" class="error">{{ error }}</p>

    <pre v-else>{{ payload }}</pre>
  </section>
</template>