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
const showTechOverlay = ref(false)

const frameUrl = computed(() => props.project?.demoUrl || '')
const canEmbed = computed(() => props.project?.allowEmbed !== false)
const shouldRenderIframe = computed(() => Boolean(frameUrl.value) && canEmbed.value)
const isEnglish = computed(() => props.lang === 'en')

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
        ? 'External demo detected. If it does not load in iframe, use "Open External".'
        : 'Demo externa detectada. Si no carga en iframe, utiliza "Abrir Externo".'
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
      showTechOverlay.value = false
    }
    runAvailabilityCheck()
  },
)

const openExternal = () => {
  if (!frameUrl.value) return
  window.open(frameUrl.value, '_blank', 'noopener,noreferrer')
}

const toggleTechOverlay = () => {
  showTechOverlay.value = !showTechOverlay.value
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
            <p class="font-mono text-[11px] text-emerald-100/90">STREAMS: ACTIVE</p>
          </div>

          <div class="flex items-center gap-2">
            <button class="viewer-btn" @click="toggleTechOverlay">
              {{ showTechOverlay ? (isEnglish ? 'Hide technical sheet' : 'Ocultar ficha tecnica') : (isEnglish ? 'View technical sheet' : 'Ver ficha tecnica') }}
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
          <Transition name="holo-drop">
            <div v-if="showTechOverlay" class="holo-overlay">
              <div class="holo-grid" />
              <div class="holo-content">
                <p class="holo-label">{{ isEnglish ? 'TECHNICAL SHEET' : 'FICHA TECNICA' }}</p>
                <p class="holo-title">{{ project?.name }}</p>

                <p class="holo-sub">{{ isEnglish ? 'Stack' : 'Stack' }}</p>
                <div class="holo-tags">
                  <span v-for="tag in (project?.stack || [])" :key="tag" class="holo-tag">{{ tag }}</span>
                </div>

                <p class="holo-sub">{{ isEnglish ? 'Detail' : 'Detalle' }}</p>
                <ul v-if="project?.technicalDetails?.length" class="holo-list">
                  <li v-for="(item, index) in project.technicalDetails" :key="`${index}-${item}`" class="holo-list-item">
                    {{ item }}
                  </li>
                </ul>
                <p v-else class="holo-text">{{ project?.techSummary || project?.impact }}</p>
              </div>
            </div>
          </Transition>

          <div
            v-if="!shouldRenderIframe && frameUrl"
            class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center"
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
            class="h-full w-full border-0"
            title="Project Demo Viewer"
          />

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

.holo-drop-enter-active,
.holo-drop-leave-active {
  transition: opacity 0.26s ease, transform 0.3s ease;
}

.holo-drop-enter-from,
.holo-drop-leave-to {
  opacity: 0;
  transform: translateY(-16px);
}

.holo-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 15;
  border-bottom: 1px solid rgba(94, 234, 212, 0.4);
  background: linear-gradient(180deg, rgba(8, 47, 73, 0.8), rgba(15, 23, 42, 0.28));
  box-shadow: 0 16px 32px rgba(14, 116, 144, 0.2);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.holo-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(125, 211, 252, 0.12) 1px, transparent 1px),
    linear-gradient(90deg, rgba(125, 211, 252, 0.12) 1px, transparent 1px);
  background-size: 16px 16px;
  opacity: 0.55;
  pointer-events: none;
}

.holo-content {
  position: relative;
  padding: 14px 16px 16px;
  color: #e0f2fe;
}

.holo-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.14em;
  color: #99f6e4;
}

.holo-title {
  font-family: Inter, sans-serif;
  font-size: 16px;
  font-weight: 600;
  margin-top: 2px;
}

.holo-sub {
  margin-top: 10px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.12em;
  color: #a5f3fc;
  text-transform: uppercase;
}

.holo-tags {
  margin-top: 6px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.holo-tag {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  border: 1px solid rgba(165, 243, 252, 0.35);
  border-radius: 999px;
  padding: 3px 8px;
  color: #ecfeff;
  background: rgba(8, 145, 178, 0.18);
}

.holo-text {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
  color: #e2e8f0;
  max-width: 1000px;
}

.holo-list {
  margin-top: 6px;
  display: grid;
  gap: 6px;
  max-width: 1050px;
}

.holo-list-item {
  font-size: 12px;
  line-height: 1.4;
  color: #e2e8f0;
  padding-left: 12px;
  position: relative;
}

.holo-list-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 7px;
  width: 5px;
  height: 5px;
  border-radius: 999px;
  background: #5eead4;
  box-shadow: 0 0 10px rgba(94, 234, 212, 0.5);
}
</style>
