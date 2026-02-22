<template>
  <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
    <div class="sm:flex sm:items-start">
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
          {{ product ? 'Edit Product' : 'Add New Product' }}
        </h3>
        <form @submit.prevent="saveProduct" class="mt-5 space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" id="name" v-model="form.name" required
                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
          </div>
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" name="description" v-model="form.description" required
                      class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
          </div>
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
            <input type="number" name="price" id="price" v-model="form.price" required step="0.01"
                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
              {{ product ? 'Update' : 'Add' }}
            </button>
            <button type="button" @click="$emit('cancel')"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../services/api';

const props = defineProps({
  product: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['product-saved', 'cancel']);

const form = ref({
  name: '',
  description: '',
  price: 0,
});

watch(() => props.product, (newProduct) => {
  if (newProduct) {
    form.value = { ...newProduct };
  } else {
    form.value = { name: '', description: '', price: 0 };
  }
}, { immediate: true });

const saveProduct = async () => {
  try {
    if (props.product) {
      // Update existing product
      await api.put(`/products/${props.product.id}`, form.value);
    } else {
      // Add new product
      await api.post('/products', form.value);
    }
    emit('product-saved');
  } catch (error) {
    console.error('Error saving product:', error);
    // Optionally, display an error message to the user
  }
};
</script>
