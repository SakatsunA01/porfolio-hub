<template>
  <div class="min-h-screen bg-bg-primary text-text-primary">
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
        <TransitionChild
          as="template"
          enter="transition-opacity duration-200 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity duration-200 ease-out"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-text-primary/10 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 flex">
          <TransitionChild
            as="template"
            enter="transform transition duration-200 ease-out"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transform transition duration-200 ease-out"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel class="flex w-[250px] max-w-full flex-col bg-bg-secondary p-5">
              <div class="flex items-center justify-between">
                <p class="font-serif text-lg tracking-wide text-text-primary">Admin</p>
                <button
                  type="button"
                  class="text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
                  @click="sidebarOpen = false"
                >
                  Cerrar
                </button>
              </div>

              <nav class="mt-8 space-y-3">
                <router-link
                  v-for="link in links"
                  :key="link.name"
                  :to="link.to"
                  class="block rounded-[16px] px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                  :class="isActive(link.to) ? 'bg-bg-primary text-text-primary' : 'text-text-secondary hover:bg-bg-primary/60 hover:text-text-primary'"
                  @click="sidebarOpen = false"
                >
                  {{ link.name }}
                </router-link>
              </nav>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <div class="flex min-h-screen">
      <aside class="hidden w-[250px] shrink-0 border-r border-bg-secondary bg-bg-secondary lg:flex lg:flex-col">
        <div class="px-6 py-6">
          <p class="font-serif text-lg tracking-wide text-text-primary">Admin</p>
        </div>

        <nav class="space-y-3 px-4">
          <router-link
            v-for="link in links"
            :key="link.name"
            :to="link.to"
            class="block rounded-[16px] px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
            :class="isActive(link.to) ? 'bg-bg-primary text-text-primary' : 'text-text-secondary hover:bg-bg-primary/60 hover:text-text-primary'"
          >
            {{ link.name }}
          </router-link>
        </nav>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col">
        <header class="border-b border-bg-secondary bg-bg-primary">
          <div class="flex items-center justify-between px-6 py-5">
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="rounded-[16px] border border-bg-secondary px-4 py-2 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary lg:hidden"
                @click="sidebarOpen = true"
              >
                Menú
              </button>
              <p class="text-sm tracking-wide text-text-secondary">
                {{ userName }}
              </p>
            </div>

            <button
              type="button"
              class="rounded-[16px] border border-bg-secondary px-4 py-2 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
              @click="handleLogout"
            >
              Logout
            </button>
          </div>
        </header>

        <main class="flex-1 px-6 py-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

const sidebarOpen = ref(false)

const links = [
  { name: 'Dashboard', to: '/admin/dashboard' },
  { name: 'Productos', to: '/admin/products' },
  { name: 'Órdenes', to: '/admin/orders' },
  { name: 'Configuracion', to: '/admin/settings' },
]

const userName = computed(() => authStore.user?.name || 'Administrador')

const isActive = (target: string) => route.path === target

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>
