import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('token') || null)
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const isAuthenticated = computed(() => !!token.value)

    function setSession(newToken, newUser) {
        token.value = newToken
        user.value = newUser
        localStorage.setItem('token', newToken)
        localStorage.setItem('user', JSON.stringify(newUser))
    }

    function clearSession() {
        token.value = null
        user.value = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    async function login(credentials) {
        const data = await api.post('/login', credentials)
        setSession(data.token, data.user)
        return data.user
    }

    async function logout() {
        try { await api.post('/logout') } catch {}
        clearSession()
    }

    return { token, user, isAuthenticated, setSession, clearSession, login, logout }
})