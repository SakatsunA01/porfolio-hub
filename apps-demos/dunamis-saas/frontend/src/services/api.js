import axios from 'axios'

const backendUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')

export const api = axios.create({
  baseURL: `${backendUrl}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

const sanctum = axios.create({
  baseURL: backendUrl,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

export const getCsrfCookie = () => sanctum.get('/sanctum/csrf-cookie')

export const healthCheck = () => sanctum.get('/api/health')

export const authApi = {
  login: (payload) => api.post('/auth/login', payload),
  me: () => api.get('/auth/me'),
  logout: () => api.post('/auth/logout'),
}

export const dashboardApi = {
  fetch: () => api.get('/dashboard'),
}

export const productsApi = {
  list: (params = {}) => api.get('/products', { params }),
  create: (payload) => api.post('/products', payload, { headers: { 'Content-Type': 'multipart/form-data' } }),
  update: (id, payload) => api.post(`/products/${id}?_method=PUT`, payload, { headers: { 'Content-Type': 'multipart/form-data' } }),
  remove: (id) => api.delete(`/products/${id}`),
}

export const clientsApi = {
  list: (params = {}) => api.get('/clients', { params }),
  create: (payload) => api.post('/clients', payload),
  update: (id, payload) => api.put(`/clients/${id}`, payload),
  remove: (id) => api.delete(`/clients/${id}`),
}

export const salesApi = {
  list: (params = {}) => api.get('/sales', { params }),
  create: (payload) => api.post('/sales', payload),
  show: (id) => api.get(`/sales/${id}`),
}

export const reportsApi = {
  profit: (params = {}) => api.get('/reports/profit', { params }),
}

export const profileApi = {
  show: () => api.get('/profile'),
  update: (payload) => api.put('/profile', payload),
  updatePassword: (payload) => api.put('/profile/password', payload),
}

export default api
