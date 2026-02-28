<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Boxes, ClipboardList, History, Home, Package, Percent, Shield, Tag, User, Users, Wallet } from 'lucide-vue-next'

const route = useRoute()

const tabs = [
  { to: '/admin/home', label: 'Inicio del panel', icon: Home },
  { to: '/admin/orders', label: 'Gestion de pedidos', icon: ClipboardList },
  { to: '/admin/products', label: 'Productos', icon: Package },
  { to: '/admin/inventory', label: 'Inventario', icon: Boxes },
  { to: '/admin/categories', label: 'Categorias del menu', icon: Tag },
  { to: '/admin/combos', label: 'Ofertas y combos', icon: Percent },
  { to: '/admin/cashbox', label: 'Caja y cobranzas', icon: Wallet },
  { to: '/admin/team', label: 'Equipo y usuarios', icon: Users },
  { to: '/admin/roles', label: 'Roles', icon: Shield },
  { to: '/admin/customers', label: 'Clientes', icon: User },
  { to: '/admin/audit', label: 'Auditoria y trazabilidad', icon: History },
]

const activePath = computed(() => route.path)
</script>

<template>
  <nav class="mb-3 flex flex-wrap gap-2 rounded-xl border border-slate-100 bg-white p-2">
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
  </nav>
</template>
