import { ref, computed, watch } from 'vue'

// Load cart from localStorage on startup
const savedCart = localStorage.getItem('shoppingCart')
const cart = ref(savedCart ? JSON.parse(savedCart) : [])

const isCartOpen = ref(false)

// Watch for changes in the cart and save to localStorage
watch(cart, (newCart) => {
  localStorage.setItem('shoppingCart', JSON.stringify(newCart))
}, { deep: true })

export function useCart() {
  const addToCart = (product, color, size, quantity = 1) => {
    const variantId = `${product.id}-${color.name}-${size.name}`
    const existingProduct = cart.value.find(item => item.variantId === variantId)

    if (existingProduct) {
      existingProduct.quantity += quantity
    } else {
      cart.value.push({ 
        id: product.id,
        name: product.name,
        price: product.price,
        imageSrc: product.images[0].src, // Assuming the first image is the main one
        imageAlt: product.images[0].alt, // Assuming the first image is the main one
        variantId,
        color: color.name, 
        size: size.name, 
        quantity 
      })
    }
  }

  const removeFromCart = (variantId) => {
    cart.value = cart.value.filter(item => item.variantId !== variantId)
  }

  const openCart = () => {
    isCartOpen.value = true
  }

  const closeCart = () => {
    isCartOpen.value = false
  }

  const subtotal = computed(() => {
    return cart.value.reduce((total, item) => {
      const price = Number(item.price.replace(/[^0-9.-]+/g, ""));
      return total + price * item.quantity
    }, 0)
  })

  return {
    cart,
    isCartOpen,
    addToCart,
    removeFromCart,
    openCart,
    closeCart,
    subtotal,
  }
}
