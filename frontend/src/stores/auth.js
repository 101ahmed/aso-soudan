import { defineStore } from 'pinia'
import api, { ensureCsrf } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
    bootstrapped: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    permissions: (state) => state.user?.permissions || [],
    fullName: (state) =>
      state.user
        ? [state.user.first_name, state.user.last_name].filter(Boolean).join(' ') || state.user.name
        : '',
  },
  actions: {
    hasPermission(code) {
      if (!this.user) return false
      if (this.user.roles?.some((role) => role.code === 'SUPER_ADMIN')) return true
      return this.permissions.includes(code)
    },
    setUser(user) {
      this.user = user
    },
    clear() {
      this.user = null
    },
    async bootstrap() {
      if (this.bootstrapped) return this.user
      try {
        await this.fetchMe()
      } catch {
        this.clear()
      } finally {
        this.bootstrapped = true
      }
      return this.user
    },
    async login(payload) {
      this.loading = true
      this.error = null
      try {
        await ensureCsrf()
        const { data } = await api.post('/auth/login', payload)
        this.setUser(data.user)
        return data.user
      } catch (error) {
        this.error =
          error.response?.data?.errors?.email?.[0] ||
          error.userMessage ||
          error.response?.data?.message ||
          error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      const { data } = await api.get('/auth/me')
      this.setUser(data.data || data)
      return this.user
    },
    async logout() {
      try {
        await api.post('/auth/logout')
      } catch {
        // ignore network errors on logout
      } finally {
        this.clear()
      }
    },
  },
})
