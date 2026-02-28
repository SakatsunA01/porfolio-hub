<script setup>
import { computed } from 'vue'
import { ExternalLink, Github } from 'lucide-vue-next'
import sakCommerceCover from '../assets/images/Sak-Commerce.png'
import dunamisCover from '../assets/images/Dunamis SaaS.png'
import deliveryCover from '../assets/images/Delivery App.png'
import laBarcaCover from '../assets/images/labarca.png'

const props = defineProps({
  lang: { type: String, default: 'es' },
})

const emit = defineEmits(['open-demo'])
const modules = [
  {
    key: 'sak',
    slug: 'sak-commerce',
    name: 'Sak-Commerce',
    summary: 'Tienda online lista para vender con una experiencia de compra clara',
    description:
      'Pensado para marcas que quieren vender mejor: catalogo ordenado, productos bien presentados y recorrido de compra intuitivo.',
    status: 'MVP',
    state: 'functional',
    coverImage: sakCommerceCover,
    stack: ['VUE 3', 'TS', 'PINIA', 'TAILWIND'],
    impact: 'Ideal para aumentar confianza, mejorar la imagen de marca y facilitar conversiones.',
    technicalDetails: [
      'Frontend modular en Vue 3 + TypeScript con componentes reutilizables para escalar nuevas categorias sin rehacer pantallas.',
      'Gestion de estado centralizada con Pinia para carrito, filtros y flujo de producto consistente en toda la experiencia.',
      'UI en Tailwind con enfoque responsive, performance visual y jerarquia clara para reducir friccion en la navegacion.',
      'Estructura preparada para integrar checkout, pasarela de pago y sincronizacion con backend de inventario.',
    ],
    techSummary: 'Arquitectura frontend escalable con Vue 3 + TypeScript, estado centralizado con Pinia y UI en Tailwind.',
    demoUrl: 'http://localhost:5174/',
    githubUrl: '#',
    loadLogs: [
      '> Inyectando modulos Vue 3...',
      '> Cargando assets de Sak-Commerce...',
      '> Renderizacion completa.',
    ],
    visualClass: 'from-cyan-100 via-emerald-100/60 to-slate-100',
  },
  {
    key: 'dunamis',
    slug: 'dunamis-saas',
    name: 'Dunamis SaaS',
    summary: 'Sistema de gestion para ordenar la operacion diaria del negocio',
    description:
      'Centraliza ventas, clientes, productos y reportes en un unico panel para ahorrar tiempo y trabajar con mas control.',
    status: 'Optimized',
    state: 'functional',
    coverImage: dunamisCover,
    stack: ['LARAVEL 10', 'SANCTUM', 'MYSQL'],
    impact: 'Reduce errores operativos y mejora la visibilidad del negocio con datos trazables.',
    technicalDetails: [
      'Backend en Laravel 10 con API protegida por Sanctum para sesiones seguras y control de acceso por usuario.',
      'Modelo de datos orientado a trazabilidad: ventas, stock y movimientos quedan registrados para auditoria operativa.',
      'Optimizacion de consultas SQL y flujo de controladores para disminuir tiempos de respuesta en pantallas criticas.',
      'Arquitectura separada front/back lista para escalar modulos nuevos sin comprometer estabilidad del sistema.',
    ],
    techSummary: 'Backend Laravel 10 con autenticacion segura y optimizacion de consultas para mejor rendimiento.',
    demoUrl: 'http://127.0.0.1:8000/login',
    githubUrl: '#',
    loadLogs: [
      '> Inyectando modulos Vue 3...',
      '> Conectando con Backend Laravel...',
      '> Renderizacion completa.',
    ],
    visualClass: 'from-emerald-100 via-slate-100 to-cyan-50',
  },
  {
    key: 'delivery',
    slug: 'delivery-app',
    name: 'Delivery App',
    summary: 'Demo SaaS de delivery con gestion completa por roles',
    description:
      'Simula una plataforma real de delivery: cliente, cocina, repartidor y admin trabajan en flujos separados con seguimiento en tiempo real.',
    status: 'MVP',
    state: 'development',
    coverImage: deliveryCover,
    stack: ['FIREBASE', 'VUE', 'MOBILE UX'],
    impact: 'Demuestra como operar un negocio de delivery con mayor control, menos friccion operativa y mejor experiencia de compra.',
    technicalDetails: [
      'Arquitectura por roles y rutas separadas (cliente, cocina, repartidor y admin) para una operacion clara y escalable.',
      'Panel administrativo con gestion de productos, categorias, combos y bundles mediante modales y acciones operativas rapidas.',
      'Estados de pedido en tiempo real con trazabilidad de eventos y control de flujo desde toma hasta entrega.',
      'Base orientada a SaaS multi-negocio: modelo de tenant y permisos para evolucionar a despliegues por cliente.',
    ],
    techSummary: 'Arquitectura lista para evolucionar a SaaS multi-negocio, con estados en tiempo real y paneles por rol.',
    demoUrl: 'http://localhost:5176/',
    githubUrl: '#',
    loadLogs: [
      '> Estableciendo enlace con Firebase Node....',
      '> Sincronizando persistencia de datos...',
      '> Renderizacion completa.',
    ],
    visualClass: 'from-amber-100 via-orange-50 to-slate-100',
  },
  {
    key: 'labarca',
    slug: 'la-barca-music',
    name: 'La Barca Music',
    summary: 'Plataforma real para eventos y comunidad',
    description:
      'Sitio activo en produccion, enfocado en publicar contenido, gestionar actividades y sostener una experiencia estable.',
    status: 'Live',
    state: 'production',
    coverImage: laBarcaCover,
    stack: ['LARAVEL', 'LIVE OPS', 'AUTOMATION'],
    impact: 'Canal digital activo para comunidad y eventos, con operacion continua.',
    technicalDetails: [
      'Sitio en produccion con foco en continuidad operativa y publicacion de contenido para comunidad activa.',
      'Mantenimiento evolutivo orientado a estabilidad, tiempos de carga razonables y compatibilidad multi-dispositivo.',
      'Estructura preparada para administrar eventos, contenido dinamico y crecimiento sostenido de trafico.',
      'Monitoreo y ajustes continuos para sostener confiabilidad en escenarios de uso real.',
    ],
    techSummary: 'Plataforma en produccion con foco en estabilidad, mantenimiento continuo y soporte operativo.',
    demoUrl: 'https://www.labarcaministerio.com/',
    allowEmbed: false,
    githubUrl: '#',
    loadLogs: [
      '> Detectando nodo en produccion...',
      '> Modo externo habilitado por politicas de seguridad...',
      '> Listo para abrir demo oficial.',
    ],
    visualClass: 'from-emerald-200/90 via-teal-100/60 to-slate-100',
  },
]

const badgeClass = (state) => {
  if (state === 'production') return 'bg-emerald-100 text-emerald-700 ring-emerald-200'
  if (state === 'development') return 'bg-sky-100 text-sky-700 ring-sky-200'

  return 'bg-cyan-100 text-cyan-700 ring-cyan-200'
}

const moduleTranslationsEn = {
  sak: {
    summary: 'Online store ready to sell with a clear buying experience',
    description:
      'Built for brands that want better sales results: organized catalog, polished product presentation and intuitive customer journey.',
    impact: 'Great for building trust, strengthening brand perception and improving conversions.',
    technicalDetails: [
      'Modular Vue 3 + TypeScript frontend with reusable components to scale categories without rebuilding screens.',
      'Centralized state with Pinia for cart, filters and consistent product flows across the experience.',
      'Tailwind-based UI focused on responsive behavior, visual performance and clear hierarchy.',
      'Structure ready for checkout, payment gateway integration and inventory backend sync.',
    ],
    loadLogs: ['> Injecting Vue 3 modules...', '> Loading Sak-Commerce assets...', '> Rendering complete.'],
  },
  dunamis: {
    summary: 'Management system to organize daily business operations',
    description:
      'Centralizes sales, customers, products and reports in one place to save time and improve operational control.',
    impact: 'Reduces operational errors and improves business visibility with traceable data.',
    technicalDetails: [
      'Laravel 10 backend with Sanctum-protected API for secure sessions and user access control.',
      'Data model focused on traceability: sales, stock and inventory movements are fully auditable.',
      'SQL query tuning and controller flow optimization for faster response times on critical screens.',
      'Separated front/back architecture ready to scale new modules without compromising stability.',
    ],
    loadLogs: ['> Injecting Vue 3 modules...', '> Connecting to Laravel backend...', '> Rendering complete.'],
  },
  delivery: {
    summary: 'SaaS-ready delivery demo with full role-based operations',
    description:
      'Simulates a production-style delivery platform where customer, kitchen, courier and admin run on separated role-based flows.',
    impact: 'Shows how delivery operations can scale with clearer control, lower friction and better customer experience.',
    technicalDetails: [
      'Role-based architecture with separated routes (customer, kitchen, courier and admin) for clearer operations.',
      'Admin console with product, category, combo and bundle management through modal-driven workflows.',
      'Realtime order lifecycle visibility with event traceability from intake to final delivery.',
      'SaaS-oriented foundation with tenant and permission model ready for multi-business deployments.',
    ],
    loadLogs: ['> Establishing Firebase node link...', '> Syncing data persistence...', '> Rendering complete.'],
  },
  labarca: {
    summary: 'Live platform for events and community operations',
    description:
      'Production website focused on publishing content, coordinating activities and maintaining a stable user experience.',
    impact: 'Active digital channel for community and events with continuous operation.',
    technicalDetails: [
      'Production-ready site focused on continuity and content operations for an active community.',
      'Evolving maintenance strategy for stability, loading performance and multi-device compatibility.',
      'Prepared structure for event management, dynamic content and sustained traffic growth.',
      'Continuous monitoring and tuning to ensure reliability in real usage scenarios.',
    ],
    loadLogs: ['> Detecting production node...', '> External mode enabled by security policy...', '> Ready to open official demo.'],
  },
}

const statusLabel = (status) => {
  if (props.lang !== 'en') return status
  if (status === 'Optimized') return 'Optimized'
  return status
}

const orderedModules = computed(() => {
  if (props.lang !== 'en') return modules

  return modules.map((module) => {
    const translated = moduleTranslationsEn[module.key]
    if (!translated) return module

    return {
      ...module,
      summary: translated.summary,
      description: translated.description,
      impact: translated.impact,
      technicalDetails: translated.technicalDetails,
      loadLogs: translated.loadLogs || module.loadLogs,
    }
  })
})
</script>

<template>
  <section class="module-grid mt-8 grid grid-cols-1 gap-5 md:grid-cols-2">
    <article
      v-for="module in orderedModules"
      :key="module.key"
      class="group overflow-hidden rounded-xl border border-slate-200/50 bg-white/80 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
    >
      <div class="relative aspect-video overflow-hidden rounded-t-xl border-b border-slate-200/60">
        <img
          v-if="module.coverImage"
          :src="module.coverImage"
          :alt="`Preview ${module.name}`"
          class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03] group-hover:saturate-125"
          loading="lazy"
        />
        <div
          v-else
          class="project-visual h-full w-full bg-gradient-to-br transition duration-300 group-hover:scale-[1.03] group-hover:saturate-125"
          :class="module.visualClass"
        />
        <span
          class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-semibold tracking-[0.08em] ring-1"
          :class="badgeClass(module.state)"
        >
          {{ statusLabel(module.status) }}
        </span>
      </div>

      <div class="bg-white p-6">
        <h3 class="font-[Inter] text-lg font-bold text-slate-900">{{ module.name }}</h3>
        <p class="mt-1 text-sm font-medium text-slate-700">{{ module.summary }}</p>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ module.description }}</p>

        <p class="mt-4 text-sm leading-relaxed text-slate-800">{{ module.impact }}</p>

        <div class="mt-5 flex flex-wrap gap-2">
          <button
            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.35),0_8px_18px_rgba(5,150,105,0.28)] active:translate-y-[1px]"
            @click="emit('open-demo', module)"
          >
            <ExternalLink :size="14" />
            {{ props.lang === 'en' ? 'View Demo' : 'Ver Demo' }}
          </button>

          <a
            :href="module.githubUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-transparent px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            <Github :size="14" />
            {{ props.lang === 'en' ? 'Source' : 'GitHub' }}
          </a>
        </div>
      </div>
    </article>
  </section>
</template>

<style scoped>
.module-grid {
  position: relative;
  z-index: 2;
}
</style>
