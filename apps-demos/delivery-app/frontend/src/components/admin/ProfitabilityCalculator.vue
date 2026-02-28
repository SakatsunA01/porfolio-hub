<script setup lang="ts">
import { computed, ref } from 'vue'
import { Trash2 } from 'lucide-vue-next'

type IngredientOption = {
  id: number
  name: string
  unit: string
  purchasePrice: number
}

type RecipeLine = {
  ingredientId: number
  qty: number
}

const props = defineProps<{
  salePrice: number
  ingredients: IngredientOption[]
  lines: RecipeLine[]
}>()

const emit = defineEmits<{
  'update:salePrice': [value: number]
  'add-line': [payload: { ingredientId: number; qty: number }]
  'remove-line': [ingredientId: number]
}>()

const search = ref('')
const draftIngredientId = ref(0)
const draftQty = ref(1)

const filteredIngredients = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return props.ingredients
  return props.ingredients.filter((item) => item.name.toLowerCase().includes(query))
})

const lineRows = computed(() =>
  props.lines.map((line) => {
    const ingredient = props.ingredients.find((item) => item.id === line.ingredientId)
    const purchasePrice = Number(ingredient?.purchasePrice || 0)
    return {
      ingredientId: line.ingredientId,
      name: ingredient?.name || `Insumo #${line.ingredientId}`,
      unit: ingredient?.unit || 'unidad',
      qty: Number(line.qty || 0),
      subtotal: purchasePrice * Number(line.qty || 0),
    }
  }),
)

const totalCost = computed(() => lineRows.value.reduce((acc, row) => acc + row.subtotal, 0))
const profit = computed(() => Number(props.salePrice || 0) - totalCost.value)
const margin = computed(() => {
  const sale = Number(props.salePrice || 0)
  if (!sale) return 0
  return (profit.value / sale) * 100
})

const marginTone = computed(() => {
  if (margin.value > 40) return 'green'
  if (margin.value >= 20) return 'orange'
  return 'red'
})

const marginTextClass = computed(() => {
  if (marginTone.value === 'green') return 'text-emerald-700'
  if (marginTone.value === 'orange') return 'text-amber-700'
  return 'text-rose-700'
})

const progressClass = computed(() => {
  if (marginTone.value === 'green') return 'bg-emerald-500'
  if (marginTone.value === 'orange') return 'bg-amber-500'
  return 'bg-rose-500'
})

const progressWidth = computed(() => `${Math.max(4, Math.min(100, margin.value))}%`)

const submitLine = () => {
  const ingredientId = Number(draftIngredientId.value || 0)
  const qty = Number(draftQty.value || 0)
  if (!ingredientId || qty <= 0) return
  emit('add-line', { ingredientId, qty })
  draftIngredientId.value = 0
  draftQty.value = 1
}

const removeLine = (ingredientId: number) => {
  emit('remove-line', ingredientId)
}

const updateSalePrice = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:salePrice', Number(target.value || 0))
}

const qtyLabel = (qty: number) => (Number.isInteger(qty) ? `${qty}` : qty.toFixed(2))
</script>

<template>
  <section class="rounded-2xl bg-[#F9FAFB] p-4 font-['Inter',sans-serif]">
    <div class="grid gap-3 md:grid-cols-3">
      <article class="rounded-2xl bg-white p-3">
        <p class="text-[11px] uppercase tracking-wide text-slate-500">Venta</p>
        <input
          :value="salePrice"
          class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-lg font-bold text-slate-900"
          type="number"
          min="0"
          step="0.01"
          @input="updateSalePrice"
        />
      </article>
      <article class="rounded-2xl bg-white p-3">
        <p class="text-[11px] uppercase tracking-wide text-slate-500">Costo</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">${{ totalCost.toFixed(2) }}</p>
      </article>
      <article class="rounded-2xl bg-white p-3">
        <p class="text-[11px] uppercase tracking-wide text-slate-500">Ganancia</p>
        <p class="mt-2 text-2xl font-bold" :class="profit >= 0 ? 'text-emerald-700' : 'text-rose-700'">${{ profit.toFixed(2) }}</p>
      </article>
    </div>

    <div class="mt-3 rounded-2xl bg-white p-3">
      <div class="flex items-center justify-between gap-2">
        <p class="text-xs text-slate-500">Margen de rentabilidad</p>
        <p class="text-sm font-bold" :class="marginTextClass">{{ margin.toFixed(1) }}%</p>
      </div>
      <div class="mt-2 h-2 rounded-full bg-slate-200">
        <div class="h-2 rounded-full transition-all" :class="progressClass" :style="{ width: progressWidth }"></div>
      </div>
    </div>

    <div class="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_110px_auto]">
      <input v-model="search" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm" type="text" placeholder="Buscar insumo..." />
      <select v-model.number="draftIngredientId" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm">
        <option :value="0">Seleccionar insumo</option>
        <option v-for="ingredient in filteredIngredients" :key="`calc-ingredient-${ingredient.id}`" :value="ingredient.id">
          {{ ingredient.name }} (${{ Number(ingredient.purchasePrice || 0).toFixed(2) }})
        </option>
      </select>
      <input v-model.number="draftQty" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm" type="number" min="0.1" step="0.1" placeholder="Cant." />
      <button type="button" class="rounded-2xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" @click="submitLine">Agregar</button>
    </div>

    <div class="mt-3 space-y-2">
      <article v-for="line in lineRows" :key="`line-${line.ingredientId}`" class="rounded-2xl bg-white px-3 py-2">
        <div class="flex items-center justify-between gap-2">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">{{ line.name }}</p>
            <p class="text-xs text-slate-500">{{ qtyLabel(line.qty) }} {{ line.unit }}</p>
          </div>
          <div class="flex items-center gap-2">
            <p class="text-sm font-bold text-slate-900">${{ line.subtotal.toFixed(2) }}</p>
            <button type="button" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-rose-100 hover:text-rose-700" @click="removeLine(line.ingredientId)">
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
