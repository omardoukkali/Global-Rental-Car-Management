import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AgencySettings from '@/pages/AgencySettings.vue'
import AgencyProfile from '@/pages/AgencyProfile.vue'
import AgencyLocations from '@/pages/AgencyLocations.vue'
import AdminAgencyValidation from '@/pages/AdminAgencyValidation.vue'
import AgencyCars from '@/pages/AgencyCars.vue'
import CarForm from '@/pages/CarForm.vue'
import AgencyDashboard from '@/pages/AgencyDashboard.vue'
import ReservationForm from '@/pages/ReservationForm.vue'
import ClientReservations from '@/pages/ClientReservations.vue'

const routes = [{
        path: '/',
        name: 'home',
        redirect: '/login'
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
    },
    {
        path: '/agency/cars/new',
        name: 'AgencyCarCreate',
        component: CarForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/cars/:id/edit',
        name: 'AgencyCarEdit',
        component: CarForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/dashboard',
        name: 'AgencyDashboard',
        component: AgencyDashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/reservations/new',
        name: 'ReservationCreate',
        component: ReservationForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/cars/:carId/reserve',
        name: 'CarReserve',
        component: ReservationForm,
        props: true,
        meta: { requiresAuth: true }
    },
    {
        path: '/myreservations',
        name: 'ClientReservations',
        component: ClientReservations,
        meta: { requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

function homeForRole(user) {
    const role = user?.role
    if (role === 'agency') return { name: 'AgencyDashboard' }
    if (role === 'admin') return { name: 'AdminAgencyValidation' }
    return { name: 'ClientReservations' }
}

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()
    const isAuthenticated = auth.isAuthenticated

    if (to.path === '/' && isAuthenticated) {
        next(homeForRole(auth.user))
        return
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    if (to.meta.guestOnly && isAuthenticated) {
        next(homeForRole(auth.user))
        return
    }

    next()
})

export default router
