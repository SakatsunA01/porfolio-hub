<script setup>
import { computed } from 'vue'

const props = defineProps({
  telemetry: { type: Object, required: true },
  health: { type: Object, required: true },
  latencySeries: { type: Array, default: () => [] },
  weather: { type: Object, required: true },
  location: { type: Object, required: true },
  permissionsState: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:lang'])

const isEs = computed(() => (props.telemetry?.lang ?? 'es') === 'es')

const labels = computed(() => ({
  language: isEs.value ? 'IDIOMA' : 'LANG',
  more: isEs.value ? 'Mas' : 'More',
  est: isEs.value ? 'est.' : 'est.',
  systemHealth: 'SYSTEM_HEALTH',
  latency: 'LATENCY',
  sync: 'SYNC',
  branch: isEs.value ? 'SUCURSAL' : 'BRANCH',
  loc: 'LOC',
  env: 'ENV',
  temp: 'TEMP',
  hum: 'HUM',
  air: 'AIR*',
  userMode: 'USER_MODE',
  stable: isEs.value ? 'Estable' : 'Stable',
  degraded: isEs.value ? 'Degradado' : 'Degraded',
  down: isEs.value ? 'Caido' : 'Down',
  optimal: isEs.value ? 'Optimo' : 'Optimal',
  justNow: isEs.value ? 'justo ahora' : 'just now',
  raining: isEs.value ? 'Lloviendo' : 'Raining',
  noRain: isEs.value ? 'Sin lluvia' : 'No rain',
  syncHint: isEs.value ? 'Ultima sincronizacion del sistema' : 'Last system sync',
  latencyHint: isEs.value ? 'Medicion contra endpoint real' : 'Measured against a real endpoint',
  envHintLabel: isEs.value ? 'Fuente' : 'Source',
  updated: isEs.value ? 'Actualizado' : 'Updated',
  derivedHint: isEs.value ? 'Derivado de humedad (heuristica).' : 'Derived from humidity (heuristic).',
}))

const humidity = computed(() => {
  const v = Number(props.weather?.humidity ?? 0)
  return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0
})
const temperatureLabel = computed(() => {
  const v = Number(props.weather?.temperatureC)
  return Number.isFinite(v) ? `${v.toFixed(1)}°C` : '-'
})

const healthBadge = computed(() => {
  const s = props.health?.status || 'stable'
  if (s === 'down') {
    return {
      text: labels.value.down,
      dot: 'bg-red-500/80',
      ring: 'shadow-[0_0_0_3px_rgba(239,68,68,.14)]',
      pill: 'bg-white/55 text-zinc-900 ring-1 ring-black/5',
    }
  }
  if (s === 'degraded') {
    return {
      text: labels.value.degraded,
      dot: 'bg-amber-500/85',
      ring: 'shadow-[0_0_0_3px_rgba(245,158,11,.16)]',
      pill: 'bg-white/55 text-zinc-900 ring-1 ring-black/5',
    }
  }
  return {
    text: labels.value.stable,
    dot: 'bg-emerald-500/80',
    ring: 'shadow-[0_0_0_3px_rgba(16,185,129,.14)]',
    pill: 'bg-white/55 text-zinc-900 ring-1 ring-black/5',
  }
})

const cityLabel = computed(() => props.location?.compactLabel || props.location?.label || '-')
const locationHint = computed(() => {
  const geo = props.permissionsState?.geolocation || 'prompt'
  const src = props.location?.source || 'fallback'
  const precision = props.location?.precision || 'n/a'
  const sourceLabel = src === 'gps' ? 'GPS' : src === 'ip' ? 'IP' : 'Fallback'
  return `Perm: ${geo} · Src: ${sourceLabel} · Prec: ${precision}`
})
const envHint = computed(() => {
  const source = props.weather?.source || '-'
  const ago = props.weather?.updatedAgo || labels.value.justNow
  return `${labels.value.envHintLabel}: ${source} · ${labels.value.updated}: ${ago}`
})
const servicesHint = computed(() => {
  const s = props.health?.services || {}
  const parts = Object.entries(s).map(([k, v]) => `${k}:${v}`)
  return parts.length ? parts.join(' · ') : ''
})
const endpointHint = computed(() => {
  const ep = props.telemetry?.endpoint || ''
  if (!ep) return labels.value.latencyHint
  return `${labels.value.latencyHint} · ${ep}`
})

const sparkPath = computed(() => {
  const values = (props.latencySeries || [])
    .slice(-40)
    .map((n) => Number(n))
    .filter((n) => Number.isFinite(n))
  if (values.length < 2) return 'M1 12 L79 12'

  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = Math.max(1, max - min)
  const w = 80
  const h = 16
  const padX = 1
  const padY = 2
  const step = (w - padX * 2) / (values.length - 1)
  const pts = values.map((v, i) => {
    const x = padX + i * step
    const t = (v - min) / range
    const y = (h - padY) - t * (h - padY * 2)
    return [x, y]
  })
  return pts.reduce((acc, [x, y], i) => (i === 0 ? `M${x.toFixed(2)} ${y.toFixed(2)}` : `${acc} L${x.toFixed(2)} ${y.toFixed(2)}`), '')
})

const latestLatency = computed(() => {
  const arr = props.latencySeries || []
  const v = arr[arr.length - 1]
  return Number.isFinite(Number(v)) ? Math.max(1, Math.round(Number(v))) : null
})

const setLang = (next) => emit('update:lang', next)
</script>

<template>
  <div
    class="rounded-2xl border border-white/25 bg-white/55 backdrop-blur-md shadow-[0_20px_60px_rgba(0,0,0,.12)]"
    role="region"
    aria-label="System HUD"
  >
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-2 py-1 hover:bg-white/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70"
        :title="endpointHint"
      >
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.latency }}</span>
        <span class="rounded-full bg-white/55 px-2 py-0.5 text-xs font-semibold text-zinc-900 ring-1 ring-black/5">
          <template v-if="latestLatency !== null">{{ latestLatency }}ms</template>
          <template v-else>-</template>
        </span>
        <span class="ml-1 inline-flex items-center">
          <svg class="h-4 w-20" viewBox="0 0 80 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Latency sparkline">
            <path :d="sparkPath" stroke="rgba(16,185,129,.18)" stroke-width="6" stroke-linecap="round" />
            <path :d="sparkPath" stroke="rgba(16,185,129,.85)" stroke-width="1.6" stroke-linecap="round" />
          </svg>
        </span>
      </button>

      <span class="hidden h-5 w-px bg-black/10 sm:block" aria-hidden="true" />

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-2 py-1 hover:bg-white/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70"
        :title="locationHint"
      >
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.loc }}</span>
        <span class="text-xs font-semibold text-zinc-900">{{ cityLabel }}</span>
      </button>

      <span class="hidden h-5 w-px bg-black/10 sm:block" aria-hidden="true" />

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-2 py-1 hover:bg-white/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70"
        :title="envHint"
      >
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.env }}</span>
        <span class="text-xs font-semibold text-zinc-900">{{ weather.label }}</span>
        <span class="text-[11px] text-zinc-600">· {{ weather.isRaining ? labels.raining : labels.noRain }}</span>
      </button>

      <span class="hidden h-5 w-px bg-black/10 sm:block" aria-hidden="true" />

      <div class="inline-flex items-center gap-2 rounded-xl bg-white/35 px-2 py-1 ring-1 ring-black/5">
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.temp }}</span>
        <span class="text-xs font-semibold text-zinc-900">{{ temperatureLabel }}</span>
      </div>

      <span class="hidden h-5 w-px bg-black/10 sm:block" aria-hidden="true" />

      <div class="inline-flex items-center gap-2 rounded-xl bg-white/35 px-2 py-1 ring-1 ring-black/5">
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.hum }}</span>
        <span class="text-xs font-semibold text-zinc-900">{{ humidity }}%</span>
        <span class="h-2 w-20 overflow-hidden rounded-full bg-black/5" aria-label="Humidity level">
          <span class="block h-full rounded-full bg-emerald-500/60" :style="{ width: `${humidity}%` }" />
        </span>
      </div>

      <span class="hidden h-5 w-px bg-black/10 sm:block" aria-hidden="true" />

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-2 py-1 hover:bg-white/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/70"
        :title="labels.derivedHint"
      >
        <span class="text-[11px] tracking-[0.18em] text-zinc-600">{{ labels.air }}</span>
        <span class="text-xs font-semibold text-zinc-900">{{ labels.optimal }}</span>
        <span class="text-[11px] text-zinc-600">· {{ labels.est }}</span>
      </button>

      <div class="flex-1" />

      <div class="flex items-center gap-2">
        <span class="hidden text-[11px] tracking-[0.18em] text-zinc-600 sm:block">{{ labels.language }}</span>
        <div class="inline-flex rounded-xl bg-white/55 p-1 ring-1 ring-black/5">
          <button
            type="button"
            class="rounded-lg px-2.5 py-1 text-xs font-semibold shadow-[0_1px_0_rgba(0,0,0,.06)]"
            :class="telemetry.lang === 'es' ? 'bg-white/70 text-zinc-900' : 'text-zinc-700 hover:bg-white/45'"
            :aria-pressed="telemetry.lang === 'es'"
            @click="setLang('es')"
          >
            ES
          </button>
          <button
            type="button"
            class="rounded-lg px-2.5 py-1 text-xs font-semibold"
            :class="telemetry.lang === 'en' ? 'bg-white/70 text-zinc-900 shadow-[0_1px_0_rgba(0,0,0,.06)]' : 'text-zinc-700 hover:bg-white/45'"
            :aria-pressed="telemetry.lang === 'en'"
            @click="setLang('en')"
          >
            EN
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
