<template>
  <section class="min-h-screen bg-bg-primary py-section-sm">
    <div class="axis-container pb-32 lg:pb-0">
      <header class="mb-10 space-y-4">
        <p class="text-sm tracking-wide text-text-secondary">Checkout</p>
        <h1 class="font-serif text-4xl tracking-wide text-text-primary">Compra sin friccion</h1>

        <ol class="flex flex-wrap gap-3">
          <li
            v-for="step in checkoutStore.steps"
            :key="step.key"
            class="inline-flex items-center gap-2 border px-3 py-2 text-xs tracking-wide"
            :class="step.current ? 'border-text-primary text-text-primary' : 'border-bg-secondary text-text-secondary'"
          >
            <span class="inline-flex h-5 w-5 items-center justify-center border text-[10px]" :class="step.completed ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary'">
              {{ step.completed ? '✓' : step.key === 'identity' ? '1' : step.key === 'shipping' ? '2' : '3' }}
            </span>
            {{ step.label }}
          </li>
        </ol>
      </header>

      <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
        <div class="space-y-8">
          <section v-if="checkoutStore.step === 'identity'" class="space-y-6 border border-bg-secondary bg-bg-secondary/30 p-6">
            <div class="space-y-2">
              <h2 class="font-serif text-2xl tracking-wide text-text-primary">Identidad</h2>
              <p class="text-sm text-text-secondary">Elige modo invitado para continuar en un paso.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.identity.mode === 'guest' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="checkoutStore.identity.mode = 'guest'"
              >
                Continuar como invitado
              </button>
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.identity.mode === 'account' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="checkoutStore.identity.mode = 'account'"
              >
                Tengo cuenta
              </button>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-name">Nombre</label>
                <input
                  id="checkout-name"
                  :ref="(el) => { fieldRefs.name = el as HTMLInputElement | null }"
                  v-model="checkoutStore.identity.name"
                  type="text"
                  autocomplete="name"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('name')"
                  @input="checkoutStore.validateField('name')"
                />
                <p v-if="showError('name')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.name.message }}</p>
              </div>

              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-email">Email</label>
                <input
                  id="checkout-email"
                  :ref="(el) => { fieldRefs.email = el as HTMLInputElement | null }"
                  v-model="checkoutStore.identity.email"
                  type="email"
                  autocomplete="email"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('email')"
                  @input="checkoutStore.validateField('email')"
                />
                <p v-if="showError('email')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.email.message }}</p>
              </div>

              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-phone">Telefono</label>
                <input
                  id="checkout-phone"
                  :ref="(el) => { fieldRefs.phone = el as HTMLInputElement | null }"
                  v-model="checkoutStore.identity.phone"
                  type="tel"
                  autocomplete="tel"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('phone')"
                  @input="checkoutStore.validateField('phone')"
                />
                <p v-if="showError('phone')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.phone.message }}</p>
              </div>
            </div>

            <div class="flex justify-end">
              <BaseButton full-width @click="goToShipping">Continuar</BaseButton>
            </div>
          </section>

          <section v-else-if="checkoutStore.step === 'shipping'" class="space-y-6 border border-bg-secondary bg-bg-secondary/30 p-6">
            <div class="space-y-2">
              <h2 class="font-serif text-2xl tracking-wide text-text-primary">Envio</h2>
              <p class="text-sm text-text-secondary">Usamos validacion de direccion para evitar fallas de entrega.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.shipping.method === 'delivery' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="setShippingMethod('delivery')"
              >
                Envio a domicilio
              </button>
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.shipping.method === 'pickup' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="setShippingMethod('pickup')"
              >
                Retiro en local
              </button>
            </div>

            <div v-if="checkoutStore.shipping.method === 'delivery'" class="grid gap-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-address">Direccion</label>
                <input
                  id="checkout-address"
                  :ref="(el) => { fieldRefs.address = el as HTMLInputElement | null }"
                  v-model="checkoutStore.shipping.address"
                  type="text"
                  autocomplete="street-address"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('address')"
                  @input="checkoutStore.validateField('address')"
                />
                <p v-if="showError('address')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.address.message }}</p>
              </div>

              <div class="md:col-span-2">
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-address2">Piso / depto (opcional)</label>
                <input
                  id="checkout-address2"
                  v-model="checkoutStore.shipping.addressLine2"
                  type="text"
                  autocomplete="address-line2"
                  class="w-full min-h-12 border border-bg-secondary bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-city">Ciudad</label>
                <input
                  id="checkout-city"
                  :ref="(el) => { fieldRefs.city = el as HTMLInputElement | null }"
                  v-model="checkoutStore.shipping.city"
                  type="text"
                  autocomplete="address-level2"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('city')"
                  @input="checkoutStore.validateField('city')"
                />
                <p v-if="showError('city')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.city.message }}</p>
              </div>

              <div>
                <label class="mb-2 block text-sm tracking-wide text-text-secondary" for="checkout-postal">Codigo postal</label>
                <input
                  id="checkout-postal"
                  :ref="(el) => { fieldRefs.postalCode = el as HTMLInputElement | null }"
                  v-model="checkoutStore.shipping.postalCode"
                  type="text"
                  autocomplete="postal-code"
                  class="w-full min-h-12 border bg-bg-primary px-4 py-3 text-sm text-text-primary outline-none transition duration-200 ease-out focus:border-text-secondary"
                  :class="fieldClass('postalCode')"
                  @input="checkoutStore.validateField('postalCode')"
                />
                <p v-if="showError('postalCode')" class="mt-2 text-sm text-state-error">{{ checkoutStore.validation.postalCode.message }}</p>
              </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
              <button type="button" class="min-h-12 border border-bg-secondary px-5 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:border-text-secondary hover:text-text-primary" @click="checkoutStore.setStep('identity')">
                Volver
              </button>
              <BaseButton full-width @click="goToPayment">Continuar al pago</BaseButton>
            </div>
          </section>

          <section v-else class="space-y-6 border border-bg-secondary bg-bg-secondary/30 p-6">
            <div class="space-y-2">
              <h2 class="font-serif text-2xl tracking-wide text-text-primary">Pago</h2>
              <p class="text-sm text-text-secondary">Pago seguro con Mercado Pago. El total final se recalcula en backend.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.payment.method === 'mercado_pago' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="checkoutStore.payment.method = 'mercado_pago'"
              >
                Mercado Pago
              </button>
              <button
                type="button"
                class="min-h-12 border px-4 py-3 text-sm tracking-wide transition duration-200 ease-out"
                :class="checkoutStore.payment.method === 'card' ? 'border-text-primary bg-text-primary text-bg-primary' : 'border-bg-secondary text-text-secondary hover:border-text-secondary hover:text-text-primary'"
                @click="checkoutStore.payment.method = 'card'"
              >
                Tarjeta
              </button>
            </div>

            <div class="rounded-xl border border-bg-secondary bg-bg-primary p-4 text-sm text-text-secondary">
              Confirmacion de cuenta opcional luego de completar la compra.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
              <button type="button" class="min-h-12 border border-bg-secondary px-5 py-3 text-sm tracking-wide text-text-secondary transition duration-200 ease-out hover:border-text-secondary hover:text-text-primary" @click="checkoutStore.setStep('shipping')">
                Volver
              </button>
              <BaseButton full-width @click="submitOrder">Pagar {{ formatPrice(checkoutStore.preview.total || cartStore.totalPrice) }}</BaseButton>
            </div>
          </section>
        </div>

        <aside class="hidden lg:block">
          <div class="sticky top-8 space-y-6 border border-bg-secondary bg-bg-secondary/30 p-6">
            <p class="text-sm tracking-wide text-text-secondary">Resumen</p>

            <ul class="space-y-3 text-sm text-text-secondary">
              <li v-for="item in cartStore.items" :key="item.id" class="flex items-center justify-between gap-4">
                <span class="truncate">{{ item.name }} x{{ item.quantity }}</span>
                <span>{{ formatPrice(item.price * item.quantity) }}</span>
              </li>
            </ul>

            <div class="space-y-2 border-t border-bg-secondary pt-4 text-sm">
              <div class="flex items-center justify-between text-text-secondary">
                <span>Subtotal</span>
                <span>{{ formatPrice(checkoutStore.preview.subtotal || cartStore.totalPrice) }}</span>
              </div>
              <div class="flex items-center justify-between text-text-secondary">
                <span>Envio</span>
                <span>{{ formatPrice(checkoutStore.preview.shipping) }}</span>
              </div>
              <div class="flex items-center justify-between font-serif text-2xl tracking-wide text-text-primary">
                <span>Total</span>
                <span>{{ formatPrice(checkoutStore.preview.total || cartStore.totalPrice) }}</span>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bg-secondary bg-bg-primary/95 px-4 py-4 backdrop-blur-sm lg:hidden">
      <div class="axis-container px-0">
        <div class="mb-3 flex items-center justify-between text-sm text-text-secondary">
          <span>{{ cartStore.totalItems }} productos</span>
          <span>{{ formatPrice(checkoutStore.preview.total || cartStore.totalPrice) }}</span>
        </div>
        <button
          type="button"
          class="inline-flex min-h-12 w-full items-center justify-center bg-text-primary px-6 py-3 text-sm uppercase tracking-widest text-bg-primary transition duration-200 ease-out hover:opacity-90 disabled:opacity-60"
          :disabled="checkoutStore.step !== 'payment' || checkoutStore.isSubmitting"
          @click="submitOrder"
        >
          {{ checkoutStore.isSubmitting ? 'Procesando...' : `Pagar ${formatPrice(checkoutStore.preview.total || cartStore.totalPrice)}` }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../components/BaseButton.vue'
import { useCartStore } from '../stores/cart'
import { useCheckoutStore } from '../stores/checkout'
import { useToast } from '../composables/useToast'

const router = useRouter()
const cartStore = useCartStore()
const checkoutStore = useCheckoutStore()
const { success, error } = useToast()

const fieldRefs = reactive<{
  name: HTMLInputElement | null
  email: HTMLInputElement | null
  phone: HTMLInputElement | null
  address: HTMLInputElement | null
  city: HTMLInputElement | null
  postalCode: HTMLInputElement | null
}>({
  name: null,
  email: null,
  phone: null,
  address: null,
  city: null,
  postalCode: null,
})

const formatPrice = (value: number) =>
  `$${Math.round(Number(value)).toLocaleString('es-AR')}`

const showError = (field: keyof typeof checkoutStore.validation) =>
  checkoutStore.validation[field].touched && !checkoutStore.validation[field].valid

const fieldClass = (field: keyof typeof checkoutStore.validation) =>
  showError(field) ? 'border-state-error' : 'border-bg-secondary'

const scrollToField = async (field: keyof typeof fieldRefs) => {
  await nextTick()
  const input = fieldRefs[field]
  if (!input) {
    return
  }

  input.focus()
  input.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const goToShipping = async () => {
  if (!checkoutStore.validateIdentity()) {
    const first = checkoutStore.getFirstInvalidFieldForCurrentStep()
    if (first) {
      await scrollToField(first)
    }
    return
  }

  checkoutStore.setStep('shipping')
}

const setShippingMethod = (method: 'delivery' | 'pickup') => {
  checkoutStore.shipping.method = method
  checkoutStore.previewOrder()
}

const goToPayment = async () => {
  if (!checkoutStore.validateShipping()) {
    const first = checkoutStore.getFirstInvalidFieldForCurrentStep()
    if (first) {
      await scrollToField(first)
    }
    return
  }

  const isAddressValid = await checkoutStore.validateAddress()
  if (!isAddressValid) {
    error('No pudimos validar la direccion. Revisa ciudad y codigo postal.')
    return
  }

  await checkoutStore.previewOrder()
  checkoutStore.setStep('payment')
}

const submitOrder = async () => {
  if (checkoutStore.step !== 'payment') {
    return
  }

  try {
    const order = await checkoutStore.placeOrder()
    cartStore.clearCart()
    checkoutStore.reset()
    success('Compra confirmada')
    router.push({
      name: 'order-success',
      query: {
        order_number: order.order_number,
      },
    })
  } catch (submitError) {
    console.error(submitError)
    error('No se pudo completar la compra. Revisa los datos e intenta otra vez.')
  }
}

watch(
  () => checkoutStore.shipping.method,
  () => {
    checkoutStore.previewOrder()
  },
)

onMounted(() => {
  if (cartStore.items.length === 0) {
    router.replace({ name: 'cart' })
    return
  }

  checkoutStore.previewOrder()
})
</script>
