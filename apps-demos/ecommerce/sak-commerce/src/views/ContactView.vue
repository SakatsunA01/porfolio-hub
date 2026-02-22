<template>
  <div class="bg-axis-primary py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-3xl font-bold tracking-tight text-axis-secondary sm:text-4xl font-display">Contáctanos</h2>
        <p class="mt-6 text-lg leading-8 text-axis-tertiary">
          Estamos aquí para ayudarte. Nuestro equipo de expertos está listo para resolver tus dudas y asegurar que
          tengas la mejor experiencia con Axis Tech.
        </p>
      </div>

      <div class="mx-auto mt-16 grid max-w-lg grid-cols-1 gap-8 sm:grid-cols-2 lg:max-w-none lg:grid-cols-3">
        <div v-for="method in contactMethods" :key="method.name"
          class="rounded-2xl border border-neutral-200/60 bg-white p-8 shadow-sm transition-shadow hover:shadow-lg">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-axis-accent text-white">
            <component :is="method.icon" class="h-6 w-6" aria-hidden="true" />
          </div>
          <h3 class="mt-6 text-xl font-bold tracking-tight text-axis-secondary font-display">{{ method.name }}</h3>
          <p class="mt-4 text-base leading-7 text-axis-tertiary">{{ method.description }}</p>
          <p class="mt-6">
            <a :href="method.href" class="text-base font-semibold leading-6 text-axis-accent">{{ method.linkText }}
              &rarr;</a>
          </p>
        </div>
      </div>

      <div class="mx-auto mt-24 max-w-7xl text-center">
        <h3 class="text-3xl font-bold tracking-tight text-axis-secondary sm:text-4xl font-display">Conoce a Nuestro
          Equipo</h3>
        <p class="mt-4 text-lg leading-8 text-axis-tertiary">
          Personas reales, dedicadas a ofrecerte el mejor soporte.
        </p>
        <div class="mt-12 grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="person in team" :key="person.name" class="flex flex-col items-center text-center">
            <img class="h-48 w-48 rounded-full object-cover shadow-md" :src="person.imageUrl"
              :alt="`Retrato de ${person.name}`" />
            <div class="mt-6">
              <p class="text-lg font-semibold leading-7 text-axis-secondary">{{ person.name }}</p>
              <p class="text-sm font-semibold text-axis-accent">{{ person.role }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EnvelopeIcon, PhoneIcon, ChatBubbleBottomCenterTextIcon } from '@heroicons/vue/24/outline'

// Datos de los métodos de contacto
const contactMethods = [
  {
    name: 'Soporte por Email',
    description: 'Para consultas generales o soporte técnico, envíanos un correo. Te responderemos en menos de 24 horas.',
    href: 'mailto:soporte@axistech.com',
    linkText: 'soporte@axistech.com',
    icon: EnvelopeIcon,
  },
  {
    name: 'Atención Telefónica',
    description: 'Si prefieres hablar directamente, nuestro equipo está disponible de Lunes a Viernes de 9 a 18 hs.',
    href: 'tel:+34912345678',
    linkText: '+34 912 345 678',
    icon: PhoneIcon,
  },
  {
    name: 'Chat en Vivo',
    description: 'Para asistencia inmediata, utiliza nuestro chat. La forma más rápida de obtener una respuesta.',
    href: '#', // Aquí iría la función para abrir el chat
    linkText: 'Iniciar Chat',
    icon: ChatBubbleBottomCenterTextIcon,
  },
]

// Datos del equipo de soporte con las rutas a las imágenes locales
const team = [
  {
    name: 'Ana García',
    role: 'Especialista en Atención al Cliente',
    imageUrl: '@/assets/team/ana-garcia.png', // Reemplaza con la ruta real a tu imagen
  },
  {
    name: 'Pedro Martínez',
    role: 'Soporte Técnico Senior',
    imageUrl: '@/assets/team/pedro-martinez.png', // Reemplaza con la ruta real a tu imagen
  },
  {
    name: 'Laura Fernández',
    role: 'Coordinadora de Experiencia del Cliente',
    imageUrl: '@/assets/team/laura-fernandez.png', // Reemplaza con la ruta real a tu imagen
  },
]
</script>