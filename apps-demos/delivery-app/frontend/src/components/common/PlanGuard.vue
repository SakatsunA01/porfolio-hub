<script setup lang="ts">
import { computed } from 'vue'
import { Lock } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    plan: 'takeaway' | 'full' | 'bi'
    currentPlan: 'takeaway' | 'full' | 'bi'
    title?: string
    ctaLabel?: string
  }>(),
  {
    title: 'Funcion disponible en plan superior',
    ctaLabel: 'Desbloquea esta funcion',
  },
)

const emit = defineEmits<{
  upgrade: [requiredPlan: 'takeaway' | 'full' | 'bi']
}>()

const planRankMap: Record<'takeaway' | 'full' | 'bi', number> = {
  takeaway: 1,
  full: 2,
  bi: 3,
}

const locked = computed(() => planRankMap[props.currentPlan] < planRankMap[props.plan])
</script>

<template>
  <div class="plan-guard rounded-[24px]">
    <slot />
    <div v-if="locked" class="plan-guard-overlay">
      <div class="plan-guard-card">
        <span class="plan-guard-lock">
          <Lock class="h-4 w-4" />
        </span>
        <p class="text-sm font-semibold text-slate-900">{{ title }}</p>
        <button
          type="button"
          class="mt-2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]"
          @click="emit('upgrade', plan)"
        >
          {{ ctaLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plan-guard {
  position: relative;
  overflow: hidden;
}

.plan-guard-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  backdrop-filter: blur(5px);
  background: rgb(248 250 252 / 0.62);
}

.plan-guard-card {
  display: grid;
  place-items: center;
  border-radius: 16px;
  border: 1px solid rgb(203 213 225);
  background: rgb(255 255 255 / 0.9);
  padding: 14px 16px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.plan-guard-lock {
  margin-bottom: 8px;
  display: inline-flex;
  height: 30px;
  width: 30px;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background: rgb(254 243 199);
  color: rgb(217 119 6);
}
</style>
