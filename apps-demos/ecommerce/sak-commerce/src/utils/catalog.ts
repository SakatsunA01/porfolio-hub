import type { Product } from '../types/catalog'

export const formatPrice = (value: number) =>
  `$${Math.round(Number(value)).toLocaleString('es-AR')}`

export const getProductImageUrl = (path?: string | null) => {
  if (!path) {
    return ''
  }

  if (/^(https?:)?\/\//.test(path)) {
    return path
  }

  return new URL(path, import.meta.url).href
}

export const formatPreorderDate = (value?: string | null) => {
  if (!value) {
    return 'A confirmar'
  }

  const date = new Date(`${value}T00:00:00`)

  return new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
  }).format(date)
}

export const getAvailabilityLabel = (product: Product) => {
  if (product.type === 'limited') {
    return `Serie limitada · ${product.stock} unidades disponibles`
  }

  if (product.type === 'preorder') {
    return `Preventa · Envío estimado: ${formatPreorderDate(product.preorder_shipping_date)}`
  }

  return `Disponible en ${product.stock} unidades`
}
