import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('rdp_token'),
    user: JSON.parse(localStorage.getItem('rdp_user') || 'null'),
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
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
    persist(token, user) {
      this.token = token
      this.user = user
      localStorage.setItem('rdp_token', token)
      localStorage.setItem('rdp_user', JSON.stringify(user))
    },
    clear() {
      this.token = null
      this.user = null
      localStorage.removeItem('rdp_token')
      localStorage.removeItem('rdp_user')
    },
    async login(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/auth/login', {
          ...payload,
          device_name: 'rdp-web',
        })
        this.persist(data.token, data.user)
        return data.user
      } catch (error) {
        this.error =
          error.userMessage || error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      if (!this.token) return null
      const { data } = await api.get('/auth/me')
      this.user = data.data || data
      localStorage.setItem('rdp_user', JSON.stringify(this.user))
      return this.user
    },
    async logout() {
      try {
        if (this.token) {
          await api.post('/auth/logout')
        }
      } catch {
        // ignore network errors on logout
      } finally {
        this.clear()
      }
    },
  },
})
