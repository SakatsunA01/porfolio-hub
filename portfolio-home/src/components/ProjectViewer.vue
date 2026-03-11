<script setup>
import { computed, ref, watch } from 'vue'
import { ExternalLink, X } from 'lucide-vue-next'

const props = defineProps({
  visible: { type: Boolean, default: false },
  project: { type: Object, default: null },
  lang: { type: String, default: 'es' },
})

const emit = defineEmits(['close', 'log'])

const checking = ref(false)
const checkMessage = ref('')
const infoMessage = ref('')
const showContextOverlay = ref(false)

const frameUrl = computed(() => props.project?.demoUrl || '')
const canEmbed = computed(() => props.project?.allowEmbed !== false)
const isEnglish = computed(() => props.lang === 'en')
const shouldRenderIframe = computed(() => Boolean(frameUrl.value) && canEmbed.value)
const impactMetrics = computed(() => props.project?.impactMetrics || [])
const impactSummary = computed(() => props.project?.impactSummary || [])
const technicalDetails = computed(() => props.project?.technicalDetails || [])

const runAvailabilityCheck = async () => {
  if (!props.visible || !frameUrl.value) return

  if (!canEmbed.value) {
    checking.value = false
    checkMessage.value = ''
    infoMessage.value = isEnglish.value
      ? 'This demo opens on its official domain due to production security policies.'
      : 'Esta demo se abre en su dominio oficial por politicas de seguridad del sitio en produccion.'
    emit('log', isEnglish.value ? '> External mode enabled: open official site.' : '> Demo en modo externo: abrir sitio oficial.')
    return
  }

  try {
    const target = new URL(frameUrl.value, window.location.href)
    if (target.origin !== window.location.origin) {
      checkMessage.value = ''
      checking.value = false
      infoMessage.value = isEnglish.value
        ? 'External demo detected. Embedded navigation is enabled for this project.'
        : 'Demo externa detectada. La navegacion embebida esta habilitada para este proyecto.'
      return
    }
  } catch {
    // ignore malformed URL and continue with standard check
  }

  checking.value = true
  checkMessage.value = ''
  infoMessage.value = ''

  try {
    const response = await fetch(frameUrl.value, { method: 'GET' })
    if (!response.ok) {
      checkMessage.value = isEnglish.value
        ? 'Demo returned a non-success status. You can open it in a new tab.'
        : 'La demo respondio con estado no exitoso. Puedes abrirla en una pestaña nueva.'
      emit('log', isEnglish.value
        ? '> Warning: embedded demo responded with an error status.'
        : '> Advertencia: la demo no respondio correctamente en modo embebido.')
    }
  } catch {
    checkMessage.value = isEnglish.value
      ? 'Embedded demo verification failed. Use external access to open it manually.'
      : 'No se pudo verificar la demo embebida. Usa el acceso externo para levantarla manualmente.'
    emit('log', isEnglish.value
      ? '> Error: unable to establish embedded demo link.'
      : '> Error: no se pudo establecer enlace con la demo embebida.')
  } finally {
    checking.value = false
  }
}

watch(
  () => [props.visible, frameUrl.value],
  () => {
    if (!props.visible) {
      showContextOverlay.value = false
    }
    runAvailabilityCheck()
  },
)

const openExternal = () => {
  if (!frameUrl.value) return
  window.open(frameUrl.value, '_blank', 'noopener,noreferrer')
}

const toggleContextOverlay = () => {
  showContextOverlay.value = !showContextOverlay.value
}
</script>

<template>
  <Transition name="viewer-fade">
    <div v-if="visible" class="viewer-overlay fixed inset-0 z-[90] bg-slate-900/45 backdrop-blur-2xl">
      <div class="viewer-shell relative mx-auto flex h-full w-full max-w-[1700px] flex-col p-4 sm:p-6">
        <header class="viewer-header mb-4 flex items-center justify-between rounded-xl border border-slate-200/40 bg-white/18 px-4 py-3 backdrop-blur-xl">
          <div>
            <p class="font-mono text-[11px] uppercase tracking-[0.14em] text-emerald-200">SYNC_STATUS: OK</p>
            <h3 class="font-[Inter] text-lg font-semibold text-white">{{ project?.name || 'Project Viewer' }}</h3>
            <p class="font-mono text-[11px] text-emerald-100/90">{{ isEnglish ? 'FLOW: CONTEXT -> DEMO' : 'FLUJO: CONTEXTO -> DEMO' }}</p>
          </div>

          <div class="flex items-center gap-2">
            <button class="viewer-btn" @click="toggleContextOverlay">
              {{ showContextOverlay ? (isEnglish ? 'Hide context' : 'Ocultar contexto') : (isEnglish ? 'View project context' : 'Ver contexto del proyecto') }}
            </button>
            <button class="viewer-btn" @click="openExternal">
              <ExternalLink :size="14" /> {{ isEnglish ? 'Open External' : 'Abrir Externo' }}
            </button>
            <button class="viewer-btn danger" @click="emit('close')">
              <X :size="14" /> {{ isEnglish ? 'CLOSE SIMULATION' : 'CERRAR SIMULACION' }}
            </button>
          </div>
        </header>

        <div class="viewer-frame-wrap relative min-h-0 flex-1 overflow-hidden rounded-xl border border-slate-200/40 bg-white/10">
          <div class="viewer-content">
            <section class="viewer-block viewer-block--demo">
              <div class="viewer-demo-head">
                <div>
                  <p class="viewer-section-label">{{ isEnglish ? 'DEMO SECTION' : 'SECCION DEMO' }}</p>
                </div>
              </div>
              <div class="viewer-demo-surface">
                <div
                  v-if="!shouldRenderIframe && frameUrl"
                  class="viewer-demo-fallback"
                >
                  <p class="max-w-2xl text-sm text-emerald-50/95">
                    {{ isEnglish ? 'This demo runs on its official environment for compatibility and security.' : 'Esta demo se visualiza en su entorno oficial para mantener compatibilidad y seguridad.' }}
                  </p>
                  <button class="viewer-btn" @click="openExternal">
                    <ExternalLink :size="14" /> {{ isEnglish ? 'Open official demo' : 'Abrir demo oficial' }}
                  </button>
                </div>

                <iframe
                  v-else-if="frameUrl"
                  :src="frameUrl"
                  class="viewer-iframe"
                  title="Project Demo Viewer"
                />

                <div v-else class="viewer-demo-fallback">
                  <p class="max-w-2xl text-sm text-emerald-50/95">
                    {{ isEnglish ? 'Demo not available yet. External access will be enabled when ready.' : 'La demo aun no esta disponible. El acceso externo se habilitara cuando este lista.' }}
                  </p>
                </div>

                <Transition name="tech-expand">
                  <section v-if="showContextOverlay" class="viewer-curtain">
                    <div class="viewer-context-stack">
                      <section class="viewer-block viewer-block--metrics">
                        <div class="viewer-block-head">
                          <p class="viewer-section-label">{{ isEnglish ? 'IMPACT METRICS' : 'METRICAS DE IMPACTO' }}</p>
                          <p class="viewer-summary-title">{{ project?.name }}</p>
                        </div>
                        <div class="viewer-metrics-grid">
                          <article v-for="metric in impactMetrics" :key="`${metric.label}-${metric.value}`" class="viewer-metric-card">
                            <p class="viewer-metric-value">{{ metric.value }}</p>
                            <p class="viewer-metric-label">{{ metric.label }}</p>
                            <p class="viewer-metric-detail">{{ metric.detail }}</p>
                          </article>
                        </div>
                      </section>

                      <section class="viewer-block">
                        <p class="viewer-section-label">{{ isEnglish ? 'BUSINESS VALUE' : 'VALOR DE NEGOCIO' }}</p>
                        <p class="viewer-summary-text viewer-summary-text--lead">{{ project?.impact }}</p>
                        <ul v-if="impactSummary.length" class="viewer-summary-list">
                          <li v-for="(item, index) in impactSummary" :key="`${index}-${item}`" class="viewer-summary-item">
                            {{ item }}
                          </li>
                        </ul>
                      </section>

                      <section class="viewer-block viewer-block--technical">
                        <p class="viewer-section-label">{{ isEnglish ? 'TECHNICAL DETAILS' : 'DETALLES TECNICOS' }}</p>
                        <p class="viewer-summary-title">{{ project?.name }}</p>

                        <div class="viewer-tech-tags">
                          <span v-for="tag in (project?.stack || [])" :key="tag" class="viewer-tech-tag">{{ tag }}</span>
                        </div>

                        <ul v-if="technicalDetails.length" class="viewer-summary-list viewer-summary-list--technical">
                          <li v-for="(item, index) in technicalDetails" :key="`${index}-${item}`" class="viewer-summary-item">
                            {{ item }}
                          </li>
                        </ul>
                        <p v-else class="viewer-summary-text">{{ project?.techSummary || project?.impact }}</p>
                      </section>
                    </div>
                  </section>
                </Transition>
              </div>
            </section>
          </div>

          <div class="scanlines" aria-hidden="true" />

          <div v-if="checking || checkMessage || infoMessage" class="viewer-toast">
            <p v-if="checking">{{ isEnglish ? 'Checking demo link...' : 'Verificando enlace de demo...' }}</p>
            <p v-else-if="checkMessage">{{ checkMessage }}</p>
            <p v-else>{{ infoMessage }}</p>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.viewer-fade-enter-active,
.viewer-fade-leave-active {
  transition: opacity 0.26s ease;
}

.viewer-fade-enter-from,
.viewer-fade-leave-to {
  opacity: 0;
}

.viewer-header {
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.32);
}

.viewer-insight-grid {
  display: contents;
}

.viewer-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  min-height: 0;
  padding: 12px;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.38), rgba(15, 23, 42, 0.12));
}

.viewer-context-stack {
  display: grid;
  gap: 12px;
}

.viewer-block {
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.07);
  padding: 14px 16px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.viewer-block--metrics {
  background: rgba(236, 253, 245, 0.07);
}

.viewer-block-head,
.viewer-demo-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.viewer-metrics-grid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.viewer-metric-card {
  border: 1px solid rgba(110, 231, 183, 0.16);
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.22);
  padding: 12px;
}

.viewer-metric-value {
  font-family: Inter, sans-serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: #f0fdf4;
}

.viewer-metric-label {
  margin-top: 4px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #a7f3d0;
}

.viewer-metric-detail {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.45;
  color: rgba(240, 253, 250, 0.86);
}

.viewer-summary-card {
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.07);
  padding: 14px 16px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.viewer-summary-card--technical {
  background: rgba(15, 23, 42, 0.24);
}

.viewer-section-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.14em;
  color: #a7f3d0;
}

.viewer-summary-title {
  margin-top: 4px;
  font-family: Inter, sans-serif;
  font-size: 15px;
  font-weight: 600;
  color: #f8fafc;
}

.viewer-summary-list {
  margin-top: 10px;
  display: grid;
  gap: 7px;
  padding: 0;
  list-style: none;
}

.viewer-summary-item,
.viewer-summary-text {
  font-size: 13px;
  line-height: 1.5;
  color: rgba(240, 253, 250, 0.92);
}

.viewer-summary-text--lead {
  margin-top: 8px;
  font-size: 14px;
  color: #f8fafc;
}

.viewer-summary-item {
  position: relative;
  padding-left: 12px;
}

.viewer-summary-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: #6ee7b7;
}

.viewer-tech-tags {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.viewer-tech-tag {
  border: 1px solid rgba(110, 231, 183, 0.24);
  border-radius: 999px;
  padding: 4px 8px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  color: #d1fae5;
  background: rgba(16, 185, 129, 0.08);
}

.viewer-demo-text {
  margin-top: 6px;
  max-width: 58ch;
  font-size: 13px;
  line-height: 1.5;
  color: rgba(226, 232, 240, 0.92);
}

.viewer-demo-surface {
  position: relative;
  margin-top: 14px;
  display: flex;
  flex: 1;
  min-height: 0;
  height: 100%;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.34);
}

.viewer-iframe {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 0;
  border: 0;
  background: #0f172a;
}

.viewer-demo-fallback {
  display: flex;
  min-height: 420px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 24px;
  text-align: center;
}

.viewer-curtain {
  position: absolute;
  inset: 0;
  z-index: 5;
  overflow: auto;
  padding: 14px;
  background: linear-gradient(180deg, rgba(2, 6, 23, 0.52), rgba(15, 23, 42, 0.62));
  backdrop-filter: blur(10px) saturate(120%);
  -webkit-backdrop-filter: blur(10px) saturate(120%);
}

.viewer-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #d1fae5;
  border: 1px solid rgba(167, 243, 208, 0.32);
  background: rgba(16, 185, 129, 0.14);
  border-radius: 8px;
  padding: 8px 10px;
}

.viewer-btn:hover {
  background: rgba(16, 185, 129, 0.2);
}

.viewer-btn.danger {
  color: #fee2e2;
  border-color: rgba(252, 165, 165, 0.34);
  background: rgba(127, 29, 29, 0.28);
}

.viewer-btn.danger:hover {
  background: rgba(127, 29, 29, 0.38);
}

.scanlines {
  pointer-events: none;
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    to bottom,
    rgba(255, 255, 255, 0.06) 0px,
    rgba(255, 255, 255, 0.06) 1px,
    rgba(255, 255, 255, 0) 3px,
    rgba(255, 255, 255, 0) 5px
  );
  mix-blend-mode: overlay;
}

.viewer-toast {
  position: absolute;
  bottom: 14px;
  left: 14px;
  max-width: min(480px, calc(100% - 28px));
  border: 1px solid rgba(148, 163, 184, 0.45);
  border-radius: 10px;
  background: rgba(15, 23, 42, 0.66);
  color: #e2e8f0;
  font-family: Inter, sans-serif;
  font-size: 13px;
  padding: 10px 12px;
}

.tech-expand-enter-active,
.tech-expand-leave-active {
  transition: opacity 0.32s ease, transform 0.32s ease;
}

.tech-expand-enter-from,
.tech-expand-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.viewer-block--technical {
  background: rgba(15, 23, 42, 0.22);
}

.viewer-block--demo {
  display: flex;
  flex: 1;
  min-height: 0;
  flex-direction: column;
}

.viewer-summary-list--technical {
  margin-top: 14px;
}

@media (max-width: 960px) {
  .viewer-metrics-grid {
    grid-template-columns: 1fr;
  }

  .viewer-block-head,
  .viewer-demo-head {
    flex-direction: column;
  }
}
</style>
