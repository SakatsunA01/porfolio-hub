export type CheckoutStepKey = 'identity' | 'shipping' | 'payment'

export interface CheckoutStepState {
  key: CheckoutStepKey
  label: string
  completed: boolean
  current: boolean
}

export interface ValidationFieldState {
  message: string
  touched: boolean
  valid: boolean
}

export type SearchIntent = 'Audio' | 'Carga' | 'Accesorios' | 'Eco'

export interface FacetState {
  category: string | null
  type: 'permanent' | 'limited' | 'preorder' | null
  inStockOnly: boolean
  priceRange: 'all' | '0-100000' | '100000-250000' | '250000+'
}

export interface SocialProofBlock {
  rating: {
    average: number
    count: number
    updated_at: string
  }
  signals: {
    high_demand: boolean
    orders_last_week: number
    delivery_notice: string
  }
}

export interface CheckoutIdentity {
  mode: 'guest' | 'account'
  name: string
  email: string
  phone: string
}

export interface CheckoutShipping {
  method: 'delivery' | 'pickup'
  address: string
  city: string
  postalCode: string
  addressLine2: string
}

export interface CheckoutPayment {
  method: 'mercado_pago' | 'card'
}
