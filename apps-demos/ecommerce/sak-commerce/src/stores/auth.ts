import { defineStore } from 'pinia'
import api, { login as apiLogin, logout as apiLogout } from '../services/api'

type AuthUser = {
  id: number
  name: string
  email: string
  admin_sn?: boolean
  profile_photo_url?: string | null
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as AuthUser | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    isAdmin: (state) => state.user?.admin_sn === true,
  },

  actions: {
    async login(credentials: { email: string; password: string }) {
      try {
        const response = await apiLogin(credentials)
        await this.fetchUser()
        return response.data
      } catch (error) {
        this.user = null
        throw error
      }
    },

    async logout() {
      try {
        await apiLogout()
      } finally {
        this.user = null
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/user')
        this.user = response.data
      } catch {
        this.user = null
      }
    },

    async updateProfile(payload: { name: string; email: string }) {
      const response = await api.put('/account/profile', payload)
      this.user = response.data.data
      return this.user
    },
  },
})
