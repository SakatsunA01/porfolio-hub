import { defineStore } from 'pinia'

const CART_STORAGE_KEY = 'sak-commerce-cart'
const LEGACY_CART_STORAGE_KEY = 'shoppingCart'

type CartProductInput = {
  product_id?: number
  id?: number
  name: string
  price: number
  image?: string | null
}

type CartVariantInput = {
  variant_id?: number | null
  id?: number | null
  size?: string | null
  color?: string | null
}

export type CartItem = {
  id: string
  product_id: number
  variant_id: number | null
  name: string
  color: string | null
  size: string | null
  price: number
  quantity: number
  image: string | null
}

const loadStoredItems = (): CartItem[] => {
  if (typeof window === 'undefined') {
    return []
  }

  try {
    const raw = window.localStorage.getItem(CART_STORAGE_KEY)
    if (raw) {
      return JSON.parse(raw)
    }

    const legacyRaw = window.localStorage.getItem(LEGACY_CART_STORAGE_KEY)

    if (!legacyRaw) {
      return []
    }

    const legacyItems = JSON.parse(legacyRaw) as Array<{
      id: number
      name: string
      price: number
      imageSrc?: string
      variantId: string
      color?: string
      size?: string
      quantity: number
    }>

    const normalizedItems = legacyItems.map((item) => ({
      id: item.variantId,
      product_id: Number(item.id),
      variant_id: null,
      name: item.name,
      color: item.color ?? null,
      size: item.size ?? null,
      price: Number(item.price),
      quantity: Number(item.quantity),
      image: item.imageSrc ?? null,
    }))

    persistItems(normalizedItems)
    window.localStorage.removeItem(LEGACY_CART_STORAGE_KEY)

    return normalizedItems
  } catch {
    return []
  }
}

const persistItems = (items: CartItem[]) => {
  if (typeof window === 'undefined') {
    return
  }

  window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items))
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: loadStoredItems() as CartItem[],
  }),

  getters: {
    totalItems: (state) =>
      state.items.reduce((total, item) => total + item.quantity, 0),

    totalPrice: (state) =>
      state.items.reduce((total, item) => total + item.price * item.quantity, 0),
  },

  actions: {
    addItem(product: CartProductInput, variant?: CartVariantInput | null, quantity = 1) {
      if (quantity <= 0) {
        return
      }

      const productId = Number(product.product_id ?? product.id)
      const variantId = variant?.variant_id ?? variant?.id ?? null
      const sizeKey = variant?.size ?? 'base'
      const colorKey = variant?.color ?? 'default'
      const cartItemId = variantId !== null
        ? `${productId}-${variantId}`
        : `${productId}-${sizeKey}-${colorKey}`
      const existingItem = this.items.find((item) => item.id === cartItemId)

      if (existingItem) {
        existingItem.quantity += quantity
        persistItems(this.items)
        return
      }

      this.items.push({
        id: cartItemId,
        product_id: productId,
        variant_id: variantId,
        name: product.name,
        color: variant?.color ?? null,
        size: variant?.size ?? null,
        price: Number(product.price),
        quantity,
        image: product.image ?? null,
      })

      persistItems(this.items)
    },

    removeItem(id: string) {
      this.items = this.items.filter((item) => item.id !== id)
      persistItems(this.items)
    },

    updateQuantity(id: string, quantity: number) {
      const item = this.items.find((entry) => entry.id === id)

      if (!item) {
        return
      }

      if (quantity <= 0) {
        this.removeItem(id)
        return
      }

      item.quantity = quantity
      persistItems(this.items)
    },

    clearCart() {
      this.items = []
      persistItems(this.items)
    },
  },
})
