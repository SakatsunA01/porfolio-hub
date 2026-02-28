import { computed } from 'vue'
import { useDeliveryStore } from '../stores/delivery'

const terminalStatuses = new Set(['delivered', 'canceled', 'rejected'])

const statusLabelMap: Record<string, string> = {
  received: 'Recibido',
  preparing: 'En cocina',
  ready: 'Listo',
  onroute: 'En camino',
  delivered: 'Entregado',
  canceled: 'Cancelado',
  rejected: 'Rechazado',
}

const statusSteps = [
  { key: 'received', label: 'Recibido' },
  { key: 'preparing', label: 'En cocina' },
  { key: 'ready', label: 'Listo' },
  { key: 'onroute', label: 'En camino' },
  { key: 'delivered', label: 'Entregado' },
] as const

const statusIndexMap: Record<string, number> = {
  received: 0,
  preparing: 1,
  ready: 2,
  onroute: 3,
  delivered: 4,
  canceled: 0,
  rejected: 0,
}

export const useClientOrders = () => {
  const store = useDeliveryStore()

  const normalizedCustomer = computed(() => (store.currentUser?.name || '').trim().toLowerCase())
  const clientOrders = computed(() => {
    if (!normalizedCustomer.value) return []
    return store.sortedOrders.filter((order) => order.customer.toLowerCase() === normalizedCustomer.value)
  })
  const activeClientOrders = computed(() => clientOrders.value.filter((order) => !terminalStatuses.has(order.status)))
  const deliveredClientOrdersCount = computed(() => clientOrders.value.filter((order) => order.status === 'delivered').length)
  const completedClientOrdersCount = computed(() => clientOrders.value.filter((order) => terminalStatuses.has(order.status)).length)

  const orderStatusIndex = (status: string) => statusIndexMap[status] ?? -1

  return {
    clientOrders,
    activeClientOrders,
    deliveredClientOrdersCount,
    completedClientOrdersCount,
    statusLabelMap,
    statusSteps,
    orderStatusIndex,
  }
}
