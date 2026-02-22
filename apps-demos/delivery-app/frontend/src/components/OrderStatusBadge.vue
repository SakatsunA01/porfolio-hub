<script setup lang="ts">
import StatusBadge from './common/StatusBadge.vue'

const props = defineProps<{
  status: 'received' | 'preparing' | 'ready' | 'onroute' | 'delivered' | 'canceled' | 'rejected'
}>()

const labels = {
  received: 'Recibido',
  preparing: 'En preparacion',
  ready: 'Listo',
  onroute: 'En camino',
  delivered: 'Entregado',
  canceled: 'Cancelado',
  rejected: 'Rechazado',
}

</script>

<template>
  <StatusBadge :value="labels[status]">{{ labels[status] }}</StatusBadge>
</template>
