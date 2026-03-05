<template>
  <section class="bg-bg-primary py-section">
    <div class="mx-auto max-w-container px-6">
      <div class="mb-12">
        <router-link to="/account" class="text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary">
          Volver a mi cuenta
        </router-link>
      </div>

      <div v-if="order" class="space-y-12">
        <header class="space-y-4">
          <p class="text-sm tracking-wide text-text-secondary">Detalle de pedido</p>
          <h1 class="font-serif text-4xl tracking-wide text-text-primary">
            {{ order.order_number }}
          </h1>
        </header>

        <section class="grid gap-10 lg:grid-cols-3">
          <div class="space-y-3">
            <p class="text-sm tracking-wide text-text-secondary">Estado pago</p>
            <p class="text-text-primary">{{ paymentLabel(order.payment_status) }}</p>
          </div>
          <div class="space-y-3">
            <p class="text-sm tracking-wide text-text-secondary">Estado envio</p>
            <p class="text-text-primary">{{ orderLabel(order.order_status) }}</p>
          </div>
          <div class="space-y-3">
            <p class="text-sm tracking-wide text-text-secondary">Direccion</p>
            <p class="text-text-primary">{{ order.shipping_address }}</p>
          </div>
        </section>

        <section class="space-y-6">
          <div v-if="hasPreorderItem" class="max-w-2xl text-sm tracking-wide text-text-secondary">
            Esta orden incluye una preventa. El despacho se confirma segun la fecha estimada informada en el producto.
          </div>

          <article
            v-for="item in order.items"
            :key="item.id"
            class="rounded-[24px] border border-bg-secondary bg-bg-primary p-6"
          >
            <div class="flex flex-col gap-5 sm:flex-row">
              <div class="h-20 w-20 shrink-0 overflow-hidden rounded-[16px] bg-bg-secondary">
                <img :src="getProductImageUrl(item.image)" :alt="item.name" class="h-full w-full object-cover" />
              </div>

              <div class="flex-1">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p class="font-serif text-2xl tracking-wide text-text-primary">{{ item.name }}</p>
                    <p v-if="item.size" class="mt-1 text-sm text-text-secondary">{{ item.size }}</p>
                  </div>
                  <p class="text-text-primary">{{ formatPrice(item.subtotal) }}</p>
                </div>

                <p class="mt-4 text-sm text-text-secondary">
                  {{ item.quantity }} x {{ formatPrice(item.price) }}
                </p>
              </div>
            </div>
          </article>
        </section>

        <section class="max-w-md rounded-[24px] border border-bg-secondary bg-bg-primary p-6">
          <p class="text-sm tracking-wide text-text-secondary">Resumen</p>
          <div class="mt-6 flex items-end justify-between gap-4">
            <span class="text-sm tracking-wide text-text-secondary">Total</span>
            <span class="font-serif text-3xl tracking-wide text-text-primary">
              {{ formatPrice(order.total) }}
            </span>
          </div>
        </section>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { formatPrice, getProductImageUrl } from '../utils/catalog'

type AccountOrderDetailItem = {
  id: number
  name: string
  size: string | null
  price: number
  quantity: number
  subtotal: number
  image: string | null
  is_preorder: boolean
}

type AccountOrderDetail = {
  id: number
  order_number: string
  payment_status: 'pending' | 'paid' | 'failed'
  order_status: 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled'
  shipping_address: string
  total: number
  items: AccountOrderDetailItem[]
}

const route = useRoute()
const order = ref<AccountOrderDetail | null>(null)

const hasPreorderItem = computed(() => order.value?.items.some((item) => item.is_preorder) === true)

const paymentLabel = (status: AccountOrderDetail['payment_status']) => {
  if (status === 'paid') return 'Pagado'
  if (status === 'failed') return 'Fallido'
  return 'Pendiente'
}

const orderLabel = (status: AccountOrderDetail['order_status']) => {
  if (status === 'confirmed') return 'Confirmado'
  if (status === 'shipped') return 'Enviado'
  if (status === 'delivered') return 'Entregado'
  if (status === 'cancelled') return 'Cancelado'
  return 'Pendiente'
}

onMounted(async () => {
  const response = await api.get(`/account/orders/${route.params.id}`)
  order.value = response.data.data
})
</script>
