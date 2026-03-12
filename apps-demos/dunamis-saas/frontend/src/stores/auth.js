import { reactive, readonly } from 'vue'
import { authApi, getCsrfCookie } from '../services/api'

const state = reactive({
  user: null,
  loading: false,
  initialized: false,
})

const setUser = (user) => {
  state.user = user
}

const fetchUser = async () => {
  try {
    const { data } = await authApi.me()
    const user = data?.data ?? data ?? null
    setUser(user)
    return user
  } catch (error) {
    setUser(null)
    const status = error?.response?.status
    if (status === 401 || status === 404) {
      return null
    }
    return null
  } finally {
    state.initialized = true
  }
}

const login = async (payload) => {
  state.loading = true
  try {
    await getCsrfCookie()
    await authApi.login(payload)
    return await fetchUser()
  } finally {
    state.loading = false
  }
}

const logout = async () => {
  state.loading = true
  try {
    await getCsrfCookie()
    await authApi.logout()
  } finally {
    setUser(null)
    state.loading = false
  }
}

const ensureUser = async () => {
  if (state.initialized) {
    return state.user
  }
  return fetchUser()
}

export const useAuthStore = () => ({
  state: readonly(state),
  get isAuthenticated() {
    return Boolean(state.user)
  },
  fetchUser,
  ensureUser,
  login,
  logout,
})
