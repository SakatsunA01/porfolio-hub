<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { animate, stagger } from 'motion'
import { Activity, Leaf, Waves } from 'lucide-vue-next'
import ProjectModule from './ProjectModule.vue'
import ProjectViewer from './ProjectViewer.vue'
import forestCityBokeh from '../assets/images/forest-city-bokeh2.jpg'

const rootRef = ref(null)
const pointer = ref({ x: 0, y: 0 })
const eased = ref({ x: 0, y: 0 })

const vistaActiva = ref('PERFIL')
const activityStream = ref([])
const enTransicion = ref(false)
const viewerAbierto = ref(false)
const proyectoActivo = ref(null)
const lang = ref('es')
const fallbackCoords = { latitude: -34.6037, longitude: -58.3816 }
const fallbackLocation = '34.6037deg S, 58.3816deg W (Buenos Aires Centro)'
const currentCoords = ref({ ...fallbackCoords })
const locationLabel = ref(fallbackLocation)
const envLabel = ref('Buenos Aires Monitor')
const humidityLevel = ref(82)
const oxygenLevel = ref(94)
const latencyMs = ref(12)
const weatherMode = ref('clear')

const i18n = {
  es: {
    language: 'Idioma',
    spanish: 'ES',
    english: 'EN',
    humidity: 'Humedad',
    oxygen: 'Oxigeno',
    systemLabel: 'SYS',
    latency: 'Latencia',
    optimization: 'Optimizacion',
    profileTitle: 'Sergio Quinteros | 28 años, Argentina',
    profileP1: 'Ayudo a empresas a ordenar sus procesos con herramientas digitales claras, rapidas y faciles de usar.',
    profileP2: 'Tengo 3 años de experiencia construyendo soluciones web. En Rusoft logre reducir en un 50% los tiempos de respuesta de sistemas criticos, mejorando la experiencia diaria de los equipos.',
    profileP3: 'Mi enfoque combina funcionalidad real con una interfaz moderna: software que rinde bien y se entiende rapido.',
    goProjects: 'EJECUTAR: VER_PROYECTOS',
    projectsTitle: 'Proyectos y Demos',
    backProfile: '< VOLVER_AL_PERFIL',
    projectsIntro: 'Selecciona un proyecto para ver una demostracion y su valor de negocio. La informacion tecnica queda disponible como detalle opcional.',
    profilePanel: 'Perfil Profesional',
    workStyle: 'Forma de Trabajo',
    downloadCv: 'CV_SERGIO QUINTEROS',
    activityStream: 'Activity Stream',
    awaiting: '> en espera de nuevos eventos',
    oxygenOptimal: 'Optimo',
    oxygenStable: 'Estable',
    envBio: 'Monitoreo Buenos Aires',
    envCloudy: 'Nubosidad alta',
    envRain: 'Lluvia detectada',
    logs: {
      toProjects: ['> Inicializando modulo de proyectos...', '> Cargando demostraciones...', '> Proyectos listos para explorar.'],
      toProfile: ['> Recuperando perfil profesional...', '> Cargando historial laboral...', '> Perfil listo.'],
      initial: ['> Iniciando tablero...', '> Sincronizando datos base...', '> Sistema listo.'],
      closeDemo: '> Simulacion cerrada. Volviendo al hub.',
      geoOk: '> Ubicacion detectada en tiempo real.',
      weatherOk: '> Clima actualizado.',
      weatherFallback: '> Clima estimado para Buenos Aires Centro.',
    },
  },
  en: {
    language: 'Language',
    spanish: 'ES',
    english: 'EN',
    humidity: 'Humidity',
    oxygen: 'Oxygen',
    systemLabel: 'SYS',
    latency: 'Latency',
    optimization: 'Optimization',
    profileTitle: 'Sergio Quinteros | 28 years old, Argentina',
    profileP1: 'I help companies organize their processes with clear, fast and easy-to-use digital tools.',
    profileP2: 'I have 3 years of experience building web solutions. At Rusoft, I reduced critical system response times by 50%, improving day-to-day team operations.',
    profileP3: 'My approach combines real functionality with modern interfaces: software that performs well and is easy to understand.',
    goProjects: 'RUN: VIEW_PROJECTS',
    projectsTitle: 'Projects and Demos',
    backProfile: '< BACK_TO_PROFILE',
    projectsIntro: 'Select a project to see a live demo and business value. Technical details are available as an optional layer.',
    profilePanel: 'Professional Profile',
    workStyle: 'Working Style',
    downloadCv: 'CV_SERGIO QUINTEROS',
    activityStream: 'Activity Stream',
    awaiting: '> awaiting next events',
    oxygenOptimal: 'Optimal',
    oxygenStable: 'Stable',
    envBio: 'Buenos Aires Monitor',
    envCloudy: 'Cloudy conditions',
    envRain: 'Rain detected',
    logs: {
      toProjects: ['> Initializing project module...', '> Loading demonstrations...', '> Projects ready to explore.'],
      toProfile: ['> Recovering professional profile...', '> Loading work history...', '> Profile ready.'],
      initial: ['> Booting workstation...', '> Syncing base data...', '> System ready.'],
      closeDemo: '> Simulation closed. Returning to hub.',
      geoOk: '> Realtime location detected.',
      weatherOk: '> Weather data updated.',
      weatherFallback: '> Estimated weather for Downtown Buenos Aires.',
    },
  },
}

const t = computed(() => i18n[lang.value])

const hardSkillGroups = computed(() => (lang.value === 'es'
  ? [
      { title: 'HERRAMIENTAS PRINCIPALES', highlight: true, items: ['Vue.js 3', 'Laravel 10', 'MySQL'] },
      { title: 'DESARROLLO Y DISENO', highlight: false, items: ['JavaScript', 'PHP', 'Tailwind CSS', 'TypeScript'] },
      { title: 'ENTORNO Y FLUJO', highlight: false, items: ['Git', 'Laragon', 'Terminal Linux/Bash', 'npm'] },
    ]
  : [
      { title: 'CORE TOOLING', highlight: true, items: ['Vue.js 3', 'Laravel 10', 'MySQL'] },
      { title: 'DEVELOPMENT & DESIGN', highlight: false, items: ['JavaScript', 'PHP', 'Tailwind CSS', 'TypeScript'] },
      { title: 'ENVIRONMENT & FLOW', highlight: false, items: ['Git', 'Laragon', 'Terminal Linux/Bash', 'npm'] },
    ]))

const engineeringAttributes = computed(() => (lang.value === 'es'
  ? [
      { title: 'Optimizacion', detail: 'Me enfoco en hacer que los procesos sean mas rapidos y simples de usar.' },
      { title: 'Comunicacion', detail: 'Puedo traducir necesidades de negocio a soluciones claras y utiles.' },
      { title: 'Precision', detail: 'Trabajo con cuidado en los detalles para evitar errores y retrabajo.' },
      { title: 'Agilidad', detail: 'Me adapto rapido a cambios y aprendo nuevas herramientas de forma continua.' },
    ]
  : [
      { title: 'Optimization', detail: 'I focus on making processes faster and easier to use.' },
      { title: 'Communication', detail: 'I translate business needs into clear and useful solutions.' },
      { title: 'Precision', detail: 'I work carefully on details to prevent errors and rework.' },
      { title: 'Adaptability', detail: 'I adapt quickly to change and continuously learn new tools.' },
    ]))

let frame = 0
let reducedMotion = false
const logTimers = []
const sensorTimers = []

const styleVars = computed(() => ({
  '--mx': `${eased.value.x}px`,
  '--my': `${eased.value.y}px`,
  '--bgx': `${-eased.value.x * 0.42}px`,
  '--bgy': `${-eased.value.y * 0.42}px`,
}))

const onPointerMove = (event) => {
  const rect = rootRef.value?.getBoundingClientRect()
  if (!rect) return

  const x = (event.clientX - rect.left) / rect.width - 0.5
  const y = (event.clientY - rect.top) / rect.height - 0.5

  pointer.value = {
    x: x * 24,
    y: y * 16,
  }
}

const onPointerLeave = () => {
  pointer.value = { x: 0, y: 0 }
}

const easePointer = () => {
  if (reducedMotion) {
    eased.value = { x: 0, y: 0 }
    return
  }

  eased.value.x += (pointer.value.x - eased.value.x) * 0.08
  eased.value.y += (pointer.value.y - eased.value.y) * 0.08
  frame = requestAnimationFrame(easePointer)
}

const bootPanels = () => {
  if (reducedMotion) return

  const panels = rootRef.value?.querySelectorAll('[data-panel]')
  if (!panels?.length) return

  animate(
    panels,
    {
      opacity: [0, 1],
      y: [18, 0],
      filter: ['blur(4px)', 'blur(0px)'],
    },
    {
      duration: 0.72,
      delay: stagger(0.08),
      easing: [0.22, 0.7, 0.2, 1],
    },
  )
}

const clearLogTimers = () => {
  logTimers.forEach((id) => clearTimeout(id))
  logTimers.length = 0
}

const clearSensorTimers = () => {
  sensorTimers.forEach((id) => clearInterval(id))
  sensorTimers.length = 0
}

const pushActivityLog = (line) => {
  activityStream.value = [...activityStream.value.slice(-6), line]
}

const runLogSequence = (lines, onComplete) => {
  clearLogTimers()
  activityStream.value = []

  lines.forEach((line, index) => {
    const id = setTimeout(() => {
      pushActivityLog(line)

      if (index === lines.length - 1 && typeof onComplete === 'function') {
        onComplete()
      }
    }, index * 520)
    logTimers.push(id)
  })
}

const irAProyectos = () => {
  if (enTransicion.value || vistaActiva.value === 'PROYECTOS') return
  enTransicion.value = true

  runLogSequence(t.value.logs.toProjects, () => {
    vistaActiva.value = 'PROYECTOS'
    enTransicion.value = false
  })
}

const volverAlPerfil = () => {
  if (enTransicion.value || vistaActiva.value === 'PERFIL') return
  enTransicion.value = true

  runLogSequence(t.value.logs.toProfile, () => {
    vistaActiva.value = 'PERFIL'
    enTransicion.value = false
  })
}

const abrirDemo = (module) => {
  if (enTransicion.value || !module) return
  enTransicion.value = true

  runLogSequence(module.loadLogs?.length ? module.loadLogs : ['> Inicializando visor de proyecto...'], () => {
    proyectoActivo.value = module
    viewerAbierto.value = true
    enTransicion.value = false
  })
}

const cerrarDemo = () => {
  viewerAbierto.value = false
  proyectoActivo.value = null
  pushActivityLog(t.value.logs.closeDemo)
}

const agregarLog = (line) => {
  if (!line) return
  pushActivityLog(line)
}

const formatCoordinate = (value, positiveLabel, negativeLabel) => {
  const absValue = Math.abs(value).toFixed(4)
  const suffix = value >= 0 ? positiveLabel : negativeLabel
  return `${absValue}deg ${suffix}`
}

const setupGeolocation = () => {
  if (!('geolocation' in navigator)) return

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords
      currentCoords.value = { latitude, longitude }
      locationLabel.value = `${formatCoordinate(latitude, 'N', 'S')}, ${formatCoordinate(longitude, 'E', 'W')}`
      pushActivityLog(t.value.logs.geoOk)
      fetchWeather()
    },
    () => {
      currentCoords.value = { ...fallbackCoords }
      locationLabel.value = fallbackLocation
      fetchWeather()
    },
    { enableHighAccuracy: false, timeout: 8000, maximumAge: 120000 },
  )
}

const mapWeatherMode = (weatherCode) => {
  if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99].includes(weatherCode)) return 'rain'
  if ([1, 2, 3, 45, 48].includes(weatherCode)) return 'cloudy'
  return 'clear'
}

const updateEnvironmentFromMode = (mode) => {
  if (mode === 'rain') {
    envLabel.value = t.value.envRain
    weatherMode.value = 'rain'
    oxygenLevel.value = Math.max(89, oxygenLevel.value - 1)
    return
  }

  if (mode === 'cloudy') {
    envLabel.value = t.value.envCloudy
    weatherMode.value = 'cloudy'
    oxygenLevel.value = Math.max(91, oxygenLevel.value)
    return
  }

  envLabel.value = t.value.envBio
  weatherMode.value = 'clear'
}

const fetchWeather = async () => {
  try {
    const { latitude, longitude } = currentCoords.value
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current=relative_humidity_2m,weather_code&timezone=auto`
    const response = await fetch(url, { cache: 'no-store' })
    const payload = await response.json()
    const current = payload?.current
    if (!current) throw new Error('Missing weather payload')

    humidityLevel.value = Number(current.relative_humidity_2m) || humidityLevel.value
    oxygenLevel.value = humidityLevel.value > 86 ? 90 : 95
    updateEnvironmentFromMode(mapWeatherMode(Number(current.weather_code)))
    pushActivityLog(t.value.logs.weatherOk)
  } catch {
    humidityLevel.value = 78
    oxygenLevel.value = 95
    updateEnvironmentFromMode('clear')
    pushActivityLog(t.value.logs.weatherFallback)
  }
}

const measureLatency = async () => {
  const startedAt = performance.now()
  try {
    await fetch(window.location.origin, { cache: 'no-store' })
    latencyMs.value = Math.max(4, Math.round(performance.now() - startedAt))
  } catch {
    latencyMs.value = 28
  }
}

const optimizationLabel = computed(() => {
  if (latencyMs.value <= 18) return '+50% Efficiency'
  if (latencyMs.value <= 32) return '+32% Efficiency'
  return 'Baseline Efficiency'
})

const oxygenLabelText = computed(() => (oxygenLevel.value >= 94 ? t.value.oxygenOptimal : t.value.oxygenStable))

watch(lang, () => {
  updateEnvironmentFromMode(weatherMode.value)
})

onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  easePointer()
  bootPanels()
  setupGeolocation()
  fetchWeather()
  measureLatency()
  sensorTimers.push(setInterval(fetchWeather, 10 * 60 * 1000))
  sensorTimers.push(setInterval(measureLatency, 12000))
  runLogSequence(t.value.logs.initial)
})

onBeforeUnmount(() => {
  cancelAnimationFrame(frame)
  clearLogTimers()
  clearSensorTimers()
})
</script>

<template>
  <div class="relative min-h-screen overflow-hidden" :style="styleVars">
    <div class="absolute inset-0 z-0" aria-hidden="true">
      <img
        :src="forestCityBokeh"
        alt=""
        class="weather-bg fixed inset-0 h-full w-full object-cover"
        :class="`weather-bg--${weatherMode}`"
        :style="{ transform: 'translate3d(var(--bgx), var(--bgy), 0)' }"
      />
    </div>

    <div class="absolute inset-0 z-10 bg-gradient-to-br from-slate-50/40 to-transparent backdrop-blur-sm" aria-hidden="true" />
    <div class="weather-atmosphere absolute inset-0 z-[10]" :class="`weather-atmosphere--${weatherMode}`" aria-hidden="true" />
    <div v-if="weatherMode === 'cloudy'" class="weather-clouds absolute inset-0 z-[11]" aria-hidden="true" />
    <div v-if="weatherMode === 'rain'" class="weather-rain absolute inset-0 z-[12]" aria-hidden="true" />

    <div class="relative z-20 flex min-h-screen items-center justify-center overflow-hidden p-4 sm:p-6">
      <section
        ref="rootRef"
        class="workstation relative isolate h-[95vh] w-[80vw] max-h-[980px] max-w-[1680px] overflow-hidden rounded-3xl text-slate-900"
        @mousemove="onPointerMove"
        @mouseleave="onPointerLeave"
      >
        <div class="ui-layer relative z-20 flex h-full w-full flex-col overflow-hidden p-4 sm:p-6">
          <header
            data-panel
            class="metal-panel mb-4 grid gap-3 rounded-xl px-4 py-3 text-[12px] font-medium tracking-[0.08em] text-slate-800 lg:grid-cols-[1.1fr_1fr_1.2fr]"
          >
            <div class="inline-flex items-center gap-2 font-mono"><Waves :size="14" class="text-emerald-600" /> LOC: {{ locationLabel }}</div>

            <div class="space-y-2">
              <div class="inline-flex items-center gap-2 font-mono"><Leaf :size="14" class="text-emerald-600" /> ENV: {{ envLabel }}</div>
              <div class="life-widget">
                <span class="life-label">{{ t.humidity }} {{ humidityLevel }}%</span>
                <div class="life-track">
                  <span class="life-fill" :style="{ width: `${humidityLevel}%` }" />
                </div>
              </div>
              <div class="life-widget">
                <span class="life-label">{{ t.oxygen }}: {{ oxygenLabelText }}</span>
                <div class="life-track">
                  <span class="life-fill oxygen" :style="{ width: `${oxygenLevel}%` }" />
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between gap-3 lg:justify-end">
              <div class="inline-flex items-center gap-2 font-mono"><Activity :size="14" class="text-emerald-600" /> {{ t.systemLabel }}: {{ t.latency }} {{ latencyMs }}ms | {{ t.optimization }}: {{ optimizationLabel }}</div>
              <div class="inline-flex items-center gap-1 rounded-lg border border-slate-300/80 bg-white/70 p-1">
                <span class="px-1.5 text-[10px] uppercase tracking-[0.12em] text-slate-500">{{ t.language }}</span>
                <button class="lang-btn" :class="{ active: lang === 'es' }" @click="lang = 'es'">{{ t.spanish }}</button>
                <button class="lang-btn" :class="{ active: lang === 'en' }" @click="lang = 'en'">{{ t.english }}</button>
              </div>
            </div>
          </header>

          <div class="grid min-h-0 flex-1 grid-cols-12 gap-4 overflow-hidden">
            <main
              data-panel
              class="metal-panel command-core kernel-scroll relative col-span-12 flex min-h-0 flex-col overflow-x-hidden overflow-y-auto rounded-2xl bg-white/90 p-6 backdrop-blur-xl sm:p-8 lg:col-span-9"
            >
              <div class="palm-shadow-layer" aria-hidden="true">
                <svg viewBox="0 0 1200 380" preserveAspectRatio="none">
                  <defs>
                    <linearGradient id="palmShade" x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0%" stop-color="rgba(15, 23, 42, 0.18)" />
                      <stop offset="100%" stop-color="rgba(15, 23, 42, 0.04)" />
                    </linearGradient>
                  </defs>
                  <path d="M-40 25C140 95 270 20 460 120C650 220 810 95 1030 170C1130 205 1190 195 1260 230L1260 0L-40 0Z" fill="url(#palmShade)" />
                  <path d="M-20 130C120 180 260 120 370 190C520 285 690 180 860 240C980 285 1120 265 1240 320L1240 20C1030 10 910 45 780 85C610 140 450 65 280 95C170 115 70 140 -20 130Z" fill="url(#palmShade)" opacity="0.62" />
                </svg>
              </div>

              <Transition name="kernel-fade" mode="out-in">
                <div v-if="vistaActiva === 'PERFIL'" key="perfil-view">
                  <h1 class="font-[Inter] text-[clamp(2rem,4vw,3.8rem)] font-extrabold tracking-[-0.03em] text-slate-900">
                    {{ t.profileTitle }}
                  </h1>
                  <p class="mt-5 max-w-[72ch] font-[Inter] text-[1rem] leading-relaxed text-slate-800">
                    {{ t.profileP1 }}
                  </p>
                  <p class="mt-4 max-w-[72ch] font-[Inter] text-[1rem] leading-relaxed text-slate-800">
                    {{ t.profileP2 }}
                  </p>
                  <p class="mt-4 max-w-[72ch] font-[Inter] text-[1rem] leading-relaxed text-slate-800">
                    {{ t.profileP3 }}
                  </p>

                  <div class="mt-7">
                    <button class="switch-btn execute-btn" :disabled="enTransicion" @click="irAProyectos">{{ t.goProjects }}</button>
                  </div>
                </div>

                <div v-else key="projects-view">
                  <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="font-[Inter] text-3xl font-bold text-slate-900">{{ t.projectsTitle }}</h2>
                    <button class="switch-btn btn-secondary" :disabled="enTransicion" @click="volverAlPerfil">{{ t.backProfile }}</button>
                  </div>
                  <p class="mt-1 font-[Inter] text-slate-700">{{ t.projectsIntro }}</p>
                  <ProjectModule :lang="lang" @open-demo="abrirDemo" />
                </div>
              </Transition>
            </main>

            <aside
              data-panel
              class="metal-panel rack-scroll col-span-12 min-h-0 overflow-y-auto rounded-2xl bg-white/40 p-4 backdrop-blur-xl lg:col-span-3"
            >
              <p class="mb-3 font-mono text-[12px] uppercase tracking-[0.18em] text-slate-900">{{ t.profilePanel }}</p>

              <article
                v-for="group in hardSkillGroups"
                :key="group.title"
                class="rack-block mt-3 rounded-lg border bg-slate-100/70 p-3 first:mt-0"
                :class="group.highlight ? 'border-emerald-300/90 ring-1 ring-emerald-200/70' : 'border-slate-300/80'"
              >
                <h3 class="font-mono text-[11px] uppercase tracking-[0.12em] text-slate-900">{{ group.title }}</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span v-for="skill in group.items" :key="skill" class="rack-badge">
                    <span class="status-dot" />
                    {{ skill }}
                  </span>
                </div>
              </article>

              <button class="cv-btn mt-4 w-full">{{ t.downloadCv }}</button>

              <article class="rack-block mt-3 rounded-lg border border-slate-300/80 bg-slate-100/70 p-3">
                <h3 class="font-mono text-[11px] uppercase tracking-[0.12em] text-slate-900">{{ t.workStyle }}</h3>
                <ul class="mt-3 space-y-3 leading-relaxed">
                  <li v-for="attr in engineeringAttributes" :key="attr.title" class="text-sm text-slate-900">
                    <span class="font-semibold">{{ attr.title }}:</span>
                    <span class="text-slate-800"> {{ attr.detail }}</span>
                  </li>
                </ul>
              </article>
            </aside>
          </div>

          <footer
            data-panel
            class="metal-panel mt-4 rounded-xl bg-white/88 px-4 py-3 backdrop-blur-xl"
          >
            <p class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ t.activityStream }}</p>
            <div class="space-y-1 font-mono text-[12px] text-emerald-700">
              <p v-for="(line, index) in activityStream" :key="`${index}-${line}`">{{ line }}</p>
              <p class="inline-flex items-center gap-1 text-slate-500">{{ t.awaiting }} <span class="cursor-block">_</span></p>
            </div>
          </footer>
        </div>
      </section>
    </div>

    <ProjectViewer
      :visible="viewerAbierto"
      :project="proyectoActivo"
      :lang="lang"
      @close="cerrarDemo"
      @log="agregarLog"
    />
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');

.workstation {
  font-family: Inter, sans-serif;
}

.ui-layer {
  transform: translate3d(calc(var(--mx) * 0.06), calc(var(--my) * 0.06), 0);
  transition: transform 0.08s linear;
}

.weather-bg {
  transition: filter 500ms ease, transform 150ms linear;
}

.weather-bg--clear {
  filter: brightness(1.1) saturate(1.05) contrast(1);
}

.weather-bg--cloudy {
  filter: brightness(0.94) saturate(0.86) contrast(0.96);
}

.weather-bg--rain {
  filter: brightness(0.82) saturate(0.72) contrast(0.95);
}

.weather-atmosphere {
  pointer-events: none;
  transition: background 500ms ease, opacity 500ms ease;
}

.weather-atmosphere--clear {
  background: radial-gradient(1000px 300px at 50% -10%, rgba(255, 255, 255, 0.08), transparent 60%);
}

.weather-atmosphere--cloudy {
  background:
    radial-gradient(900px 280px at 25% 8%, rgba(148, 163, 184, 0.18), transparent 60%),
    radial-gradient(900px 300px at 78% 15%, rgba(148, 163, 184, 0.2), transparent 62%),
    linear-gradient(180deg, rgba(226, 232, 240, 0.12), rgba(148, 163, 184, 0.06));
}

.weather-atmosphere--rain {
  background:
    linear-gradient(180deg, rgba(15, 23, 42, 0.2), rgba(30, 41, 59, 0.14)),
    radial-gradient(900px 260px at 30% 10%, rgba(51, 65, 85, 0.24), transparent 62%);
}

.lang-btn {
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 4px 8px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  line-height: 1;
  color: #475569;
  background: transparent;
}

.lang-btn.active {
  color: #0f172a;
  border-color: rgba(148, 163, 184, 0.7);
  background: rgba(255, 255, 255, 0.9);
}

.weather-clouds {
  background:
    radial-gradient(1200px 320px at 15% 8%, rgba(148, 163, 184, 0.2), transparent 60%),
    radial-gradient(1100px 280px at 80% 14%, rgba(148, 163, 184, 0.22), transparent 62%);
  animation: cloudDrift 24s linear infinite alternate;
  pointer-events: none;
}

.weather-rain {
  background-image: repeating-linear-gradient(
    -12deg,
    rgba(148, 163, 184, 0.24) 0 2px,
    rgba(148, 163, 184, 0) 2px 16px
  );
  background-size: 100% 140%;
  animation: rainDrop 1s linear infinite;
  pointer-events: none;
}

.life-widget {
  display: grid;
  grid-template-columns: 116px 1fr;
  gap: 8px;
  align-items: center;
}

.life-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  color: #334155;
}

.life-track {
  height: 8px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.3);
  overflow: hidden;
}

.life-fill {
  display: block;
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, rgba(16, 185, 129, 0.8), rgba(16, 185, 129, 0.46));
  clip-path: polygon(0 42%, 7% 28%, 14% 54%, 22% 32%, 30% 62%, 38% 36%, 47% 60%, 55% 41%, 64% 66%, 72% 45%, 81% 57%, 90% 38%, 100% 50%, 100% 100%, 0 100%);
}

.life-fill.oxygen {
  background: linear-gradient(90deg, rgba(5, 150, 105, 0.8), rgba(16, 185, 129, 0.5));
}

.metal-panel {
  border: 1px solid transparent;
  background:
    linear-gradient(rgba(248, 250, 252, 0.9), rgba(241, 245, 249, 0.82)) padding-box,
    linear-gradient(135deg, rgba(148, 163, 184, 0.95), rgba(255, 255, 255, 0.96)) border-box;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.92),
    0 20px 34px rgba(15, 23, 42, 0.18);
}

.command-core {
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}

.kernel-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 116, 139, 0.22) transparent;
}

.kernel-scroll::-webkit-scrollbar {
  width: 5px;
}

.kernel-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.kernel-scroll::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.2);
  border-radius: 999px;
}

.kernel-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(100, 116, 139, 0.38);
}

.rack-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 116, 139, 0.28) transparent;
}

.rack-scroll::-webkit-scrollbar {
  width: 6px;
}

.rack-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.rack-scroll::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.26);
  border-radius: 999px;
}

.rack-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(100, 116, 139, 0.42);
}

.palm-shadow-layer {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: 0.28;
  mix-blend-mode: multiply;
  animation: palmSway 60s ease-in-out infinite alternate;
}

.palm-shadow-layer svg {
  width: 100%;
  height: 100%;
}

.rack-block:hover {
  border-color: #10b981;
  box-shadow:
    inset 0 0 0 1px rgba(16, 185, 129, 0.58),
    0 0 24px rgba(16, 185, 129, 0.22);
}

.rack-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.1em;
  border: 1px solid rgba(148, 163, 184, 0.82);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.7);
  color: #0f172a;
  padding: 4px 7px;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: #10b981;
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.55);
}

.cv-btn {
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #0f172a;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid transparent;
  background:
    linear-gradient(rgba(248, 250, 252, 0.96), rgba(226, 232, 240, 0.96)) padding-box,
    linear-gradient(135deg, rgba(148, 163, 184, 0.95), rgba(255, 255, 255, 0.98)) border-box;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.96),
    0 10px 16px rgba(15, 23, 42, 0.16);
  transition: transform 120ms ease, box-shadow 120ms ease;
}

.cv-btn:hover {
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.98),
    0 12px 20px rgba(15, 23, 42, 0.2);
}

.cv-btn:active {
  transform: translateY(2px);
  box-shadow:
    inset 0 2px 5px rgba(15, 23, 42, 0.2),
    0 5px 10px rgba(15, 23, 42, 0.12);
}

.switch-btn {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #ffffff;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(5, 150, 105, 0.86);
  background: #059669;
  box-shadow: 0 10px 18px rgba(5, 150, 105, 0.34);
  transition: transform 120ms ease, box-shadow 120ms ease, background-color 120ms ease;
}

.switch-btn:hover {
  background: #10b981;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.35),
    0 12px 22px rgba(5, 150, 105, 0.36);
}

.switch-btn:active {
  transform: translateY(2px);
  box-shadow:
    inset 0 2px 5px rgba(15, 23, 42, 0.2),
    0 5px 10px rgba(15, 23, 42, 0.12);
}

.switch-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: 0 8px 12px rgba(15, 23, 42, 0.1);
}

.btn-secondary {
  color: #475569;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.95);
  box-shadow: 0 8px 14px rgba(15, 23, 42, 0.1);
}

.btn-secondary:hover {
  background: #f8fafc;
  box-shadow: 0 10px 16px rgba(15, 23, 42, 0.12);
}

.execute-btn {
  position: relative;
  overflow: hidden;
}

.execute-btn::before {
  content: '';
  position: absolute;
  top: -130%;
  left: -45%;
  width: 52%;
  height: 320%;
  transform: rotate(18deg);
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(16, 185, 129, 0.45) 52%,
    rgba(255, 255, 255, 0) 100%
  );
  opacity: 0;
  pointer-events: none;
}

.execute-btn:hover {
  box-shadow:
    0 0 0 1px rgba(16, 185, 129, 0.44),
    0 0 22px rgba(16, 185, 129, 0.38),
    0 12px 20px rgba(15, 23, 42, 0.18);
}

.execute-btn:hover::before {
  opacity: 1;
  animation: retinaScan 560ms cubic-bezier(0.2, 0.65, 0.2, 1);
}

.cursor-block {
  animation: blink 1.05s steps(1) infinite;
}

@keyframes blink {
  0%,
  50% {
    opacity: 1;
  }

  51%,
  100% {
    opacity: 0;
  }
}

@keyframes palmSway {
  0% {
    transform: translateX(-2%) rotate(-1.2deg) scale(1.02);
  }

  100% {
    transform: translateX(2%) rotate(1deg) scale(1.04);
  }
}

@keyframes retinaScan {
  0% {
    transform: translateX(-145%) rotate(18deg);
  }

  100% {
    transform: translateX(260%) rotate(18deg);
  }
}

@keyframes cloudDrift {
  from {
    transform: translateX(-1.5%);
  }

  to {
    transform: translateX(1.5%);
  }
}

@keyframes rainDrop {
  from {
    background-position: 0 -12px;
  }

  to {
    background-position: 0 24px;
  }
}

.kernel-fade-enter-active,
.kernel-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.kernel-fade-enter-from,
.kernel-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 1280px) {
  .workstation {
    width: 92vw;
  }
}

@media (max-width: 1024px) {
  .ui-layer {
    transform: none;
  }

  .workstation {
    width: 96vw;
    height: 94vh;
  }
}
</style>
