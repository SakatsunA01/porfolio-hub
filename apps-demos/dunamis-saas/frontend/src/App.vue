<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useUiStore } from './stores/ui'
import CommandPalette from './components/CommandPalette.vue'
import ToastStack from './components/ToastStack.vue'

const auth = useAuthStore()
const ui = useUiStore()
const route = useRoute()
const router = useRouter()

const privateRoutes = ['/dashboard', '/products', '/clients', '/sales', '/reports', '/profile']
const navItems = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/products', label: 'Productos' },
  { to: '/clients', label: 'Clientes' },
  { to: '/sales', label: 'Ventas' },
  { to: '/reports', label: 'Reportes' },
  { to: '/profile', label: 'Perfil' },
]

const showAppShell = computed(() => privateRoutes.some((base) => route.path.startsWith(base)))

const commandActions = computed(() => [
  { id: 'go-dashboard', group: 'Navegacion', label: 'Ir a dashboard', hint: 'Alt+1', to: '/dashboard' },
  { id: 'go-products', group: 'Navegacion', label: 'Ir a productos', hint: 'Alt+2', to: '/products' },
  { id: 'go-clients', group: 'Navegacion', label: 'Ir a clientes', hint: 'Alt+3', to: '/clients' },
  { id: 'go-sales', group: 'Navegacion', label: 'Ir a ventas', hint: 'Alt+4', to: '/sales' },
  { id: 'go-reports', group: 'Navegacion', label: 'Ir a reportes', hint: 'Alt+5', to: '/reports' },
  {
    id: 'new-sale',
    group: 'Acciones',
    label: 'Crear venta',
    hint: 'MVP',
    run: () => router.push({ path: '/sales', query: { mode: 'new' } }),
  },
  {
    id: 'search-sku',
    group: 'Acciones',
    label: 'Buscar por SKU',
    hint: 'Productos',
    run: () => router.push({ path: '/products', query: { focus: 'sku' } }),
    keywords: ['sku', 'stock', 'producto'],
  },
  {
    id: 'logout',
    group: 'Sesion',
    label: 'Cerrar sesion',
    hint: 'Confirmacion',
    run: async () => {
      const ok = window.confirm('Esta accion cerrara la sesion actual. Continuar?')
      if (!ok) return
      await auth.logout()
      ui.toast('Sesion cerrada.', 'info')
      await router.push('/login')
    },
  },
])

const applyTheme = () => {
  document.documentElement.classList.toggle('dark', ui.state.theme === 'dark')
}

const handleShortcut = async (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    ui.openCommand()
    return
  }

  if (event.altKey && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
    const map = {
      '1': '/dashboard',
      '2': '/products',
      '3': '/clients',
      '4': '/sales',
      '5': '/reports',
    }
    const next = map[event.key]
    if (next && showAppShell.value) {
      event.preventDefault()
      await router.push(next)
    }
  }
}

const runCommand = async (action) => {
  if (action.run) {
    await action.run()
    return
  }
  if (action.to) {
    await router.push(action.to)
  }
}

const logout = async () => {
  await auth.logout()
  ui.toast('Sesion cerrada.', 'info')
  await router.push('/login')
}

watch(() => ui.state.theme, applyTheme, { immediate: true })

onMounted(() => {
  window.addEventListener('keydown', handleShortcut)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleShortcut)
})
</script>

<template>
  <div class="min-h-screen" :class="showAppShell ? 'grid md:grid-cols-[260px_minmax(0,1fr)]' : ''">
    <aside
      v-if="showAppShell"
      class="border-r px-4 py-5"
      style="border-color: rgb(var(--border)); background: rgb(var(--card));"
    >
      <div class="mb-4">
        <p class="m-0 text-xs uppercase tracking-widest muted">Dunamis SaaS</p>
        <p class="m-0 mt-1 text-sm">{{ auth.state.user?.organization_name || 'Operacion' }}</p>
      </div>
      <nav class="grid gap-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800"
          :class="route.path === item.to ? 'bg-slate-100 dark:bg-slate-800 font-medium' : ''"
        >
          {{ item.label }}
        </RouterLink>
      </nav>
    </aside>

    <div class="min-w-0">
      <header
        v-if="showAppShell"
        class="sticky top-0 z-40 border-b px-3 py-2 backdrop-blur md:px-5"
        style="border-color: rgb(var(--border)); background: rgba(var(--background), 0.88);"
      >
        <div class="flex items-center justify-between gap-2">
          <button type="button" class="btn btn-secondary" @click="ui.openCommand()">
            Buscar / Acciones
            <span class="text-xs muted">Ctrl+K</span>
          </button>
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn btn-secondary" @click="ui.toggleDensity()">
              Densidad: {{ ui.state.density === 'compact' ? 'Compacta' : 'Comfortable' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="ui.toggleTheme()">
              {{ ui.state.theme === 'dark' ? 'Tema claro' : 'Tema oscuro' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="logout">Salir</button>
          </div>
        </div>
      </header>

      <main class="p-3 md:p-5">
        <RouterView />
      </main>
    </div>

    <CommandPalette :open="ui.state.commandOpen" :actions="commandActions" @close="ui.closeCommand()" @run="runCommand" />
    <ToastStack />
  </div>
</template>
