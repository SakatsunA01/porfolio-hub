<template>
  <section class="bg-axis-primary py-12">
    <div class="axis-container">
      <div class="mb-6">
        <h2 class="text-2xl font-display font-bold text-axis-secondary">Explora por categoria</h2>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <router-link
          to="/category/Audio"
          class="group rounded-2xl border border-slate-200 bg-white/90 p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 transition group-hover:bg-emerald-50">
            <svg class="h-7 w-7 text-axis-secondary transition group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-semibold text-axis-secondary">Audio</h3>
        </router-link>

        <router-link
          to="/category/Carga"
          class="group rounded-2xl border border-slate-200 bg-white/90 p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 transition group-hover:bg-emerald-50">
            <svg class="h-7 w-7 text-axis-secondary transition group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-semibold text-axis-secondary">Carga</h3>
        </router-link>

        <router-link
          to="/category/Accesorios"
          class="group rounded-2xl border border-slate-200 bg-white/90 p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 transition group-hover:bg-emerald-50">
            <svg class="h-7 w-7 text-axis-secondary transition group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-semibold text-axis-secondary">Accesorios</h3>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
</script>
