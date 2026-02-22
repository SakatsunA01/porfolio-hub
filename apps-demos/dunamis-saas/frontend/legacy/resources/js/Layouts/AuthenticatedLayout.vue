<script setup>
import { computed, ref, watch } from 'vue'
import ToastNotification from '@/Components/ToastNotification.vue'
import Sidebar from '@/Components/Sidebar.vue'
import { Link, usePage } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)
const page = usePage()

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

const iconSize = computed(() => '20')
</script>

<template>
    <div class="ui-page">
        <ToastNotification
            v-if="toastVisible"
            :type="toastType"
            :message="toastMessage"
            @close="closeToast"
        />
        <Sidebar :nav-links="navLinks" />

        <div class="min-h-screen md:ml-64">
            <div class="flex items-center justify-end bg-white/70 px-8 py-5 backdrop-blur shadow-soft">
                <div class="flex items-center gap-3 rounded-full border border-surface-border bg-white px-4 py-2 text-sm font-semibold text-text-main shadow-soft">
                    {{ $page.props.auth.user.name }}
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-full border border-surface-border px-3 py-1 text-xs font-semibold text-text-muted hover:bg-canvas transition"
                    >
                        Logout
                    </Link>
                </div>
            </div>

            <header class="bg-canvas" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-8 py-6">
                    <slot name="header" />
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-8 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
