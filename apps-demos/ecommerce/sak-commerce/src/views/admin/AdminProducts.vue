<template>
  <AdminLayout>
    <div class="space-y-8">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm tracking-wide text-text-secondary">Productos</p>
          <h1 class="mt-2 font-serif text-4xl tracking-wide text-text-primary">
            Catalogo
          </h1>
        </div>

        <button
          type="button"
          class="rounded-[16px] border border-text-primary px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
        >
          Nuevo producto
        </button>
      </div>

      <div class="space-y-4">
        <article
          v-for="product in productItems"
          :key="product.id"
          class="rounded-[16px] border border-bg-secondary bg-bg-primary p-5"
        >
          <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-[16px] bg-bg-secondary">
              <img :src="getProductImageUrl(product.image)" :alt="product.name" class="h-full w-full object-cover" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-serif text-2xl tracking-wide text-text-primary">
                      {{ product.name }}
                    </h2>
                    <span class="rounded-[16px] border border-bg-secondary px-3 py-1 text-sm tracking-wide text-text-secondary">
                      {{ productTypeLabel(product.type) }}
                    </span>
                  </div>
                  <p class="mt-2 text-sm text-text-secondary">
                    {{ product.description }}
                  </p>
                </div>

                <p class="text-lg tracking-wide text-text-primary">
                  {{ formatPrice(product.price) }}
                </p>
              </div>

              <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <button
                  type="button"
                  class="inline-flex w-fit items-center gap-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:text-text-primary"
                  @click="toggleActive(product)"
                >
                  <span
                    class="relative inline-flex h-7 w-12 items-center rounded-full border border-bg-secondary transition duration-200 ease-out"
                    :class="product.is_active ? 'bg-bg-secondary' : 'bg-bg-primary'"
                  >
                    <span
                      class="inline-block h-5 w-5 rounded-full bg-text-primary transition duration-200 ease-out"
                      :class="product.is_active ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </span>
                  <span>{{ product.is_active ? 'Activo' : 'Inactivo' }}</span>
                </button>

                <button
                  type="button"
                  class="rounded-[16px] border border-bg-secondary px-4 py-3 text-sm tracking-wide text-text-primary transition duration-200 ease-out hover:bg-bg-secondary"
                >
                  Editar
                </button>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import AdminLayout from '../../components/admin/AdminLayout.vue'
import api from '../../services/api'
import { useCatalogStore } from '../../stores/catalog'
import type { Product, ProductType } from '../../types/catalog'
import { formatPrice, getProductImageUrl } from '../../utils/catalog'

const catalogStore = useCatalogStore()

const productItems = computed(() => catalogStore.products)

const toggleActive = async (product: Product) => {
  await api.put(`/admin/products/${product.id}`, {
    name: product.name,
    description: product.description_short || product.description,
    price: product.price,
    is_active: !product.is_active,
  })

  const response = await api.get('/admin/products')
  catalogStore.products = response.data.data
  catalogStore.hasLoadedProducts = true
}

const productTypeLabel = (type: ProductType) => {
  if (type === 'limited') {
    return 'Limited'
  }

  if (type === 'preorder') {
    return 'Preorder'
  }

  return 'Permanent'
}

onMounted(async () => {
  const response = await api.get('/admin/products')
  catalogStore.products = response.data.data
  catalogStore.hasLoadedProducts = true
})
</script>
