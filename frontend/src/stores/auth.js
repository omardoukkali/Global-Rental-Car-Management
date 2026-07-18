import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isClient  = computed(() => user.value?.role === 'client')
  const isOwner   = computed(() => user.value?.role === 'agency_owner')
  const isAdmin   = computed(() => user.value?.role === 'admin')

  function setSession(newToken, newUser) {
    token.value = newToken
    user.value  = newUser
    localStorage.setItem('token', newToken)
    localStorage.setItem('user', JSON.stringify(newUser))
  }

  function clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function login(credentials) {
    loading.value = true
    try {
      const data = await api.post('/auth/login', credentials)
      setSession(data.token, data.user)
      return data.user
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    clearSession()
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const data = await api.get('/auth/me')
      user.value = data.user
      localStorage.setItem('user', JSON.stringify(data.user))
    } catch {
      clearSession()
    }
  }

  return { token, user, loading, isAuthenticated, isClient, isOwner, isAdmin,
           login, logout, fetchMe, setSession, clearSession }
})
