import { onBeforeUnmount, onMounted } from 'vue'
import { collection, onSnapshot, orderBy, query } from 'firebase/firestore'
import type { Order, OrderStatus } from '../stores/delivery'
import { useDeliveryStore } from '../stores/delivery'
import { db, hasFirebaseConfig } from '../firebase/config'

let activeListeners = 0
let unsubscribeGlobal: null | (() => void) = null

const normalizeStatus = (status: string): OrderStatus => {
  switch (status) {
    case 'pendiente':
      return 'received'
    case 'preparando':
      return 'preparing'
    case 'listo':
      return 'ready'
    case 'en_camino':
      return 'onroute'
    case 'entregado':
      return 'delivered'
    default:
      return 'received'
  }
}

const normalizeOrder = (raw: Record<string, unknown>): Order | null => {
  const numericFromId = Number(raw.id)
  const fallbackFromId = parseInt(String(raw.id || '').replace(/\D+/g, ''), 10)
  const fallbackFromTime = Number(raw.createdAt || Date.now())
  const id = Number.isFinite(numericFromId) && numericFromId > 0
    ? numericFromId
    : (Number.isFinite(fallbackFromId) && fallbackFromId > 0 ? fallbackFromId : fallbackFromTime)
  if (!id) return null
  const status = normalizeStatus(String(raw.status || 'pendiente'))
  const items = Array.isArray(raw.items) ? raw.items : []
  return {
    id,
    customer: String(raw.customer || 'Cliente'),
    address: String(raw.address || 'Direccion pendiente'),
    items: items
      .map((item) => ({
        productId: Number((item as { productId?: number }).productId || 0),
        qty: Number((item as { qty?: number }).qty || 0),
      }))
      .filter((item) => item.productId > 0 && item.qty > 0),
    status,
    employeeId: raw.employeeId ? Number(raw.employeeId) : null,
    driverId: raw.driverId ? Number(raw.driverId) : null,
    createdAt: Number(raw.createdAt || Date.now()),
    etaMin: Number(raw.etaMin || 0),
  }
}

const startRealtime = (store: ReturnType<typeof useDeliveryStore>) => {
  if (!hasFirebaseConfig || !db || unsubscribeGlobal) return

  const ordersQuery = query(collection(db, 'orders'), orderBy('createdAt', 'desc'))
  unsubscribeGlobal = onSnapshot(
    ordersQuery,
    (snapshot) => {
      const remoteOrders = snapshot.docs
        .map((doc) => normalizeOrder({ id: doc.id, ...doc.data() }))
        .filter(Boolean) as Order[]
      store.setOrdersFromRealtime(remoteOrders)
      store.setRealtimeStatus({ connected: true })
    },
    () => {
      store.setRealtimeStatus({ connected: false, error: 'No se pudo sincronizar en tiempo real.' })
    },
  )
}

const stopRealtime = (store: ReturnType<typeof useDeliveryStore>) => {
  if (activeListeners > 0) return
  if (unsubscribeGlobal) {
    unsubscribeGlobal()
    unsubscribeGlobal = null
  }
  store.setRealtimeStatus({ connected: false })
}

export const useOrdersRealtime = () => {
  const store = useDeliveryStore()

  onMounted(() => {
    activeListeners += 1
    if (!hasFirebaseConfig) {
      store.setRealtimeStatus({ connected: false, error: 'Firebase no configurado para esta demo.' })
      return
    }
    startRealtime(store)
  })

  onBeforeUnmount(() => {
    activeListeners = Math.max(0, activeListeners - 1)
    stopRealtime(store)
  })
}
