<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { History, Route, User } from 'lucide-vue-next'
import ProfileMenu from '../common/ProfileMenu.vue'

const route = useRoute()

const tabs = [
  { to: '/repartidor/ruta', label: 'Ruta de entregas', icon: Route },
  { to: '/repartidor/historial', label: 'Pedidos entregados', icon: History },
  { to: '/repartidor/perfil', label: 'Mi perfil', icon: User },
]

const activePath = computed(() => route.path)
</script>

<template>
  <nav class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 bg-white p-2">
    <div class="flex flex-wrap gap-2">
      <RouterLink
        v-for="tab in tabs"
        :key="tab.to"
        :to="tab.to"
        class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
        :class="
          activePath === tab.to
            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 shadow-sm'
            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-800'
        "
      >
        <component :is="tab.icon" class="h-3.5 w-3.5" />
        {{ tab.label }}
      </RouterLink>
    </div>
    <ProfileMenu :profile-route="'/repartidor/perfil'" />
  </nav>
</template>
