<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <h1>Dunamis SaaS</h1>
      <nav>
        <RouterLink to="/">Home</RouterLink>
        <RouterLink to="/health">Health API</RouterLink>
      </nav>
    </header>

    <main>
      <RouterView />
    </main>
  </div>
</template>