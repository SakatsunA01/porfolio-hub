<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Product Management</h1>
        <button @click="openProductForm()" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Add New Product
        </button>
      </div>
    </header>
    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-8 sm:px-0">
          <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Product List</h3>
            </div>
            <div class="border-t border-gray-200">
              <dl>
                <div v-for="product in products" :key="product.id" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt class="text-sm font-medium text-gray-500">{{ product.name }}</dt>
                  <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 flex justify-between items-center">
                    <span>{{ product.description }} - ${{ product.price }}</span>
                    <div>
                      <button @click="openProductForm(product)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                      <button @click="deleteProduct(product.id)" class="text-red-600 hover:text-red-900">Delete</button>
                    </div>
                  </dd>
                </div>
                <div v-if="products.length === 0" class="bg-white px-4 py-5 sm:px-6">
                  <p class="text-sm text-gray-500">No products found.</p>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Product Form Modal -->
    <div v-if="showProductFormModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <ProductForm :product="editingProduct" @product-saved="handleProductSaved" @cancel="closeProductForm" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import ProductForm from '../../components/ProductForm.vue';

const products = ref([]);
const showProductFormModal = ref(false);
const editingProduct = ref(null);

const fetchProducts = async () => {
  try {
    const response = await api.get('/products');
    products.value = response.data;
  } catch (error) {
    console.error('Error fetching products:', error);
  }
};

const openProductForm = (product = null) => {
  editingProduct.value = product;
  showProductFormModal.value = true;
};

const closeProductForm = () => {
  showProductFormModal.value = false;
  editingProduct.value = null;
};

const handleProductSaved = () => {
  closeProductForm();
  fetchProducts(); // Refresh the list
};

const deleteProduct = async (id) => {
  if (confirm('Are you sure you want to delete this product?')) {
    try {
      await api.delete(`/products/${id}`);
      fetchProducts(); // Refresh the list
    } catch (error) {
      console.error('Error deleting product:', error);
    }
  }
};

onMounted(fetchProducts);
</script>
