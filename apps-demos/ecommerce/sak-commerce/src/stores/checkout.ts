import { defineStore } from 'pinia'
import api from '../services/api'
import type {
  CheckoutIdentity,
  CheckoutPayment,
  CheckoutShipping,
  CheckoutStepKey,
  CheckoutStepState,
  ValidationFieldState,
} from '../types/commerce'
import { useCartStore } from './cart'

type CheckoutField =
  | 'name'
  | 'email'
  | 'phone'
  | 'address'
  | 'city'
  | 'postalCode'

type OrderPreviewResponse = {
  data: {
    items: Array<{
      product_id: number
      variant_id: number | null
      product_name_snapshot: string
      price_snapshot: number
      quantity: number
      subtotal: number
    }>
    totals: {
      subtotal: number
      shipping: number
      total: number
    }
  }
}

type CreateOrderResponse = {
  data: {
    id: number
    order_number: string
    payment_status: string
    order_status: string
    total: number
  }
}

const buildInitialValidationState = (): Record<CheckoutField, ValidationFieldState> => ({
  name: { message: '', touched: false, valid: false },
  email: { message: '', touched: false, valid: false },
  phone: { message: '', touched: false, valid: false },
  address: { message: '', touched: false, valid: false },
  city: { message: '', touched: false, valid: false },
  postalCode: { message: '', touched: false, valid: false },
})

export const useCheckoutStore = defineStore('checkout', {
  state: () => ({
    step: 'identity' as CheckoutStepKey,
    identity: {
      mode: 'guest',
      name: '',
      email: '',
      phone: '',
    } as CheckoutIdentity,
    shipping: {
      method: 'delivery',
      address: '',
      city: '',
      postalCode: '',
      addressLine2: '',
    } as CheckoutShipping,
    payment: {
      method: 'mercado_pago',
    } as CheckoutPayment,
    validation: buildInitialValidationState(),
    isSubmitting: false,
    isPreviewLoading: false,
    preview: {
      subtotal: 0,
      shipping: 0,
      total: 0,
    },
  }),
  getters: {
    steps(state): CheckoutStepState[] {
      const order: CheckoutStepKey[] = ['identity', 'shipping', 'payment']
      const labels: Record<CheckoutStepKey, string> = {
        identity: 'Identidad',
        shipping: 'Envio',
        payment: 'Pago',
      }
      const currentIndex = order.indexOf(state.step)

      return order.map((key, index) => ({
        key,
        label: labels[key],
        completed: index < currentIndex,
        current: key === state.step,
      }))
    },
    canContinueIdentity(state) {
      return (
        state.validation.name.valid
        && state.validation.email.valid
        && state.validation.phone.valid
      )
    },
    canContinueShipping(state) {
      if (state.shipping.method === 'pickup') {
        return true
      }

      return (
        state.validation.address.valid
        && state.validation.city.valid
        && state.validation.postalCode.valid
      )
    },
  },
  actions: {
    setStep(step: CheckoutStepKey) {
      this.step = step
    },
    validateField(field: CheckoutField) {
      const mark = this.validation[field]
      mark.touched = true

      const validators: Record<CheckoutField, () => string> = {
        name: () => (this.identity.name.trim().length >= 3 ? '' : 'Ingresa un nombre valido.'),
        email: () => (/\S+@\S+\.\S+/.test(this.identity.email.trim()) ? '' : 'Ingresa un email valido.'),
        phone: () => (this.identity.phone.trim().length >= 6 ? '' : 'Ingresa un telefono valido.'),
        address: () => (this.shipping.address.trim().length >= 5 ? '' : 'Ingresa una direccion valida.'),
        city: () => (this.shipping.city.trim().length >= 2 ? '' : 'Ingresa una ciudad valida.'),
        postalCode: () => (this.shipping.postalCode.trim().length >= 4 ? '' : 'Ingresa un codigo postal valido.'),
      }

      const message = validators[field]()
      this.validation[field] = {
        touched: true,
        valid: message === '',
        message,
      }
    },
    validateIdentity() {
      this.validateField('name')
      this.validateField('email')
      this.validateField('phone')
      return this.canContinueIdentity
    },
    validateShipping() {
      if (this.shipping.method === 'pickup') {
        return true
      }

      this.validateField('address')
      this.validateField('city')
      this.validateField('postalCode')
      return this.canContinueShipping
    },
    getFirstInvalidFieldForCurrentStep(): CheckoutField | null {
      if (this.step === 'identity') {
        const fields: CheckoutField[] = ['name', 'email', 'phone']
        return fields.find((field) => !this.validation[field].valid) ?? null
      }

      if (this.step === 'shipping' && this.shipping.method === 'delivery') {
        const fields: CheckoutField[] = ['address', 'city', 'postalCode']
        return fields.find((field) => !this.validation[field].valid) ?? null
      }

      return null
    },
    async previewOrder() {
      const cartStore = useCartStore()
      this.isPreviewLoading = true

      try {
        const response = await api.post<OrderPreviewResponse>('/orders/preview', {
          items: cartStore.items.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
          })),
          shipping_method: this.shipping.method,
        })
        this.preview = response.data.data.totals
      } finally {
        this.isPreviewLoading = false
      }
    },
    async validateAddress() {
      if (this.shipping.method !== 'delivery') {
        return true
      }

      const response = await api.post<{ data: { valid: boolean } }>('/orders/address-validate', {
        address: this.shipping.address,
        city: this.shipping.city,
        postal_code: this.shipping.postalCode,
      })

      return response.data.data.valid
    },
    async placeOrder() {
      const cartStore = useCartStore()
      this.isSubmitting = true

      try {
        const response = await api.post<CreateOrderResponse>('/orders', {
          customer: {
            name: this.identity.name,
            email: this.identity.email,
            phone: this.identity.phone,
          },
          shipping: {
            address: this.shipping.address,
            city: this.shipping.city,
            postal_code: this.shipping.postalCode,
          },
          shipping_method: this.shipping.method,
          items: cartStore.items.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
          })),
        })

        return response.data.data
      } finally {
        this.isSubmitting = false
      }
    },
    reset() {
      this.step = 'identity'
      this.identity = {
        mode: 'guest',
        name: '',
        email: '',
        phone: '',
      }
      this.shipping = {
        method: 'delivery',
        address: '',
        city: '',
        postalCode: '',
        addressLine2: '',
      }
      this.payment = {
        method: 'mercado_pago',
      }
      this.validation = buildInitialValidationState()
      this.preview = {
        subtotal: 0,
        shipping: 0,
        total: 0,
      }
    },
  },
})
