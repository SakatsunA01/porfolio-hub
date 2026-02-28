<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ShieldAlert } from 'lucide-vue-next'
import AppButton from '../components/common/AppButton.vue'

const router = useRouter()

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push('/login')
}
</script>

<template>
  <section class="min-h-screen bg-slate-100 px-4 py-8 sm:py-12">
    <div class="mx-auto max-w-xl rounded-[24px] bg-white p-6 text-center shadow-[0_4px_20px_rgba(0,0,0,0.05)] sm:p-8">
      <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
        <ShieldAlert class="h-6 w-6" />
      </span>
      <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Error 403</p>
      <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Acceso denegado</h1>
      <p class="mt-2 text-sm text-slate-600">
        Ups, no tenes permisos para esta seccion. Contacta con tu administrador.
      </p>
      <div class="mt-6 flex justify-center">
        <AppButton variant="primary" @click="goBack">Volver</AppButton>
      </div>
    </div>
  </section>
</template>
