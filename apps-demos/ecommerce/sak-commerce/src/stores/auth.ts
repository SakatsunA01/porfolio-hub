import { defineStore } from 'pinia';
import api, { login as apiLogin } from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.user,
    isAdmin: (state) => state.user && state.user.is_admin,
  },
  actions: {
    async login(credentials) {
      try {
        const response = await apiLogin(credentials);
        await this.fetchUser();
        return response.data;
      } catch (error) {
        this.user = null;
        throw error;
      }
    },
    async logout() {
      try {
        await api.post('/logout');
        this.user = null;
      } catch (error) {
        throw error;
      }
    },
    async fetchUser() {
      try {
        const response = await api.get('/user');
        this.user = response.data;
      } catch (error) {
        this.user = null;
      }
    },
  },
});
