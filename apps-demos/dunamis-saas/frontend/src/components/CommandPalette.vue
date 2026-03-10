<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  actions: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'run'])
const query = ref('')

const grouped = computed(() => {
  const q = query.value.trim().toLowerCase()
  const next = {}
  for (const action of props.actions) {
    if (q && !action.label.toLowerCase().includes(q) && !(action.keywords || []).join(' ').toLowerCase().includes(q)) {
      continue
    }
    const group = action.group || 'General'
    if (!next[group]) next[group] = []
    next[group].push(action)
  }
  return next
})

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) query.value = ''
  },
)

const run = (action) => {
  emit('run', action)
  emit('close')
}
</script>

<template>
  <teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-start justify-center bg-black/20 px-4 pb-6 pt-20 backdrop-blur-sm"
      @click.self="$emit('close')"
    >
      <section class="surface w-full max-w-2xl overflow-hidden">
        <div class="border-b px-4 py-3" style="border-color: rgb(var(--border));">
          <input
            v-model="query"
            type="text"
            class="field"
            placeholder="Buscar acciones, modulos o SKU..."
            autofocus
            @keydown.esc="$emit('close')"
          />
        </div>
        <div class="max-h-[65vh] overflow-auto p-2">
          <template v-if="Object.keys(grouped).length">
            <div v-for="(groupActions, group) in grouped" :key="group" class="mb-3">
              <p class="px-2 py-1 text-xs uppercase tracking-widest muted">{{ group }}</p>
              <button
                v-for="action in groupActions"
                :key="action.id"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800"
                @click="run(action)"
              >
                <span>{{ action.label }}</span>
                <span class="text-xs muted">{{ action.hint }}</span>
              </button>
            </div>
          </template>
          <p v-else class="px-2 py-4 text-sm muted">Sin resultados para esta busqueda.</p>
        </div>
      </section>
    </div>
  </teleport>
</template>
