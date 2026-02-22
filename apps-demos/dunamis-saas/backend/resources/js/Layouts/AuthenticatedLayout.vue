<script setup>
import { ref, watch } from 'vue'
import ToastNotification from '@/Components/ToastNotification.vue'
import Sidebar from '@/Components/Sidebar.vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const mobileMenuOpen = ref(false)

const navLinks = [
    { label: 'Dashboard', routeName: 'dashboard', icon: 'home' },
    { label: 'POS', routeName: 'sales.create', icon: 'cart' },
    { label: 'Productos', routeName: 'products.index', icon: 'tag' },
    { label: 'Clientes', routeName: 'clients.index', icon: 'users' },
    { label: 'Historial', routeName: 'sales.index', icon: 'clock' },
    { label: 'Reportes', routeName: 'reports.profit', icon: 'chart' },
]

const toastVisible = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toastMessage.value = flash.success
            toastType.value = 'success'
            toastVisible.value = true
        } else if (flash?.error) {
            toastMessage.value = flash.error
            toastType.value = 'error'
            toastVisible.value = true
        }
    },
    { deep: true }
)

const closeToast = () => {
    toastVisible.value = false
}
</script>

<template>
    <div class="ui-page">
        <ToastNotification
            v-if="toastVisible"
            :type="toastType"
            :message="toastMessage"
            @close="closeToast"
        />

        <aside class="fixed left-0 top-0 z-40 hidden h-screen md:flex">
            <Sidebar :nav-links="navLinks" />
        </aside>

        <Transition name="fade">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 z-50 bg-slate-900/45 backdrop-blur-[2px] md:hidden"
                @click="mobileMenuOpen = false"
            />
        </Transition>

        <Transition name="slide">
            <aside v-if="mobileMenuOpen" class="fixed inset-y-0 left-0 z-[60] md:hidden">
                <Sidebar :nav-links="navLinks" mobile @close="mobileMenuOpen = false" />
            </aside>
        </Transition>

        <div class="min-h-screen md:ml-64">
            <div class="sticky top-0 z-30 flex items-center justify-between bg-white/85 px-4 py-3 backdrop-blur shadow-soft sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 md:hidden">
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-control border border-surface-border bg-white text-text-main shadow-sm"
                        @click="mobileMenuOpen = true"
                        aria-label="Abrir menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <p class="text-sm font-semibold text-text-main">Panel Dunamis</p>
                </div>

                <div class="ml-auto flex items-center gap-3 rounded-full border border-surface-border bg-white px-3 py-2 text-sm font-semibold text-text-main shadow-soft sm:px-4">
                    <span class="max-w-[120px] truncate sm:max-w-none">{{ $page.props.auth.user.name }}</span>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-full border border-surface-border px-3 py-2 text-xs font-semibold text-text-muted transition hover:bg-canvas"
                    >
                        Logout
                    </Link>
                </div>
            </div>

            <header class="bg-canvas" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.22s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
}
</style>
