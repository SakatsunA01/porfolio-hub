import axios from 'axios'

const backendUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')

export const api = axios.create({
  baseURL: `${backendUrl}/api/v1`,
  withCredentials: true,
  withXSRFToken: false,
  headers: {
    Accept: 'application/json',
  },
})

const sanctum = axios.create({
  baseURL: backendUrl,
  withCredentials: true,
  withXSRFToken: false,
  headers: {
    Accept: 'application/json',
  },
})

export const getCsrfCookie = () => sanctum.get('/sanctum/csrf-cookie')

export const healthCheck = () => sanctum.get('/api/health')

export const authApi = {
  login: (payload) => api.post('/auth/login', payload, { withXSRFToken: true }),
  me: () => sanctum.get('/api/user', { withXSRFToken: false }),
  logout: () => api.post('/auth/logout', {}, { withXSRFToken: true }),
}

export const dashboardApi = {
  fetch: () => api.get('/dashboard'),
}

export const productsApi = {
  list: (params = {}) => api.get('/products', { params }),
  create: (payload) => api.post('/products', payload, { withXSRFToken: true, headers: { 'Content-Type': 'multipart/form-data' } }),
  update: (id, payload) => api.post(`/products/${id}?_method=PUT`, payload, { withXSRFToken: true, headers: { 'Content-Type': 'multipart/form-data' } }),
  remove: (id) => api.delete(`/products/${id}`, { withXSRFToken: true }),
}

export const clientsApi = {
  list: (params = {}) => api.get('/clients', { params }),
  create: (payload) => api.post('/clients', payload, { withXSRFToken: true }),
  update: (id, payload) => api.put(`/clients/${id}`, payload, { withXSRFToken: true }),
  remove: (id) => api.delete(`/clients/${id}`, { withXSRFToken: true }),
}

export const salesApi = {
  list: (params = {}) => api.get('/sales', { params }),
  create: (payload) => api.post('/sales', payload, { withXSRFToken: true }),
  show: (id) => api.get(`/sales/${id}`),
}

export const reportsApi = {
  profit: (params = {}) => api.get('/reports/profit', { params }),
}

export const profileApi = {
  show: () => api.get('/profile'),
  update: (payload) => api.put('/profile', payload, { withXSRFToken: true }),
  updatePassword: (payload) => api.put('/profile/password', payload, { withXSRFToken: true }),
}

export default api
