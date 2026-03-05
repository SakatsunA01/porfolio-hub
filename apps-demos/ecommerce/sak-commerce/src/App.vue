<template>
  <div class="min-h-screen bg-bg-primary text-text-primary font-sans">
    <Navbar />
    <AppToast />
    <Cart />
    <main class="pb-16 md:pb-0">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <Footer />
    <MobileBottomNav v-if="featureFlags.mobileBottomNavV2026" />
  </div>
</template>

<script setup lang="ts">
import Navbar from './components/Navbar.vue';
import AppToast from './components/AppToast.vue';
import Cart from './components/Cart.vue';
import Footer from './components/Footer.vue';
import MobileBottomNav from './components/MobileBottomNav.vue';
import { featureFlags } from './config/features';
</script>
