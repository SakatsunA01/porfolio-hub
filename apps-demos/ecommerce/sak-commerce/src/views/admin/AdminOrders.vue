<template>
  <AdminLayout>
    <div class="space-y-8">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm tracking-wide text-text-secondary">Ordenes</p>
          <h1 class="mt-2 font-serif text-4xl tracking-wide text-text-primary">
            Seguimiento de ventas
          </h1>
        </div>

        <div class="w-full sm:w-64">
          <label class="mb-2 block text-sm tracking-wide text-text-secondary">
            Filtrar por estado
          </label>
          <select
            v-model="selectedStatus"
            class="w-full rounded-[16px] border border-bg-secondary bg-bg-primary px-4 py-3 text-sm tracking-wide text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
          >
            <option value="all">Todos</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div class="space-y-4">
        <article
          v-for="order in filteredOrders"
          :key="order.id"
          class="rounded-[16px] border border-bg-secondary bg-bg-primary p-5"
        >
          <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-4">
              <div>
                <p class="text-sm tracking-wide text-text-secondary">Order number</p>
                <h2 class="mt-2 font-serif text-2xl tracking-wide text-text-primary">
                  {{ order.order_number }}
                </h2>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <p class="text-sm tracking-wide text-text-secondary">Cliente</p>
                  <p class="mt-1 text-sm text-text-primary">{{ order.user_name }}</p>
                </div>
                <div>
                  <p class="text-sm tracking-wide text-text-secondary">Total</p>
                  <p class="mt-1 text-sm text-text-primary">{{ formatPrice(order.total) }}</p>
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:flex-col lg:items-end">
              <span class="rounded-[16px] border border-bg-secondary px-3 py-2 text-sm tracking-wide text-text-secondary">
                Pago: {{ paymentLabel(order.payment_status) }}
              </span>
              <span class="rounded-[16px] border border-bg-secondary px-3 py-2 text-sm tracking-wide text-text-secondary">
                Estado: {{ orderLabel(order.order_status) }}
              </span>
            </div>
          </div>
        </article>

        <div
          v-if="!isLoading && filteredOrders.length === 0"
          class="rounded-[16px] border border-bg-secondary bg-bg-primary p-6 text-sm text-text-secondary"
        >
          No hay ordenes para este filtro.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '../../components/admin/AdminLayout.vue'
import api from '../../services/api'
import { formatPrice } from '../../utils/catalog'

type PaymentStatus = 'pending' | 'paid' | 'failed'
type OrderStatus = 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled'

type AdminOrder = {
  id: number
  order_number: string
  user_name: string
  total: number
  payment_status: PaymentStatus
  order_status: OrderStatus
}

const isLoading = ref(true)
const selectedStatus = ref<'all' | OrderStatus>('all')
const orders = ref<AdminOrder[]>([])

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return orders.value
  }

  return orders.value.filter((order) => order.order_status === selectedStatus.value)
})

const paymentLabel = (status: PaymentStatus) => {
  if (status === 'paid') return 'Paid'
  if (status === 'failed') return 'Failed'
  return 'Pending'
}

const orderLabel = (status: OrderStatus) => {
  if (status === 'confirmed') return 'Confirmed'
  if (status === 'shipped') return 'Shipped'
  if (status === 'delivered') return 'Delivered'
  if (status === 'cancelled') return 'Cancelled'
  return 'Pending'
}

onMounted(async () => {
  try {
    const response = await api.get('/admin/orders')
    orders.value = response.data.data
  } finally {
    isLoading.value = false
  }
})
</script>
