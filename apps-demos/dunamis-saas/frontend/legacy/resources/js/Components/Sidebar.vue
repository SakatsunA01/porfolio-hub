<script setup>
import NavLink from '@/Components/NavLink.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    navLinks: {
        type: Array,
        default: () => [],
    },
})

const iconPaths = {
    home: 'M3 9.75 12 3l9 6.75V21a.75.75 0 0 1-.75.75H3.75A.75.75 0 0 1 3 21z',
    cart: 'M2.25 4.5h1.443a1.5 1.5 0 0 1 1.415 1.037l.303.91M7.5 14.25h9.75a.75.75 0 0 0 .727-.568l1.5-6a.75.75 0 0 0-.728-.932H6.21m1.29 7.5-1.5-6m0 0L5.03 5.785A1.5 1.5 0 0 0 3.614 4.5H2.25M9 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm9 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
    tag: 'M3 6.75 6.75 3l10.42 10.42a2.121 2.121 0 0 1 0 3L15 18.59a2.121 2.121 0 0 1-3 0L3 6.75Zm3.375-.375a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z',
    users: 'M12 12.75a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-7.5 5.25a4.5 4.5 0 0 1 9 0Zm12.75-9a2.25 2.25 0 1 1-2.25-2.25 2.25 2.25 0 0 1 2.25 2.25Zm-2.7 9.54a4.5 4.5 0 0 1 2.7-4.29',
    clock: 'M12 6.75v4.5l3 1.5m6-1.5a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    chart: 'M3.75 4.5a.75.75 0 0 0-.75.75v13.5a.75.75 0 0 0 .75.75h16.5a.75.75 0 0 0 .75-.75V5.25a.75.75 0 0 0-.75-.75Zm.75 1.5h15v12h-15ZM8.25 16.5v-4.5a.75.75 0 0 1 1.5 0v4.5Zm3.75 0v-6a.75.75 0 0 1 1.5 0v6Zm3.75 0v-9a.75.75 0 1 1 1.5 0v9Z',
}
</script>

<template>
    <aside class="fixed left-0 top-0 z-40 flex h-screen w-64 flex-col border-r border-surface-border bg-brand-navy text-white shadow-soft">
        <div class="flex items-center justify-between px-5 py-5">
            <Link :href="route('dashboard')" class="text-2xl font-bold text-white">
                Dunamis
            </Link>
        </div>
        <nav class="flex-1 space-y-1 px-4 py-2">
            <NavLink
                v-for="link in navLinks"
                :key="link.routeName"
                :href="route(link.routeName)"
                :active="route().current(link.routeName)"
            >
                <svg
                    v-if="iconPaths[link.icon]"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="h-5 w-5 text-white/80"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path :d="iconPaths[link.icon]" />
                </svg>
                <span>{{ link.label }}</span>
            </NavLink>
        </nav>
    </aside>
</template>
