import {defineStore} from 'pinia'
import {api} from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null as any, loading: false }),
  getters: { isAuthenticated: () => !!localStorage.getItem('mauzohub_token') },
  actions: {
    async login(email: string, password: string) {
      this.loading = true
      try {
        const { data } = await api.post('/auth/login', { email, password })
        const user = data.user ?? data
        localStorage.setItem('mauzohub_token', data.token)
        localStorage.setItem('mauzohub_name', user?.name ?? 'Admin')
        if (user?.id) localStorage.setItem('mauzohub_user_id', String(user.id))
        this.user = user
        return user
      } finally { this.loading = false }
    },
    async me() {
      const { data } = await api.get('/auth/me')
      this.user = data
      localStorage.setItem('mauzohub_name', data?.name ?? 'Admin')
      if (data?.id) localStorage.setItem('mauzohub_user_id', String(data.id))
      return data
    },
    async logout() {
      await api.post('/auth/logout').catch(() => {})
      localStorage.removeItem('mauzohub_token')
      localStorage.removeItem('mauzohub_name')
      localStorage.removeItem('mauzohub_user_id')
      this.user = null
    }
  }
})
