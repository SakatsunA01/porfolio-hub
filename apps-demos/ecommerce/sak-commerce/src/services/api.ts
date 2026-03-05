import axios from 'axios'

const backendUrl = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

const apiBaseUrl = backendUrl ? `${backendUrl}/api` : '/api'
const sanctumBaseUrl = backendUrl || ''

const api = axios.create({
  baseURL: apiBaseUrl,
  withCredentials: true,
  withXSRFToken: true,
})

const sanctumApi = axios.create({
  baseURL: sanctumBaseUrl,
  withCredentials: true,
  withXSRFToken: true,
})

export const getCsrfCookie = () => {
  console.log('Attempting to get CSRF cookie...')
  return sanctumApi.get('/sanctum/csrf-cookie').then((response) => {
    console.log('CSRF cookie request successful.')
    return response
  }).catch((error) => {
    console.error('CSRF cookie request failed:', error)
    throw error
  })
}

export const login = (credentials: { email: string; password: string }) => {
  return api.post('/login', credentials)
}

export const register = (payload: {
  name: string
  email: string
  password: string
  password_confirmation: string
}) => {
  return api.post('/register', payload)
}

export const logout = () => {
  return api.post('/logout')
}

export const fetchStoreProducts = () => api.get('/store/products')
export const fetchStoreProduct = (id: number) => api.get(`/store/products/${id}`)
export const fetchStoreCategories = () => api.get('/store/categories')
export const fetchAdminDashboard = () => api.get('/admin/dashboard')
export const fetchAdminProducts = () => api.get('/admin/products')
export const fetchAdminOrders = () => api.get('/admin/orders')

api.interceptors.request.use((request) => {
  console.log('API Request:', request)
  return request
}, (error) => {
  console.error('API Request Error:', error)
  return Promise.reject(error)
})

api.interceptors.response.use((response) => {
  console.log('API Response:', response)
  return response
}, (error) => {
  console.error('API Response Error:', error.response || error)
  return Promise.reject(error)
})

export default api
