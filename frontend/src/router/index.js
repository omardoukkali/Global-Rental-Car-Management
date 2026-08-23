import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    { path: '/', name: 'home', component: () => import('@/pages/Logout.vue') },
    { path: '/login', name: 'login', component: () => import('@/pages/Login.vue'), meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('@/pages/Register.vue'), meta: { guestOnly: true } },
    { path: '/logout', name: 'logout', component: () => import('@/pages/Logout.vue'), meta: { requiresAuth: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()
    const isAuthenticated = auth.isAuthenticated

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    if (to.meta.guestOnly && isAuthenticated) {
        next({ name: 'logout' })
        return
    }

    next()
})

export default router