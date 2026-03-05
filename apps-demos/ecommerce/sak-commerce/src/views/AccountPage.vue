<template>
  <section class="bg-bg-primary py-section">
    <div class="mx-auto max-w-container px-6">
      <div class="mb-14">
        <p class="text-sm tracking-wide text-text-secondary">Mi cuenta</p>
        <h1 class="mt-3 font-serif text-4xl tracking-wide text-text-primary">
          {{ displayName }}
        </h1>
      </div>

      <div class="grid gap-16 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
          <div class="mb-8">
            <p class="text-sm tracking-wide text-text-secondary">Mis pedidos</p>
          </div>

          <div class="space-y-6">
            <article
              v-for="order in orders"
              :key="order.id"
              class="rounded-[24px] border border-bg-secondary bg-bg-primary p-6"
            >
              <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-4">
                  <div>
                    <p class="text-sm tracking-wide text-text-secondary">Numero de orden</p>
                    <h2 class="mt-2 font-serif text-2xl tracking-wide text-text-primary">
                      {{ order.order_number }}
                    </h2>
                  </div>

                  <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                      <p class="text-sm tracking-wide text-text-secondary">Fecha</p>
                      <p class="mt-1 text-sm text-text-primary">{{ formatDate(order.created_at) }}</p>
                    </div>
                    <div>
                      <p class="text-sm tracking-wide text-text-secondary">Total</p>
                      <p class="mt-1 text-sm text-text-primary">{{ formatPrice(order.total) }}</p>
                    </div>
                    <div>
                      <p class="text-sm tracking-wide text-text-secondary">Estado</p>
                      <span class="mt-1 inline-flex rounded-[16px] border border-bg-secondary px-3 py-1 text-sm tracking-wide text-text-secondary">
                        {{ orderLabel(order.order_status) }}
                      </span>
                    </div>
                  </div>
                </div>

                <router-link
                  :to="{ name: 'account-order-detail', params: { id: order.id } }"
                  class="w-fit border border-text-primary px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                >
                  Ver detalle
                </router-link>
              </div>
            </article>
          </div>
        </div>

        <div>
          <div class="rounded-[24px] border border-bg-secondary bg-bg-primary p-6">
            <p class="text-sm tracking-wide text-text-secondary">Datos personales</p>
            <form class="mt-8 space-y-6" @submit.prevent="saveProfile">
              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary">Nombre</label>
                <input v-model="profile.name" type="text" class="w-full rounded-[20px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
              </div>
              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary">Email</label>
                <input v-model="profile.email" type="email" class="w-full rounded-[20px] border border-bg-secondary bg-bg-primary px-5 py-4 text-base text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary" />
              </div>

              <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-[20px] bg-text-primary px-6 py-4 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90"
              >
                Guardar cambios
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../composables/useToast'
import { formatPrice } from '../utils/catalog'

type AccountOrder = {
  id: number
  order_number: string
  total: number
  order_status: 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled'
  created_at: string
}

const authStore = useAuthStore()
const { showToast } = useToast()
const orders = ref<AccountOrder[]>([])

const profile = reactive({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
})

watch(
  () => authStore.user,
  (user) => {
    profile.name = user?.name || ''
    profile.email = user?.email || ''
  },
  { immediate: true },
)

const displayName = computed(() => profile.name || 'Mi cuenta')

const formatDate = (value: string) =>
  new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(value))

const orderLabel = (status: AccountOrder['order_status']) => {
  if (status === 'confirmed') return 'Confirmado'
  if (status === 'shipped') return 'Enviado'
  if (status === 'delivered') return 'Entregado'
  if (status === 'cancelled') return 'Cancelado'
  return 'Pendiente'
}

const saveProfile = async () => {
  await authStore.updateProfile({
    name: profile.name,
    email: profile.email,
  })

  showToast('Datos actualizados correctamente.')
}

onMounted(async () => {
  const response = await api.get('/account/orders')
  orders.value = response.data.data
})
</script>
