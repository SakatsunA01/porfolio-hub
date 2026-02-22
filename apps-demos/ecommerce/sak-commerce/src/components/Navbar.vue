<template>
  <div class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <TransitionRoot as="template" :show="open">
      <Dialog class="relative z-40 lg:hidden" @close="open = false">
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 z-40 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel class="relative flex w-full max-w-xs flex-col overflow-y-auto bg-white pb-8 shadow-xl">
              <div class="flex px-4 pt-5 pb-2">
                <button
                  type="button"
                  class="relative -m-2 inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                  @click="open = false"
                >
                  <span class="absolute -inset-0.5" />
                  <span class="sr-only">Close menu</span>
                  <XMarkIcon class="size-6" aria-hidden="true" />
                </button>
              </div>

              <div class="mt-3 border-t border-slate-200 px-4 py-5">
                <div class="space-y-2">
                  <router-link
                    v-for="category in navigation.categories"
                    :key="category.name"
                    :to="category.href"
                    class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                    @click="open = false"
                  >
                    {{ category.name }}
                  </router-link>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5">
                  <div v-if="!isAuthenticated" class="grid gap-2">
                    <router-link
                      to="/login"
                      class="rounded-lg bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white transition hover:bg-slate-700"
                      @click="open = false"
                    >
                      Ingresar
                    </router-link>
                    <router-link
                      to="/register"
                      class="rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                      @click="open = false"
                    >
                      Crear cuenta
                    </router-link>
                  </div>

                  <div v-else class="space-y-2">
                    <router-link
                      v-if="isAdmin"
                      to="/admin"
                      class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                      @click="open = false"
                    >
                      Admin Panel
                    </router-link>
                    <button
                      type="button"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                      @click="handleLogout"
                    >
                      Cerrar sesion
                    </button>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <header class="relative">
      <nav aria-label="Top" class="axis-container">
        <div class="flex h-16 items-center">
          <button
            type="button"
            class="relative rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden"
            @click="open = true"
          >
            <span class="absolute -inset-0.5" />
            <span class="sr-only">Open menu</span>
            <Bars3Icon class="size-6" aria-hidden="true" />
          </button>

          <div class="ml-3 flex lg:ml-0">
            <router-link to="/" class="group inline-flex items-center gap-2">
              <img class="h-8 w-auto" src="/alexis_icon.png" alt="Axis Tech Logo" />
              <span class="hidden text-base font-semibold tracking-tight text-slate-900 transition group-hover:text-emerald-600 sm:block">
                Axis Tech
              </span>
            </router-link>
          </div>

          <div class="hidden lg:ml-10 lg:block">
            <div class="flex items-center gap-7">
              <router-link
                v-for="category in navigation.categories"
                :key="category.name"
                :to="category.href"
                class="text-sm font-medium text-slate-600 transition hover:text-slate-900"
              >
                {{ category.name }}
              </router-link>
            </div>
          </div>

          <div class="ml-auto flex items-center gap-3">
            <div class="hidden lg:flex lg:items-center lg:gap-2">
              <template v-if="isAuthenticated">
                <div class="relative">
                  <button
                    type="button"
                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                    @click="userMenuOpen = !userMenuOpen"
                  >
                    {{ user?.name || 'Usuario' }}
                  </button>

                  <Transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                  >
                    <div
                      v-if="userMenuOpen"
                      class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-lg border border-slate-200 bg-white p-1.5 shadow-lg"
                      role="menu"
                    >
                      <router-link
                        v-if="isAdmin"
                        to="/admin"
                        class="block rounded-md px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100"
                        @click="userMenuOpen = false"
                      >
                        Admin Panel
                      </router-link>
                      <button
                        type="button"
                        class="block w-full rounded-md px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100"
                        @click="handleLogout"
                      >
                        Cerrar sesion
                      </button>
                    </div>
                  </Transition>
                </div>
              </template>

              <template v-else>
                <router-link
                  to="/login"
                  class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                  Login
                </router-link>
                <router-link
                  to="/register"
                  class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-emerald-500"
                >
                  Crear cuenta
                </router-link>
              </template>
            </div>

            <a
              href="#"
              class="group inline-flex items-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
            >
              <span class="sr-only">Buscar</span>
              <MagnifyingGlassIcon class="size-5" aria-hidden="true" />
            </a>

            <a href="#" class="group inline-flex items-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
              <ShoppingBagIcon class="size-5 shrink-0" aria-hidden="true" />
              <span class="ml-1.5 text-sm font-medium">0</span>
              <span class="sr-only">items in cart, view bag</span>
            </a>
          </div>
        </div>
      </nav>
    </header>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { Bars3Icon, MagnifyingGlassIcon, ShoppingBagIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const isAuthenticated = computed(() => authStore.isAuthenticated)
const isAdmin = computed(() => authStore.isAdmin)
const user = computed(() => authStore.user)

const navigation = {
  categories: [
    { name: 'Audio', href: '/category/Audio' },
    { name: 'Carga', href: '/category/Carga' },
    { name: 'Accesorios', href: '/category/Accesorios' },
  ],
}

const open = ref(false)
const userMenuOpen = ref(false)

const handleLogout = async () => {
  userMenuOpen.value = false
  open.value = false
  await authStore.logout()
  router.push({ name: 'home' })
}
</script>
