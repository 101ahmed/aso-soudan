import { defineStore } from 'pinia'
import { fetchHealth } from '@/services/api'

export const useAppStore = defineStore('app', {
  state: () => ({
    health: null,
    healthError: null,
    loadingHealth: false,
  }),
  actions: {
    async checkHealth() {
      this.loadingHealth = true
      this.healthError = null

      try {
        this.health = await fetchHealth()
      } catch (error) {
        this.health = null
        this.healthError = error?.message || 'API unreachable'
      } finally {
        this.loadingHealth = false
      }
    },
  },
})
