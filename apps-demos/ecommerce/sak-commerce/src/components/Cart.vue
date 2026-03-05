<template>
  <MiniCart
    :open="isCartOpen"
    :items="cartStore.items"
    :total="cartStore.totalPrice"
    @close="closeCart"
    @remove-item="cartStore.removeItem"
    @update-quantity="cartStore.updateQuantity"
    @checkout="goToCheckout"
  />
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import MiniCart from './MiniCart.vue'
import { useCartStore } from '../stores/cart'
import { useCart } from '../composables/useCart'

const router = useRouter()
const cartStore = useCartStore()
const { isCartOpen, closeCart } = useCart()

const goToCheckout = async () => {
  closeCart()
  await router.push({ name: 'checkout' })
}
</script>
