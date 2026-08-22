import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    { path: '/', name: 'home', component: () => import('@/pages/Home.vue'), meta: { requiresAuth: true } },
    { path: '/login', name: 'login', component: () => import('@/pages/Login.vue'), meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('@/pages/Register.vue'), meta: { guestOnly: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()
    const isAuthenticated = auth.isAuthenticated
    const requiresAuth = to.meta.requiresAuth
    const guestOnly = to.meta.guestOnly

    // If route requires auth and user is not authenticated
    if (requiresAuth && !isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    // If route is guest-only and user is authenticated
    if (guestOnly && isAuthenticated) {
        next({ name: 'home' })
        return
    }

    // Allow navigation
    next()
})

export default router