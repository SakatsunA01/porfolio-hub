import { computed, ref } from 'vue'
import { useCartStore } from '../stores/cart'
import { useToast } from './useToast'

type VariantOption = {
  name: string
}

type ProductInput = {
  id: number
  name: string
  price: number
  image?: string | null
  images?: string[]
}

const isCartOpen = ref(false)

export function useCart() {
  const cartStore = useCartStore()
  const toast = useToast()

  const cart = computed(() =>
    cartStore.items.map((item) => ({
      id: item.product_id,
      name: item.name,
      price: item.price,
      imageSrc: item.image ?? '',
      imageAlt: item.name,
      variantId: item.id,
      color: item.color ?? 'Natural',
      size: item.size ?? 'Unico',
      quantity: item.quantity,
    })),
  )

  const addToCart = (
    product: ProductInput,
    color: VariantOption = { name: 'Default' },
    size: VariantOption = { name: 'Unico' },
    quantity = 1,
  ) => {
    cartStore.addItem(
      {
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.images?.[0] || product.image || null,
      },
      {
        size: size.name,
        color: color.name,
      },
      quantity,
    )

    toast.success('Producto agregado al carrito')
  }

  const removeFromCart = (variantId: string) => {
    cartStore.removeItem(variantId)
  }

  const updateQuantity = (variantId: string, quantity: number) => {
    cartStore.updateQuantity(variantId, quantity)
  }

  const incrementQuantity = (variantId: string) => {
    const item = cartStore.items.find((entry) => entry.id === variantId)

    if (!item) {
      return
    }

    cartStore.updateQuantity(variantId, item.quantity + 1)
  }

  const decrementQuantity = (variantId: string) => {
    const item = cartStore.items.find((entry) => entry.id === variantId)

    if (!item) {
      return
    }

    cartStore.updateQuantity(variantId, item.quantity - 1)
  }

  const openCart = () => {
    isCartOpen.value = true
  }

  const closeCart = () => {
    isCartOpen.value = false
  }

  const subtotal = computed(() => cartStore.totalPrice)
  const cartCount = computed(() => cartStore.totalItems)

  return {
    cart,
    cartCount,
    isCartOpen,
    addToCart,
    removeFromCart,
    updateQuantity,
    incrementQuantity,
    decrementQuantity,
    openCart,
    closeCart,
    subtotal,
  }
}
