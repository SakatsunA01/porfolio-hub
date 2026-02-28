<script setup lang="ts">
import ProfileMenu from '../common/ProfileMenu.vue'
</script>

<template>
  <nav class="mb-3 flex items-center justify-end">
    <ProfileMenu :orders-route="'/cliente/pedidos'" :profile-route="'/cliente/perfil'" />
  </nav>
</template>
