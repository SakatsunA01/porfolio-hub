<script setup>
const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
const appUrl = apiUrl.replace(/\/api\/?$/, '')
const loginUrl = `${appUrl}/login`
const healthUrl = `${appUrl}/api/health`
</script>

<template>
  <section class="min-h-screen bg-[#f5f1ea] px-6 py-16 text-[#2f2a24]">
    <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1.2fr_0.8fr]">
      <article class="rounded-[24px] border border-[#ded5c8] bg-white p-8">
        <p class="text-xs uppercase tracking-[0.24em] text-[#7d7166]">Dunamis Demo</p>
        <h1 class="mt-4 text-4xl font-semibold tracking-tight">Acceso de prueba</h1>
        <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#5f564d]">
          Este frontend hoy funciona como puerta de acceso liviana a la demo. El login operativo esta resuelto en Laravel y usa un usuario semilla listo para test.
        </p>

        <div class="mt-8 rounded-[20px] border border-[#e7dfd3] bg-[#faf7f2] p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-[#7d7166]">Credenciales demo</p>
          <div class="mt-4 space-y-2 text-sm text-[#5f564d]">
            <p><span class="font-semibold text-[#2f2a24]">Email:</span> demo@dunamis.local</p>
            <p><span class="font-semibold text-[#2f2a24]">Password:</span> demo1234</p>
          </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
          <a
            :href="loginUrl"
            class="inline-flex items-center justify-center rounded-[16px] bg-[#2f2a24] px-6 py-3 text-sm uppercase tracking-[0.18em] text-[#f5f1ea] transition duration-200 hover:opacity-90"
          >
            Abrir login demo
          </a>
          <a
            :href="healthUrl"
            class="inline-flex items-center justify-center rounded-[16px] border border-[#2f2a24] px-6 py-3 text-sm uppercase tracking-[0.18em] text-[#2f2a24] transition duration-200 hover:opacity-90"
          >
            Ver health
          </a>
        </div>
      </article>

      <aside class="rounded-[24px] border border-[#ded5c8] bg-white p-8">
        <p class="text-xs uppercase tracking-[0.24em] text-[#7d7166]">Estado</p>
        <ul class="mt-6 space-y-4 text-sm leading-relaxed text-[#5f564d]">
          <li>Backend Laravel separado y accesible por subdominio propio.</li>
          <li>Seeder demo disponible para usuario, cliente, producto y venta.</li>
          <li>Esta entrada sirve como acceso rapido mientras el panel Vue crece como SPA propia.</li>
        </ul>
      </aside>
    </div>
  </section>
</template>
