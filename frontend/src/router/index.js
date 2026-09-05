import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AgencySettings from '@/pages/AgencySettings.vue'
import AgencyProfile from '@/pages/AgencyProfile.vue'
import AgencyLocations from '@/pages/AgencyLocations.vue'
import AdminAgencyValidation from '@/pages/AdminAgencyValidation.vue'
import AgencyCars from '@/pages/AgencyCars.vue'

const routes = [{
        path: '/',
        name: 'home',
        component: () =>
            import ('@/pages/Logout.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: () =>
            import ('@/pages/Login.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/register',
        name: 'register',
        component: () =>
            import ('@/pages/Register.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/logout',
        name: 'logout',
        component: () =>
            import ('@/pages/Logout.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/settings',
        name: 'AgencySettings',
        component: AgencySettings,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/profile',
        name: 'AgencyProfile',
        component: AgencyProfile,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/locations',
        name: 'AgencyLocations',
        component: AgencyLocations,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/agencies/validation',
        name: 'AdminAgencyValidation',
        component: AdminAgencyValidation,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/cars',
        name: 'AgencyCars',
        component: AgencyCars,
        meta: { requiresAuth: true }
    }
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