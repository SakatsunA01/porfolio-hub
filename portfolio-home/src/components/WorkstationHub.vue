<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { animate, stagger } from 'motion'
import { Activity, Leaf, Waves } from 'lucide-vue-next'
import ProjectModule from './ProjectModule.vue'
import ProjectViewer from './ProjectViewer.vue'
import forestCityBokeh from '../assets/images/forest-city-bokeh2.jpg'
import { resolveEnvironmentState } from '../environment/environmentController'
import { resolveAdaptiveState } from '../system/adaptiveStateManager'

const rootRef = ref(null)
const mainPanelRef = ref(null)
const pointer = ref({ x: 0, y: 0 })
const eased = ref({ x: 0, y: 0 })

const vistaActiva = ref('PERFIL')
const businessStream = ref([])
const technicalStream = ref([])
const behavioralNarrationKey = ref('')
const lastNarratorMessageKey = ref('')
const lastNarratorMessageAt = ref(0)
const longIdleNotified = ref(false)
const enTransicion = ref(false)
const viewerAbierto = ref(false)
const proyectoActivo = ref(null)
const syncPulse = ref(false)
const districtEntryOverlay = ref({
  visible: false,
  name: '',
  modules: [],
  status: 'syncing',
})
const consoleMode = ref('business')
const consoleCollapsed = ref(false)
const isMobileViewport = ref(false)
const mobileDrawerOpen = ref(false)
const mobileConsoleOpen = ref(false)
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
const environmentVars = ref(resolveEnvironmentState({ weatherCode: 0, humidity: humidityLevel.value }).cssVars)
const scrollDepth = ref(0)
const interactionEvents = ref([])
const lastInteractionAt = ref(0)
const idleMs = ref(0)
const adaptiveVars = ref(resolveAdaptiveState().cssVars)
const mobileSheetDragY = ref(0)
const mobileSheetStartY = ref(null)

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
    profilePanel: 'Stack General',
    activeStackPanel: 'Stack Contextual',
    districtRole: 'Rol del distrito',
    workStyle: 'Forma de Trabajo',
    downloadCv: 'CV_SERGIO QUINTEROS',
    activityStream: 'System Console',
    consoleBusiness: 'Modo negocio',
    consoleExperimental: 'Modo experimental',
    consoleCollapse: 'Colapsar',
    consoleExpand: 'Expandir',
    mobileStack: 'Stack',
    mobileConsole: 'Narrador',
    mobileArchitecture: 'Arquitectura',
    awaitingBusiness: '> en espera de nuevos impactos',
    awaitingExperimental: '> observando patrones de interaccion',
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
    businessLogs: {
      initial: ['Sistema listo -> capacidades clave disponibles.', 'Entorno preparado -> lectura clara para negocio y reclutamiento.'],
      toProjects: ['Distritos activados -> soluciones disponibles para explorar.', 'Vista de proyectos abierta -> valor de negocio priorizado.'],
      toProfile: ['Perfil restaurado -> propuesta profesional simplificada.', 'Contexto principal recuperado -> foco en impacto y experiencia.'],
      closeDemo: 'Distrito cerrado -> exploracion general recuperada.',
      geoOk: 'Ubicacion actualizada -> contexto ambiental alineado.',
      weatherOk: 'Clima sincronizado -> atmosfera ajustada sin interrumpir la lectura.',
      weatherFallback: 'Contexto ambiental estimado -> experiencia estable mantenida.',
    },
    entryDistrict: 'Ingreso al distrito preparado -> contexto activo sincronizado.',
    activeModules: 'Modulos activos',
    entryStatus: 'Estado del sistema',
    mobileStateStable: 'Calma estable',
    mobileStateAdaptive: 'Sistema adaptando foco',
    mobileStateDeep: 'Exploracion profunda',
    experimentalPatterns: {
      idle: 'Pausa detectada -> el usuario esta procesando contexto con calma.',
      profileBrowse: 'Lectura general activa -> la narrativa profesional sigue al frente.',
      projectBrowse: 'Exploracion de distritos detectada -> comparacion de soluciones en curso.',
      scrollDeep: 'Profundidad de lectura alta -> interes sostenido en detalles de valor.',
      projectInteract: 'Interaccion profunda detectada -> evaluacion activa de un distrito.',
      syncing: 'Cambio de contexto detectado -> el sistema mantiene continuidad narrativa.',
      returnFocus: 'Reanudacion detectada -> la experiencia vuelve a ganar atencion.',
    },
    narrator: {
      firstVisit: 'Entorno listo.',
      idleReflection: 'Sistema estable.',
      commerceEntry: 'Distrito Comercio sincronizado.',
      operationsEntry: 'Distrito Operaciones sincronizado.',
      deliveryEntry: 'Distrito Delivery sincronizado.',
      liveEntry: 'Distrito Live sincronizado.',
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
    profilePanel: 'General Stack',
    activeStackPanel: 'Contextual Stack',
    districtRole: 'District role',
    workStyle: 'Working Style',
    downloadCv: 'CV_SERGIO QUINTEROS',
    activityStream: 'System Console',
    consoleBusiness: 'Business mode',
    consoleExperimental: 'Experimental mode',
    consoleCollapse: 'Collapse',
    consoleExpand: 'Expand',
    mobileStack: 'Stack',
    mobileConsole: 'Narrator',
    mobileArchitecture: 'Architecture',
    awaitingBusiness: '> awaiting next impact signals',
    awaitingExperimental: '> observing interaction patterns',
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
    businessLogs: {
      initial: ['System ready -> core capabilities available.', 'Environment prepared -> business-first reading remains clear.'],
      toProjects: ['Districts activated -> solutions available to explore.', 'Project view opened -> business value takes priority.'],
      toProfile: ['Profile restored -> professional positioning simplified.', 'Primary context recovered -> impact and experience back in focus.'],
      closeDemo: 'District closed -> general exploration restored.',
      geoOk: 'Location updated -> environmental context aligned.',
      weatherOk: 'Weather synced -> atmosphere adjusted without interrupting reading flow.',
      weatherFallback: 'Estimated environment applied -> experience remains stable.',
    },
    entryDistrict: 'District entry prepared -> active context synchronized.',
    activeModules: 'Active modules',
    entryStatus: 'System status',
    mobileStateStable: 'Stable calm',
    mobileStateAdaptive: 'System adapting focus',
    mobileStateDeep: 'Deep exploration',
    experimentalPatterns: {
      idle: 'Pause detected -> the visitor is processing context calmly.',
      profileBrowse: 'General reading active -> professional narrative stays in focus.',
      projectBrowse: 'District exploration detected -> solution comparison is underway.',
      scrollDeep: 'Reading depth is high -> sustained interest in value details.',
      projectInteract: 'Deep interaction detected -> an active district is being evaluated.',
      syncing: 'Context shift detected -> the system keeps narrative continuity intact.',
      returnFocus: 'Attention regained -> the experience is back in active focus.',
    },
    narrator: {
      firstVisit: 'Environment ready.',
      idleReflection: 'System stable.',
      commerceEntry: 'Commerce District synchronized.',
      operationsEntry: 'Operations District synchronized.',
      deliveryEntry: 'Delivery District synchronized.',
      liveEntry: 'Live District synchronized.',
    },
  },
}

const t = computed(() => i18n[lang.value])

const stateDictionary = computed(() => (lang.value === 'es'
  ? {
      stable: 'ESTABLE',
      syncing: 'SINCRONIZANDO',
      'deep-exploration': 'EXPLORACION PROFUNDA',
      idle: 'IDLE',
      browsing: 'NAVEGANDO',
      interacting: 'INTERACTUANDO',
    }
  : {
      stable: 'STABLE',
      syncing: 'SYNCING',
      'deep-exploration': 'DEEP EXPLORATION',
      idle: 'IDLE',
      browsing: 'BROWSING',
      interacting: 'INTERACTING',
    }))

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

const projectTechSet = computed(() => new Set((proyectoActivo.value?.relatedTech || []).map((item) => item.toLowerCase())))
const hasActiveProject = computed(() => Boolean(proyectoActivo.value))
const panelHeading = computed(() => (hasActiveProject.value ? t.value.activeStackPanel : t.value.profilePanel))
const districtRoleLabel = computed(() => proyectoActivo.value?.roleLabel || '')

const badgeContextClass = (skill) => {
  if (!hasActiveProject.value) return ''
  return projectTechSet.value.has(skill.toLowerCase()) ? 'rack-badge--active' : 'rack-badge--dimmed'
}

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
let viewportMedia = null
const logTimers = []
const sensorTimers = []

const styleVars = computed(() => ({
  '--mx': `${eased.value.x}px`,
  '--my': `${eased.value.y}px`,
  '--bgx': `${-eased.value.x * 0.26}px`,
  '--bgy': `${-eased.value.y * 0.22}px`,
  ...environmentVars.value,
  ...adaptiveVars.value,
}))

const compactLocationLabel = computed(() => {
  const parts = locationLabel.value.split(',')
  return parts.length > 1 ? parts[0].trim() : locationLabel.value
})

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

const pushConsoleEntry = (stream, line) => {
  stream.value = [...stream.value.slice(-6), line]
}

const pushBusinessLog = (line) => {
  if (!line) return
  pushConsoleEntry(businessStream, line)
}

const pushTechnicalLog = (line) => {
  if (!line) return
  pushConsoleEntry(technicalStream, line)
}

const pushExperimentalLog = (line) => {
  if (!line) return
  pushConsoleEntry(technicalStream, line)
}

const pushNarratorMessage = (key, line, target = 'experimental', minInterval = 12000) => {
  if (!line) return

  const now = Date.now()
  if (lastNarratorMessageKey.value === key && now - lastNarratorMessageAt.value < minInterval) return
  if (now - lastNarratorMessageAt.value < minInterval) return

  lastNarratorMessageKey.value = key
  lastNarratorMessageAt.value = now

  if (target === 'business') {
    pushBusinessLog(line)
    return
  }

  pushExperimentalLog(`> ${line}`)
}

const resolveDistrictMessage = (module) => {
  const slug = module?.slug || ''
  if (slug.includes('sak') || slug.includes('commerce')) return t.value.narrator.commerceEntry
  if (slug.includes('dunamis')) return t.value.narrator.operationsEntry
  if (slug.includes('delivery')) return t.value.narrator.deliveryEntry
  return t.value.narrator.liveEntry
}

const startSystemSyncPulse = () => {
  syncPulse.value = true
  const pulseTimer = setTimeout(() => {
    syncPulse.value = false
  }, 1400)
  logTimers.push(pulseTimer)
}

const showDistrictEntryOverlay = (module) => {
  districtEntryOverlay.value = {
    visible: true,
    name: module.name,
    modules: module.stack?.slice(0, 3) || module.relatedTech?.slice(0, 3) || [],
    status: 'syncing',
  }

  const overlayTimer = setTimeout(() => {
    districtEntryOverlay.value = {
      ...districtEntryOverlay.value,
      visible: false,
      status: systemState.value,
    }
  }, 680)

  logTimers.push(overlayTimer)
}

const runLogSequence = (technicalLines, onComplete, businessLines = []) => {
  clearLogTimers()
  technicalStream.value = []
  businessStream.value = []

  technicalLines.forEach((line, index) => {
    const id = setTimeout(() => {
      pushTechnicalLog(line)
    }, index * 520)
    logTimers.push(id)
  })

  businessLines.forEach((line, index) => {
    const id = setTimeout(() => {
      pushBusinessLog(line)
    }, index * 520)
    logTimers.push(id)
  })

  const finalDelay = (Math.max(technicalLines.length, businessLines.length, 1) - 1) * 520
  const completionId = setTimeout(() => {
    if (typeof onComplete === 'function') {
      onComplete()
    }
  }, finalDelay)
  logTimers.push(completionId)
}

const irAProyectos = () => {
  if (enTransicion.value || vistaActiva.value === 'PROYECTOS') return
  enTransicion.value = true

  runLogSequence(t.value.logs.toProjects, () => {
    vistaActiva.value = 'PROYECTOS'
    enTransicion.value = false
  }, t.value.businessLogs.toProjects)
}

const volverAlPerfil = () => {
  if (enTransicion.value || vistaActiva.value === 'PERFIL') return
  enTransicion.value = true

  runLogSequence(t.value.logs.toProfile, () => {
    vistaActiva.value = 'PERFIL'
    enTransicion.value = false
  }, t.value.businessLogs.toProfile)
}

const abrirDemo = (module) => {
  if (enTransicion.value || !module) return
  enTransicion.value = true
  if (!isMobileViewport.value) {
    consoleCollapsed.value = false
  }
  const syncEvent = lang.value === 'en'
    ? `> System event: syncing district ${module.name}.`
    : `> Evento del sistema: sincronizando distrito ${module.name}.`
  const loadSequence = module.loadLogs?.length
    ? [syncEvent, '[STORE_MUTATION] activeDistrict <- pending_sync', '[LAZY_MODULE] district viewer hydrated', ...module.loadLogs]
    : [syncEvent, '[STORE_MUTATION] activeDistrict <- pending_sync', '[LAZY_MODULE] district viewer hydrated', '> Inicializando visor de proyecto...']
  const businessSequence = [
    `${module.name} -> ${module.impactSummary?.[0] || module.impact}`,
    lang.value === 'en'
      ? 'Demo access prepared -> decision flow becomes tangible.'
      : 'Acceso a demo preparado -> el flujo de decision se vuelve tangible.',
  ]

  runLogSequence(loadSequence, () => {
    proyectoActivo.value = module
    startSystemSyncPulse()
    showDistrictEntryOverlay(module)
    pushTechnicalLog(lang.value === 'en'
      ? `> District linked: ${module.name} online.`
      : `> Distrito enlazado: ${module.name} en linea.`)
    pushTechnicalLog(`[SYSTEM_ENTRY] ${module.slug || module.name} -> ${systemStateLabel.value}`)
    pushTechnicalLog('[COMPONENT_MOUNT] ProjectViewer mounted')
    pushBusinessLog(lang.value === 'en'
      ? 'District linked -> business and technical context stay aligned.'
      : 'Distrito enlazado -> contexto de negocio y tecnico alineados.')
    pushBusinessLog(t.value.entryDistrict)
    pushNarratorMessage(
      `entry-${module.slug || module.name}`,
      resolveDistrictMessage(module),
      'business',
      5000,
    )
    const viewerTimer = setTimeout(() => {
      viewerAbierto.value = true
    }, 680)
    logTimers.push(viewerTimer)
    enTransicion.value = false
  }, businessSequence)
}

const cerrarDemo = () => {
  viewerAbierto.value = false
  proyectoActivo.value = null
  pushTechnicalLog(t.value.logs.closeDemo)
  pushTechnicalLog('[STORE_MUTATION] activeDistrict <- null')
  pushBusinessLog(t.value.businessLogs.closeDemo)
}

const agregarLog = (line) => {
  if (!line) return
  pushTechnicalLog(line)
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
      pushTechnicalLog(t.value.logs.geoOk)
      pushTechnicalLog('[API_CALL] geolocation resolved')
      pushBusinessLog(t.value.businessLogs.geoOk)
      fetchWeather()
    },
    () => {
      currentCoords.value = { ...fallbackCoords }
      locationLabel.value = fallbackLocation
      pushTechnicalLog('[API_CALL] geolocation fallback applied')
      fetchWeather()
    },
    { enableHighAccuracy: false, timeout: 8000, maximumAge: 120000 },
  )
}

const applyEnvironmentState = (weatherCode, humidity = humidityLevel.value) => {
  const environment = resolveEnvironmentState({
    weatherCode,
    humidity,
    reducedMotion,
  })

  weatherMode.value = environment.mode
  environmentVars.value = environment.cssVars
  envLabel.value = t.value[environment.environmentLabelKey] || t.value.envBio

  if (environment.mode === 'rain') {
    oxygenLevel.value = Math.max(89, oxygenLevel.value - 1)
    return
  }

  if (environment.mode === 'cloudy') {
    oxygenLevel.value = Math.max(91, oxygenLevel.value)
  }
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
    applyEnvironmentState(Number(current.weather_code), humidityLevel.value)
    pushTechnicalLog(t.value.logs.weatherOk)
    pushTechnicalLog('[API_CALL] weather forecast synced')
    pushBusinessLog(t.value.businessLogs.weatherOk)
  } catch {
    humidityLevel.value = 78
    oxygenLevel.value = 95
    applyEnvironmentState(0, humidityLevel.value)
    pushTechnicalLog(t.value.logs.weatherFallback)
    pushTechnicalLog('[API_CALL] weather fallback applied')
    pushBusinessLog(t.value.businessLogs.weatherFallback)
  }
}

const measureLatency = async () => {
  const startedAt = performance.now()
  try {
    await fetch(window.location.origin, { cache: 'no-store' })
    latencyMs.value = Math.max(4, Math.round(performance.now() - startedAt))
    pushTechnicalLog(`[API_CALL] latency probe completed in ${latencyMs.value}ms`)
  } catch {
    latencyMs.value = 28
    pushTechnicalLog('[API_CALL] latency probe failed, fallback applied')
  }
}

const optimizationLabel = computed(() => {
  if (latencyMs.value <= 18) return '+50% Efficiency'
  if (latencyMs.value <= 32) return '+32% Efficiency'
  return 'Baseline Efficiency'
})

const oxygenLabelText = computed(() => (oxygenLevel.value >= 94 ? t.value.oxygenOptimal : t.value.oxygenStable))

const recentInteractionCount = computed(() => interactionEvents.value.length)
const adaptiveState = computed(() => resolveAdaptiveState({
  scrollDepth: scrollDepth.value,
  interactionCount: recentInteractionCount.value,
  hasActiveProject: viewerAbierto.value || Boolean(proyectoActivo.value),
  isTransitioning: enTransicion.value,
  idleMs: idleMs.value,
  isProjectsView: vistaActiva.value === 'PROYECTOS',
}))
const userMode = computed(() => adaptiveState.value.userMode)
const systemState = computed(() => adaptiveState.value.systemState)

const systemStateLabel = computed(() => stateDictionary.value[systemState.value])
const userModeLabel = computed(() => stateDictionary.value[userMode.value])
const mobileStateLabel = computed(() => {
  if (systemState.value === 'deep-exploration') return t.value.mobileStateDeep
  if (systemState.value === 'syncing' || userMode.value === 'interacting') return t.value.mobileStateAdaptive
  return t.value.mobileStateStable
})

const statusToneClass = (value) => {
  if (value === 'syncing' || value === 'interacting') return 'is-busy'
  if (value === 'deep-exploration' || value === 'browsing') return 'is-shifting'
  return 'is-stable'
}

const pruneInteractions = (now = Date.now()) => {
  interactionEvents.value = interactionEvents.value.filter((timestamp) => now - timestamp <= 12000)
}

const registerInteraction = () => {
  const now = Date.now()
  if (now - lastInteractionAt.value < 700) return

  lastInteractionAt.value = now
  idleMs.value = 0
  longIdleNotified.value = false
  pruneInteractions(now)
  interactionEvents.value = [...interactionEvents.value, now]
}

const updateScrollDepth = () => {
  const target = mainPanelRef.value
  if (!target) {
    scrollDepth.value = 0
    return
  }

  const scrollable = target.scrollHeight - target.clientHeight
  scrollDepth.value = scrollable > 0 ? Number((target.scrollTop / scrollable).toFixed(3)) : 0
}

const handleViewportInteraction = () => {
  registerInteraction()
}

const activeConsoleStream = computed(() => (consoleMode.value === 'business' ? businessStream.value : technicalStream.value))
const consoleAwaitingLabel = computed(() => (consoleMode.value === 'business' ? t.value.awaitingBusiness : t.value.awaitingExperimental))

const resolveBehaviorNarrationKey = () => {
  if (systemState.value === 'syncing') return 'syncing'
  if (userMode.value === 'idle') return 'idle'
  if (viewerAbierto.value || proyectoActivo.value) return userMode.value === 'interacting' ? 'projectInteract' : 'projectBrowse'
  if (scrollDepth.value >= 0.55) return 'scrollDeep'
  return vistaActiva.value === 'PROYECTOS' ? 'projectBrowse' : 'profileBrowse'
}

const syncViewportMode = () => {
  const mobile = Boolean(viewportMedia?.matches)
  isMobileViewport.value = mobile

  if (mobile) {
    consoleMode.value = 'business'
    consoleCollapsed.value = true
    mobileConsoleOpen.value = false
    mobileDrawerOpen.value = false
    mobileSheetDragY.value = 0
    return
  }

  mobileDrawerOpen.value = false
  mobileConsoleOpen.value = false
  mobileSheetDragY.value = 0
}

const openMobileConsole = () => {
  mobileConsoleOpen.value = true
  mobileSheetDragY.value = 0
}

const closeMobileConsole = () => {
  mobileConsoleOpen.value = false
  mobileSheetDragY.value = 0
}

const onConsoleTouchStart = (event) => {
  if (!isMobileViewport.value) return
  mobileSheetStartY.value = event.touches[0]?.clientY ?? null
}

const onConsoleTouchMove = (event) => {
  if (!isMobileViewport.value || mobileSheetStartY.value == null) return
  const currentY = event.touches[0]?.clientY ?? mobileSheetStartY.value
  mobileSheetDragY.value = Math.max(0, Math.min(180, currentY - mobileSheetStartY.value))
}

const onConsoleTouchEnd = () => {
  if (!isMobileViewport.value) return
  if (mobileSheetDragY.value > 72) {
    closeMobileConsole()
  } else {
    mobileSheetDragY.value = 0
  }
  mobileSheetStartY.value = null
}

watch(adaptiveState, (state) => {
  adaptiveVars.value = state.cssVars
}, { immediate: true })

watch(idleMs, (value) => {
  if (value >= 45000 && !longIdleNotified.value) {
    longIdleNotified.value = true
    pushNarratorMessage('long-idle', t.value.narrator.idleReflection, 'business', 45000)
  }
})

watch(
  () => ({
    key: resolveBehaviorNarrationKey(),
    userMode: userMode.value,
    systemState: systemState.value,
    interactionCount: recentInteractionCount.value,
  }),
  (next, previous) => {
    const previousKey = previous?.key || ''
    let narrationKey = next.key

    if (previousKey === 'idle' && next.key !== 'idle') {
      narrationKey = 'returnFocus'
    }

    if (behavioralNarrationKey.value === narrationKey) return

    behavioralNarrationKey.value = narrationKey
    pushExperimentalLog(`> ${t.value.experimentalPatterns[narrationKey]}`)
  },
  { immediate: true },
)

watch(lang, () => {
  applyEnvironmentState(
    weatherMode.value === 'rain' ? 61 : weatherMode.value === 'cloudy' ? 2 : 0,
    humidityLevel.value,
  )
  pushTechnicalLog('[STORE_MUTATION] locale updated')
})

onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  lastInteractionAt.value = Date.now()
  idleMs.value = 0
  viewportMedia = window.matchMedia('(max-width: 1024px)')
  syncViewportMode()
  if (typeof viewportMedia.addEventListener === 'function') {
    viewportMedia.addEventListener('change', syncViewportMode)
  } else {
    viewportMedia.addListener(syncViewportMode)
  }
  applyEnvironmentState(0, humidityLevel.value)
  easePointer()
  bootPanels()
  updateScrollDepth()
  setupGeolocation()
  fetchWeather()
  measureLatency()
  sensorTimers.push(setInterval(fetchWeather, 10 * 60 * 1000))
  sensorTimers.push(setInterval(measureLatency, 12000))
  sensorTimers.push(setInterval(() => pruneInteractions(), 1500))
  sensorTimers.push(setInterval(() => {
    idleMs.value = Date.now() - lastInteractionAt.value
  }, 1000))
  window.addEventListener('pointerdown', handleViewportInteraction, { passive: true })
  window.addEventListener('wheel', handleViewportInteraction, { passive: true })
  window.addEventListener('keydown', handleViewportInteraction)
  runLogSequence(['[COMPONENT_MOUNT] WorkstationHub mounted', ...t.value.logs.initial], null, t.value.businessLogs.initial)
  const firstVisitTimer = setTimeout(() => {
    pushNarratorMessage('first-visit', t.value.narrator.firstVisit, 'business', 30000)
  }, 900)
  logTimers.push(firstVisitTimer)
})

onBeforeUnmount(() => {
  cancelAnimationFrame(frame)
  clearLogTimers()
  clearSensorTimers()
  window.removeEventListener('pointerdown', handleViewportInteraction)
  window.removeEventListener('wheel', handleViewportInteraction)
  window.removeEventListener('keydown', handleViewportInteraction)
  if (viewportMedia) {
    if (typeof viewportMedia.removeEventListener === 'function') {
      viewportMedia.removeEventListener('change', syncViewportMode)
    } else {
      viewportMedia.removeListener(syncViewportMode)
    }
  }
})
</script>

<template>
  <div class="relative min-h-screen overflow-hidden" :style="styleVars">
    <div class="absolute inset-0 z-0" aria-hidden="true">
      <img
        :src="forestCityBokeh"
        alt=""
        class="weather-bg fixed inset-0 h-full w-full object-cover"
        :style="{ transform: 'translate3d(var(--bgx), var(--bgy), 0)' }"
      />
    </div>

    <div class="weather-tone absolute inset-0 z-10" aria-hidden="true" />
    <div class="weather-atmosphere absolute inset-0 z-[10]" :class="`weather-atmosphere--${weatherMode}`" aria-hidden="true" />
    <div class="weather-clouds absolute inset-0 z-[11]" aria-hidden="true" />
    <div class="weather-rain absolute inset-0 z-[12]" aria-hidden="true" />

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
            class="status-bar metal-panel mb-4 grid gap-3 rounded-xl px-4 py-3 text-[12px] font-medium tracking-[0.08em] text-slate-800 lg:grid-cols-[1.1fr_1fr_1.2fr]"
          >
            <div class="status-loc inline-flex items-center gap-2 font-mono">
              <Waves :size="14" class="text-emerald-600" />
              <span class="status-desktop-copy">LOC: {{ locationLabel }}</span>
              <span class="status-mobile-copy">LOC: {{ compactLocationLabel }}</span>
            </div>

            <div class="status-env space-y-2">
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

            <div class="status-cluster flex items-center justify-between gap-3 lg:justify-end">
              <div class="space-y-2">
                <div class="inline-flex items-center gap-2 font-mono">
                  <Activity :size="14" class="text-emerald-600" />
                  <span class="status-desktop-copy">{{ t.systemLabel }}: {{ t.latency }} {{ latencyMs }}ms | {{ t.optimization }}: {{ optimizationLabel }}</span>
                  <span class="status-mobile-copy">{{ latencyMs }}ms | {{ envLabel }}</span>
                </div>
                <div class="status-meta">
                  <span class="status-chip" :class="statusToneClass(systemState)">
                    <span class="status-chip__label">SYSTEM_STATE</span>
                    <span>{{ systemStateLabel }}</span>
                  </span>
                  <span class="status-chip" :class="statusToneClass(userMode)">
                    <span class="status-chip__label">USER_MODE</span>
                    <span>{{ userModeLabel }}</span>
                  </span>
                </div>
                <div class="status-mobile-state">
                  <span class="status-mobile-state__dot" :class="`is-${statusToneClass(systemState)}`" />
                  <span>{{ mobileStateLabel }}</span>
                </div>
              </div>
              <div class="inline-flex items-center gap-1 rounded-lg border border-slate-300/80 bg-white/70 p-1 status-language">
                <span class="px-1.5 text-[10px] uppercase tracking-[0.12em] text-slate-500">{{ t.language }}</span>
                <button class="lang-btn" :class="{ active: lang === 'es' }" @click="lang = 'es'">{{ t.spanish }}</button>
                <button class="lang-btn" :class="{ active: lang === 'en' }" @click="lang = 'en'">{{ t.english }}</button>
              </div>
            </div>
          </header>

          <div class="mobile-ops-bar" data-panel>
            <button class="mobile-ops-btn" @click="mobileDrawerOpen = true">{{ t.mobileArchitecture }}</button>
            <button class="mobile-ops-btn" @click="openMobileConsole">{{ t.mobileConsole }}</button>
          </div>

          <div class="grid min-h-0 flex-1 grid-cols-12 gap-4 overflow-hidden">
            <main
              ref="mainPanelRef"
              data-panel
              class="metal-panel command-core kernel-scroll relative col-span-12 flex min-h-0 flex-col overflow-x-hidden overflow-y-auto rounded-2xl bg-white/90 p-6 backdrop-blur-xl sm:p-8 lg:col-span-9"
              :class="{ 'system-syncing': syncPulse }"
              @scroll="updateScrollDepth"
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

              <Transition name="district-entry">
                <div v-if="districtEntryOverlay.visible && !isMobileViewport" class="district-entry-overlay">
                  <div class="district-entry-card">
                    <p class="district-entry-label">DISTRICT ENTRY</p>
                    <h3 class="district-entry-title">{{ districtEntryOverlay.name }}</h3>
                    <div class="district-entry-meta">
                      <div>
                        <p class="district-entry-subtitle">{{ t.activeModules }}</p>
                        <p class="district-entry-text">{{ districtEntryOverlay.modules.join(' / ') }}</p>
                      </div>
                      <div>
                        <p class="district-entry-subtitle">{{ t.entryStatus }}</p>
                        <p class="district-entry-text">{{ systemStateLabel }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </Transition>

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
              class="mobile-drawer metal-panel rack-scroll col-span-12 min-h-0 overflow-y-auto rounded-2xl bg-white/40 p-4 backdrop-blur-xl lg:col-span-3"
              :class="{ 'mobile-drawer--open': mobileDrawerOpen }"
            >
              <div class="mobile-drawer-head">
                <p class="font-mono text-[11px] uppercase tracking-[0.14em] text-slate-500">{{ t.mobileArchitecture }}</p>
                <button class="console-collapse" @click="mobileDrawerOpen = false">{{ t.consoleCollapse }}</button>
              </div>
              <p class="mb-3 font-mono text-[12px] uppercase tracking-[0.18em] text-slate-900">{{ panelHeading }}</p>

              <article
                v-for="group in hardSkillGroups"
                :key="group.title"
                class="rack-block mt-3 rounded-lg border bg-slate-100/70 p-3 first:mt-0"
                :class="group.highlight ? 'border-emerald-300/90 ring-1 ring-emerald-200/70' : 'border-slate-300/80'"
              >
                <h3 class="font-mono text-[11px] uppercase tracking-[0.12em] text-slate-900">{{ group.title }}</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span
                    v-for="skill in group.items"
                    :key="skill"
                    class="rack-badge"
                    :class="badgeContextClass(skill)"
                  >
                    <span class="status-dot" />
                    {{ skill }}
                  </span>
                </div>
              </article>

              <article v-if="proyectoActivo" class="rack-block rack-block--district mt-3 rounded-lg border border-emerald-300/80 bg-emerald-50/65 p-3">
                <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-emerald-900/80">
                  {{ lang === 'en' ? 'Active District' : 'Distrito Activo' }}
                </p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ proyectoActivo.name }}</p>
                <p class="mt-2 font-mono text-[10px] uppercase tracking-[0.12em] text-emerald-800/75">{{ t.districtRole }}</p>
                <p class="mt-1 text-sm text-slate-800">{{ districtRoleLabel }}</p>
                <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ proyectoActivo.impact }}</p>
              </article>

              <button class="cv-btn mt-4 w-full">{{ t.downloadCv }}</button>

              <article v-if="!isMobileViewport" class="rack-block mt-3 rounded-lg border border-slate-300/80 bg-slate-100/70 p-3">
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
            class="metal-panel console-shell mt-4 rounded-xl bg-white/88 px-4 py-3 backdrop-blur-xl"
            :class="{ 'console-shell--collapsed': consoleCollapsed, 'mobile-console-sheet': isMobileViewport, 'mobile-console-sheet--open': mobileConsoleOpen }"
            :style="isMobileViewport ? { '--mobile-sheet-drag': `${mobileSheetDragY}px` } : undefined"
            @touchstart.passive="onConsoleTouchStart"
            @touchmove.passive="onConsoleTouchMove"
            @touchend="onConsoleTouchEnd"
          >
            <div class="console-head">
              <div class="console-title-wrap">
                <span class="mobile-sheet-handle" />
                <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ t.activityStream }}</p>
              </div>
              <div class="console-controls">
                <button class="console-toggle" :class="{ active: consoleMode === 'business' }" @click="consoleMode = 'business'">
                  {{ t.consoleBusiness }}
                </button>
                <button v-if="!isMobileViewport" class="console-toggle" :class="{ active: consoleMode === 'experimental' }" @click="consoleMode = 'experimental'">
                  {{ t.consoleExperimental }}
                </button>
                <button class="console-collapse" @click="consoleCollapsed = !consoleCollapsed">
                  {{ consoleCollapsed ? t.consoleExpand : t.consoleCollapse }}
                </button>
                <button v-if="isMobileViewport" class="console-collapse" @click="closeMobileConsole">
                  {{ t.consoleCollapse }}
                </button>
              </div>
            </div>

            <Transition name="console-drop">
              <div v-if="!consoleCollapsed" class="console-body">
                <div class="space-y-1 font-mono text-[12px]" :class="consoleMode === 'business' ? 'text-slate-700' : 'text-slate-600'">
                  <p v-for="(line, index) in activeConsoleStream" :key="`${consoleMode}-${index}-${line}`">{{ line }}</p>
                  <p class="inline-flex items-center gap-1 text-slate-500">{{ consoleAwaitingLabel }} <span class="cursor-block">_</span></p>
                </div>
              </div>
            </Transition>
            <div v-if="consoleCollapsed" class="console-collapsed-hint">
              <span>{{ consoleMode === 'business' ? t.consoleBusiness : t.consoleExperimental }}</span>
            </div>
          </footer>
        </div>
      </section>
    </div>

    <Transition name="mobile-fade">
      <button
        v-if="isMobileViewport && mobileDrawerOpen"
        class="mobile-overlay"
        aria-label="Close stack drawer"
        @click="mobileDrawerOpen = false"
      />
    </Transition>

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
  transform: translate3d(calc(var(--mx) * 0.04), calc(var(--my) * 0.04), 0);
  transition: transform 0.18s ease-out;
}

.weather-bg {
  filter: brightness(var(--env-bg-brightness)) saturate(var(--env-bg-saturate)) contrast(var(--env-bg-contrast)) var(--env-scene-blur);
  transition: filter var(--env-ui-transition) ease, transform 240ms ease-out;
}

.weather-tone {
  background: linear-gradient(to bottom right, var(--env-tone), transparent 72%);
  backdrop-filter: var(--env-scene-blur);
  -webkit-backdrop-filter: var(--env-scene-blur);
  transition: background var(--env-ui-transition) ease, backdrop-filter var(--env-ui-transition) ease, -webkit-backdrop-filter var(--env-ui-transition) ease;
}

.weather-atmosphere {
  pointer-events: none;
  opacity: var(--env-atmosphere-opacity);
  transition: background var(--env-ui-transition) ease, opacity var(--env-ui-transition) ease;
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

.status-mobile-copy,
.mobile-ops-bar,
.status-mobile-state {
  display: none;
}

.status-cluster {
  min-width: 0;
}

.status-meta {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 999px;
  background: color-mix(in srgb, rgba(255, 255, 255, 0.56) 82%, var(--sys-surface-tint) 18%);
  color: #334155;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.08em;
  padding: 5px 9px;
  transition: background-color var(--sys-transition) ease, border-color var(--sys-transition) ease, color var(--sys-transition) ease;
}

.status-chip__label {
  color: #64748b;
}

.status-chip.is-stable {
  border-color: rgba(110, 231, 183, 0.38);
  background: rgba(236, 253, 245, 0.72);
  color: #166534;
}

.status-chip.is-shifting {
  border-color: rgba(148, 163, 184, 0.42);
  background: rgba(248, 250, 252, 0.7);
  color: #334155;
}

.status-chip.is-busy {
  border-color: rgba(125, 211, 252, 0.42);
  background: rgba(240, 249, 255, 0.74);
  color: #0f766e;
}

.status-mobile-state {
  align-items: center;
  gap: 8px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.08em;
  color: #475569;
}

.status-mobile-state__dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.7);
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.36);
}

.status-mobile-state__dot.is-is-stable {
  background: rgba(16, 185, 129, 0.78);
}

.status-mobile-state__dot.is-is-shifting {
  background: rgba(100, 116, 139, 0.72);
}

.status-mobile-state__dot.is-is-busy {
  background: rgba(14, 165, 233, 0.76);
}

.weather-clouds {
  background:
    radial-gradient(1200px 320px at 15% 8%, rgba(148, 163, 184, 0.2), transparent 60%),
    radial-gradient(1100px 280px at 80% 14%, rgba(148, 163, 184, 0.22), transparent 62%);
  opacity: var(--env-cloud-opacity);
  animation: cloudDrift var(--env-cloud-duration) linear infinite alternate;
  animation-play-state: var(--env-cloud-play-state);
  transition: opacity var(--env-ui-transition) ease;
  pointer-events: none;
}

.weather-rain {
  background-image: repeating-linear-gradient(
    -12deg,
    rgba(148, 163, 184, 0.24) 0 2px,
    rgba(148, 163, 184, 0) 2px 16px
  );
  background-size: 100% 140%;
  opacity: var(--env-rain-opacity);
  animation: rainDrop var(--env-rain-duration) linear infinite;
  animation-play-state: var(--env-rain-play-state);
  transition: opacity var(--env-ui-transition) ease;
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
    linear-gradient(
      color-mix(in srgb, rgba(248, 250, 252, 0.9) 88%, var(--sys-surface-tint) 12%),
      color-mix(in srgb, rgba(241, 245, 249, 0.82) 88%, var(--sys-surface-tint) 12%)
    ) padding-box,
    linear-gradient(135deg, rgba(148, 163, 184, 0.95), color-mix(in srgb, rgba(255, 255, 255, 0.96) 84%, var(--sys-accent) 16%)) border-box;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.92),
    0 20px 34px rgba(15, 23, 42, 0.18);
  transition: background var(--sys-transition) ease, box-shadow var(--sys-transition) ease;
}

.command-core {
  backdrop-filter: blur(calc(var(--env-panel-blur-px, 24px) + var(--sys-panel-blur-boost, 0px)));
  -webkit-backdrop-filter: blur(calc(var(--env-panel-blur-px, 24px) + var(--sys-panel-blur-boost, 0px)));
  transition: backdrop-filter var(--sys-transition) ease, -webkit-backdrop-filter var(--sys-transition) ease;
}

.system-syncing::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(110deg, transparent 0%, rgba(94, 234, 212, 0.1) 42%, rgba(255, 255, 255, 0.26) 50%, transparent 58%);
  transform: translateX(-120%);
  animation: syncSweep 1.1s ease;
}

.mobile-drawer {
  transition: transform 320ms ease, opacity 320ms ease;
}

.console-shell {
  overflow: hidden;
}

.console-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.console-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mobile-sheet-handle {
  display: none;
  width: 34px;
  height: 4px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.45);
}

.console-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.console-toggle,
.console-collapse {
  border: 1px solid rgba(148, 163, 184, 0.32);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.62);
  color: #475569;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.08em;
  padding: 6px 10px;
  transition: background-color 220ms ease, border-color 220ms ease, color 220ms ease;
}

.console-toggle.active {
  border-color: var(--sys-accent-strong);
  background: color-mix(in srgb, rgba(236, 253, 245, 0.86) 88%, var(--sys-surface-tint) 12%);
  color: #065f46;
}

.console-body {
  margin-top: 10px;
}

.console-collapsed-hint {
  margin-top: 8px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.08em;
  color: #64748b;
}

.mobile-ops-btn {
  border: 1px solid rgba(148, 163, 184, 0.32);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.66);
  color: #334155;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.08em;
  padding: 7px 12px;
}

.mobile-overlay {
  position: fixed;
  inset: 0;
  z-index: 55;
  border: 0;
  background: rgba(15, 23, 42, 0.18);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.mobile-fade-enter-active,
.mobile-fade-leave-active {
  transition: opacity 240ms ease;
}

.mobile-fade-enter-from,
.mobile-fade-leave-to {
  opacity: 0;
}

.console-drop-enter-active,
.console-drop-leave-active {
  transition: opacity 240ms ease, transform 240ms ease, max-height 240ms ease;
  max-height: 180px;
  overflow: hidden;
}

.console-drop-enter-from,
.console-drop-leave-to {
  opacity: 0;
  transform: translateY(6px);
  max-height: 0;
}

.district-entry-overlay {
  position: absolute;
  inset: 0;
  z-index: 12;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 30%, rgba(16, 185, 129, 0.08), transparent 56%),
    rgba(248, 250, 252, 0.2);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.district-entry-card {
  width: min(680px, 100%);
  border: 1px solid rgba(255, 255, 255, 0.54);
  border-radius: 18px;
  padding: 20px 22px;
  background: rgba(255, 255, 255, 0.4);
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12);
}

.district-entry-label,
.district-entry-subtitle {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.district-entry-label {
  color: rgba(6, 95, 70, 0.8);
}

.district-entry-title {
  margin-top: 10px;
  font-family: Inter, sans-serif;
  font-size: clamp(1.45rem, 2vw, 2rem);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.district-entry-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 16px;
}

.district-entry-subtitle {
  color: rgba(15, 23, 42, 0.54);
}

.district-entry-text {
  margin-top: 6px;
  color: rgba(15, 23, 42, 0.86);
  font-size: 13px;
  line-height: 1.5;
}

.district-entry-enter-active,
.district-entry-leave-active {
  transition: opacity 680ms ease, transform 680ms ease, filter 680ms ease;
}

.district-entry-enter-from,
.district-entry-leave-to {
  opacity: 0;
  transform: scale(0.985);
  filter: blur(6px);
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
    inset 0 0 0 1px rgba(16, 185, 129, 0.42),
    0 0 16px rgba(16, 185, 129, 0.14);
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
  transition: border-color 220ms ease, background-color 220ms ease, box-shadow 220ms ease, color 220ms ease;
}

.rack-badge--active {
  border-color: rgba(16, 185, 129, 0.62);
  background: rgba(236, 253, 245, 0.96);
  color: #065f46;
  box-shadow:
    inset 0 0 0 1px rgba(16, 185, 129, 0.16),
    0 0 0 1px rgba(16, 185, 129, 0.12);
}

.rack-badge--dimmed {
  border-color: rgba(148, 163, 184, 0.38);
  background: rgba(255, 255, 255, 0.44);
  color: rgba(51, 65, 85, 0.62);
}

.rack-badge--dimmed .status-dot {
  opacity: 0.32;
  box-shadow: none;
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
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    0 10px 16px rgba(5, 150, 105, 0.24);
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
  box-shadow: 0 8px 14px rgba(15, 23, 42, 0.1);
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
    0 0 0 1px rgba(16, 185, 129, 0.28),
    0 0 14px rgba(16, 185, 129, 0.18),
    0 10px 16px rgba(15, 23, 42, 0.12);
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

@keyframes syncSweep {
  0% {
    opacity: 0;
    transform: translateX(-120%);
  }

  18% {
    opacity: 1;
  }

  100% {
    opacity: 0;
    transform: translateX(120%);
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
  transition: opacity 0.42s ease, transform 0.42s ease, filter 0.42s ease;
}

.kernel-fade-enter-from,
.kernel-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
  filter: blur(3px);
}

@media (max-width: 1280px) {
  .workstation {
    width: 92vw;
  }
}

@media (max-width: 1024px) {
  .ui-layer {
    transform: none;
    padding: 12px;
  }

  .workstation {
    width: 100vw;
    min-height: 100vh;
    height: auto;
    max-height: none;
    border-radius: 0;
  }

  .status-bar {
    grid-template-columns: 1fr auto;
    align-items: start;
  }

  .status-env {
    display: none;
  }

  .status-cluster {
    align-items: flex-start;
  }

  .status-meta,
  .status-mobile-state,
  .status-language .px-1\.5 {
    display: none;
  }

  .status-desktop-copy {
    display: none;
  }

  .status-mobile-copy {
    display: inline;
  }

  .status-mobile-state {
    display: inline-flex;
    margin-top: 6px;
  }

  .district-entry-meta {
    grid-template-columns: 1fr;
  }

  .mobile-ops-bar {
    display: flex;
    gap: 8px;
    margin: 0 0 10px;
    justify-content: flex-start;
  }

  .mobile-ops-btn {
    padding: 8px 11px;
    background: rgba(255, 255, 255, 0.74);
  }

  .mobile-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 70;
    width: min(84vw, 360px);
    border-radius: 24px 0 0 24px;
    transform: translateX(108%);
    opacity: 0;
    padding-top: 76px;
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.94), rgba(241, 245, 249, 0.86));
    box-shadow: -18px 0 40px rgba(15, 23, 42, 0.18);
  }

  .mobile-drawer--open {
    transform: translateX(0);
    opacity: 1;
  }

  .mobile-drawer-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
  }

  .mobile-console-sheet {
    position: fixed;
    left: 10px;
    right: 10px;
    bottom: 10px;
    z-index: 72;
    margin-top: 0;
    border-radius: 20px;
    transform: translateY(calc(100% - 54px + var(--mobile-sheet-drag, 0px)));
    transition: transform 320ms ease, box-shadow 320ms ease;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
    touch-action: pan-y;
  }

  .console-shell--collapsed.mobile-console-sheet {
    transform: translateY(calc(100% - 48px + var(--mobile-sheet-drag, 0px)));
  }

  .mobile-console-sheet--open {
    transform: translateY(var(--mobile-sheet-drag, 0px));
  }

  .console-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .console-controls {
    gap: 6px;
    width: 100%;
  }

  .console-controls .console-toggle,
  .console-controls .console-collapse {
    padding: 5px 9px;
  }

  .status-cluster {
    flex-wrap: wrap;
  }

  .status-meta {
    justify-content: flex-start;
  }

  .mobile-sheet-handle {
    display: inline-block;
  }
}
</style>
