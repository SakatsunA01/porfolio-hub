<template>
  <section class="min-h-screen bg-bg-primary py-section">
    <div class="mx-auto max-w-3xl px-6">
      <Transition
        appear
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
      >
        <div class="text-center">
          <p class="text-sm tracking-wide text-text-secondary">Orden confirmada</p>

          <h1 class="mt-6 font-serif text-5xl tracking-wide text-text-primary md:text-6xl">
            {{ orderNumber }}
          </h1>

          <p class="mx-auto mt-8 max-w-xl font-serif text-2xl leading-relaxed tracking-wide text-text-primary">
            Gracias por elegir calidad consciente.
          </p>

          <div class="mx-auto mt-12 max-w-2xl border-t border-bg-secondary pt-10 text-left">
            <p class="text-sm tracking-wide text-text-secondary">Información de envío</p>
            <div class="mt-6 space-y-3 text-text-secondary">
              <p>{{ shippingName }}</p>
              <p>{{ shippingAddress }}</p>
              <p>{{ shippingCity }}{{ shippingPostalCode ? `, ${shippingPostalCode}` : '' }}</p>
              <p v-if="shippingNote">{{ shippingNote }}</p>
            </div>
          </div>

          <div class="mt-14 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <router-link
              to="/"
              class="inline-flex items-center justify-center border border-text-primary px-7 py-3 text-sm uppercase tracking-widest text-text-primary transition duration-200 ease-out hover:opacity-90"
            >
              Volver al inicio
            </router-link>
            <router-link
              to="/register"
              class="inline-flex items-center justify-center border border-bg-secondary px-7 py-3 text-sm uppercase tracking-widest text-text-secondary transition duration-200 ease-out hover:border-text-secondary hover:text-text-primary"
            >
              Crear cuenta
            </router-link>
          </div>
        </div>
      </Transition>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const orderNumber = computed(() => String(route.query.order_number ?? 'ORD-0001'))
const shippingName = computed(() => String(route.query.name ?? 'Nombre del destinatario'))
const shippingAddress = computed(() => String(route.query.address ?? 'Dirección de envío'))
const shippingCity = computed(() => String(route.query.city ?? 'Ciudad'))
const shippingPostalCode = computed(() => String(route.query.postalCode ?? ''))
const shippingNote = computed(() => String(route.query.note ?? ''))
</script>
